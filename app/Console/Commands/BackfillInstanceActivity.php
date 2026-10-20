<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillInstanceActivity extends Command
{
    protected $signature = 'instances:backfill-activity
        {--chunk=100 : Instâncias consultadas por lote}
        {--limit=0 : Máximo de instâncias nesta execução; zero processa todas}
        {--after-id=0 : Retoma depois deste ID de instância}
        {--sleep=0 : Pausa em milissegundos entre lotes}
        {--dry-run : Calcula o backfill sem persistir alterações}';

    protected $description = 'Preenche a última atividade federada por lotes retomáveis';

    public function handle(): int
    {
        $chunk = min(1000, max(1, (int) $this->option('chunk')));
        $limit = max(0, (int) $this->option('limit'));
        $cursor = max(0, (int) $this->option('after-id'));
        $sleepMilliseconds = min(60000, max(0, (int) $this->option('sleep')));
        $remaining = $limit > 0 ? $limit : PHP_INT_MAX;
        $scanned = 0;
        $eligible = 0;
        $updated = 0;

        while ($remaining > 0) {
            $batchSize = min($chunk, $remaining);
            $instances = DB::table('instances')
                ->whereNull('last_active_at')
                ->where('id', '>', $cursor)
                ->select(['id', 'domain'])
                ->orderBy('id')
                ->limit($batchSize)
                ->get();

            if ($instances->isEmpty()) {
                break;
            }

            $activityByDomain = DB::table('profiles')
                ->whereIn('domain', $instances->pluck('domain'))
                ->whereNotNull('last_status_at')
                ->where('last_status_at', '<=', now())
                ->select('domain', DB::raw('MAX(last_status_at) as last_active_at'))
                ->groupBy('domain')
                ->pluck('last_active_at', 'domain');

            foreach ($instances as $instance) {
                $cursor = (int) $instance->id;
                $scanned++;
                $remaining--;
                $lastActiveAt = $activityByDomain->get($instance->domain);

                if ($lastActiveAt === null) {
                    continue;
                }

                $eligible++;

                if (! $this->option('dry-run')) {
                    $updated += DB::table('instances')
                        ->where('id', $instance->id)
                        ->whereNull('last_active_at')
                        ->update(['last_active_at' => $lastActiveAt]);
                }
            }

            $this->line(sprintf(
                'cursor=%d scanned=%d eligible=%d updated=%d',
                $cursor,
                $scanned,
                $eligible,
                $updated
            ));

            if ($instances->count() < $batchSize) {
                break;
            }

            if ($sleepMilliseconds > 0) {
                usleep($sleepMilliseconds * 1000);
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Concluído: scanned=%d eligible=%d updated=%d last_id=%d%s',
            $scanned,
            $eligible,
            $updated,
            $cursor,
            $this->option('dry-run') ? ' dry-run=true' : ''
        ));

        if ($limit > 0 && $scanned === $limit) {
            $this->line("Para continuar, execute novamente com --after-id={$cursor}.");
        }

        return self::SUCCESS;
    }
}
