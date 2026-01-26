<?php

namespace App\Jobs;

use App\Profile;
use App\Status;
use App\Media;
use App\Instance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurgeInactiveInstancesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 604800; // 1 semana em segundos

    public function handle()
    {
        $threshold = Carbon::now()->subYears(2);
        // Garante que estamos usando o disco correto (S3)
        $disk = config('filesystems.default');

        $inactiveDomains = Instance::where(function ($query) use ($threshold) {
            $query->where(function ($q) use ($threshold) {
                $q->where('created_at', '<', $threshold)
                    ->whereNull('nodeinfo_last_fetched');
            })
                ->orWhere(function ($q) use ($threshold) {
                    $q->whereNotNull('nodeinfo_last_fetched')
                        ->where('last_active_at', '<', $threshold);
                });
        })
            ->pluck('domain');

        if ($inactiveDomains->isEmpty()) {
            return;
        }

        // Processamos em chunks de perfis
        Profile::whereIn('domain', $inactiveDomains)->chunkById(50, function ($profiles) use ($disk) {
            foreach ($profiles as $profile) {
                try {
                    // 1. Coletar caminhos para deletar do storage físico
                    Media::where('profile_id', $profile->id)->chunk(500, function ($medias) use ($disk) {
                        $paths = [];
                        foreach ($medias as $m) {
                            if ($m->media_path && !filter_var($m->media_path, FILTER_VALIDATE_URL)) {
                                $paths[] = $m->media_path;
                            }
                            if ($m->thumbnail_path && !filter_var($m->thumbnail_path, FILTER_VALIDATE_URL)) {
                                $paths[] = $m->thumbnail_path;
                            }
                        }

                        if (!empty($paths)) {
                            Storage::disk($disk)->delete($paths);
                        }
                    });

                    // 2. Limpeza do Banco de Dados
                    DB::transaction(function () use ($profile) {
                        // Ordem: Mídias e Status primeiro, Perfil por último
                        Media::where('profile_id', $profile->id)->forceDelete();
                        Status::where('profile_id', $profile->id)->forceDelete();

                        // Limpa relacionamentos de rede (Follows)
                        DB::table('follows')
                            ->where('follower_id', $profile->id)
                            ->orWhere('following_id', $profile->id)
                            ->delete();

                        $profile->forceDelete();
                    });

                    Log::info("Purge: Conteúdo removido - Perfil #{$profile->id} do domínio {$profile->domain}");
                } catch (\Exception $e) {
                    Log::error("Falha ao limpar perfil #{$profile->id}: " . $e->getMessage());
                }
            }
        });

        Instance::whereIn('domain', $inactiveDomains)->delete();

        Log::info('Purge: Instâncias inativas removidas: ' . $inactiveDomains->implode(', '));
    }
}
