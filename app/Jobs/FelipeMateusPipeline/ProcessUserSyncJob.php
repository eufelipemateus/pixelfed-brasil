<?php

namespace App\Jobs\FelipeMateusPipeline;

use App\User;
use App\Services\FelipeMateus\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Enums\StatusEnums;


class ProcessUserSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function handle()
    {
        try {
            $user = $this->user;
            $subscriberId = $user->settings['felipemateus_subscriber_id'] ?? null;

            if ($user->status === StatusEnums::DELETED) {
                if ($subscriberId) {
                    EmailService::deleteSubscriber($user);
                    Log::info("Subscriber removido para o usuário ID {$user->id}");
                }
                return;
            }

            if ($subscriberId) {
                EmailService::updateSubscriber($user);
                Log::info("Subscriber atualizado para o usuário ID {$user->id}");
            } else {
                EmailService::addSubscriber($user);
                Log::info("Subscriber criado para o usuário ID {$user->id}");
            }
        } catch (\Throwable $e) {
            Log::error("Erro ao sincronizar subscriber do usuário ID {$user->id}: " . $e->getMessage());
        }
    }
}
