<?php

namespace App\Console\Commands;

use App\Media;
use App\Jobs\InstancePipeline\PurgeInactiveInstancesJob;
use App\Profile;
use App\Services\InstancePurgeService;
use App\Status;
use Illuminate\Console\Command;

class PurgeInactiveInstances extends Command
{
    protected $signature = 'instances:purge-inactive {--dry-run : Exibe candidatos sem apagar dados}';
    protected $description = 'Remove dados de instâncias federadas inativas com segurança e em fases';

    public function handle()
    {
        if ($this->option('dry-run')) {
            return $this->handleDryRun();
        }

        PurgeInactiveInstancesJob::dispatch()->onQueue('delete');
        $this->info('Job de limpeza de instâncias inativas disparado na fila delete.');

        return self::SUCCESS;
    }

    protected function handleDryRun(): int
    {
        $threshold = InstancePurgeService::threshold();
        $query = InstancePurgeService::inactiveInstancesQuery($threshold);
        $totalInstances = (clone $query)->count();

        $this->info('Dry-run de purge de instâncias inativas');
        $this->line(sprintf('Anos de inatividade: %d', InstancePurgeService::inactivityYears()));
        $this->line(sprintf('Threshold: %s', $threshold->toDateTimeString()));
        $this->line(sprintf('Instâncias candidatas: %d', $totalInstances));

        if ($totalInstances === 0) {
            return self::SUCCESS;
        }

        $totalProfiles = 0;
        $totalStatuses = 0;
        $totalMedia = 0;

        (clone $query)
            ->select(['id', 'domain'])
            ->orderBy('id')
            ->chunkById(100, function ($instances) use (&$totalProfiles, &$totalStatuses, &$totalMedia) {
                foreach ($instances as $instance) {
                    $profileIds = Profile::query()
                        ->where('domain', $instance->domain)
                        ->pluck('id');

                    $profileCount = $profileIds->count();
                    $statusCount = $profileCount > 0
                        ? Status::query()->whereIn('profile_id', $profileIds)->count()
                        : 0;
                    $mediaCount = $profileCount > 0
                        ? Media::query()->whereIn('profile_id', $profileIds)->count()
                        : 0;

                    $totalProfiles += $profileCount;
                    $totalStatuses += $statusCount;
                    $totalMedia += $mediaCount;

                    $this->line(sprintf(
                        '- instance_id=%d domain=%s profiles=%d statuses=%d media=%d',
                        $instance->id,
                        $instance->domain,
                        $profileCount,
                        $statusCount,
                        $mediaCount
                    ));
                }
            });

        $this->line('---');
        $this->line('Totais dry-run:');
        $this->line(sprintf('profiles=%d', $totalProfiles));
        $this->line(sprintf('statuses=%d', $totalStatuses));
        $this->line(sprintf('media=%d', $totalMedia));

        return self::SUCCESS;
    }
}
