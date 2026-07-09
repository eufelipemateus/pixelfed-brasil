<?php

namespace App\Jobs\InstancePipeline;

use App\Instance;
use App\Jobs\DeletePipeline\DeleteRemoteProfilePipeline;
use App\Profile;
use App\Services\InstancePurgeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PurgeInactiveInstanceJob implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $instanceId;

    public $timeout = 300;

    public $tries = 5;

    public $maxExceptions = 3;

    public $failOnTimeout = true;

    public $deleteWhenMissingModels = true;

    public $uniqueFor = 3600;

    public function __construct(int $instanceId)
    {
        $this->instanceId = $instanceId;
        $this->onQueue('delete');
    }

    public function uniqueId(): string
    {
        return sprintf('purge-inactive-instance:%d', $this->instanceId);
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->uniqueId()))->shared()->dontRelease()];
    }

    public function handle(): void
    {
        $instance = Instance::query()->find($this->instanceId);

        if (! $instance) {
            return;
        }

        $threshold = InstancePurgeService::threshold();

        if (! InstancePurgeService::isStillInactive($instance->id, $threshold)) {
            Log::info('PurgeInactiveInstanceJob: instância voltou a ficar ativa, ignorando', [
                'instance_id' => $instance->id,
                'domain' => $instance->domain,
            ]);

            return;
        }

        Profile::query()
            ->where('domain', $instance->domain)
            ->orderBy('id')
            ->chunkById(100, function ($profiles) use ($instance) {
                foreach ($profiles as $profile) {
                    try {
                        DeleteRemoteProfilePipeline::dispatch($profile)->onQueue('delete');
                    } catch (Throwable $e) {
                        Log::error('PurgeInactiveInstanceJob: falha ao despachar exclusão de perfil remoto', [
                            'instance_id' => $instance->id,
                            'domain' => $instance->domain,
                            'profile_id' => $profile->id,
                            'exception_class' => get_class($e),
                            'exception_message' => $e->getMessage(),
                        ]);

                        throw $e;
                    }
                }
            });

        FinalizePurgeInactiveInstanceJob::dispatch($instance->id)->onQueue('delete');
    }

    public function failed(Throwable $exception): void
    {
        Log::error('PurgeInactiveInstanceJob: falha no processamento da instância', [
            'instance_id' => $this->instanceId,
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
        ]);
    }
}
