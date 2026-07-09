<?php

namespace App\Jobs\InstancePipeline;

use App\Services\InstancePurgeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PurgeInactiveInstancesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $timeout = 120;

    public $tries = 3;

    public function __construct()
    {
        $this->onQueue('delete');
    }

    public function handle()
    {
        $threshold = InstancePurgeService::threshold();

        InstancePurgeService::inactiveInstancesQuery($threshold)
            ->orderBy('id')
            ->chunkById(100, function ($instances) use ($threshold) {
                foreach ($instances as $instance) {
                    PurgeInactiveInstanceJob::dispatch($instance->id)
                        ->onQueue('delete');
                }
            });

        Log::info('PurgeInactiveInstancesJob: jobs por instância despachados', [
            'threshold' => $threshold->toDateTimeString(),
            'years' => InstancePurgeService::inactivityYears(),
        ]);
    }
}
