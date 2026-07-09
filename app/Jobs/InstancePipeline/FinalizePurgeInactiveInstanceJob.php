<?php

namespace App\Jobs\InstancePipeline;

use App\Instance;
use App\Profile;
use App\Services\InstancePurgeService;
use App\Services\InstanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class FinalizePurgeInactiveInstanceJob implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $instanceId;

    public $timeout = 120;

    public $tries = 24;

    public $maxExceptions = 3;

    public $failOnTimeout = true;

    public $uniqueFor = 3600;

    public function __construct(int $instanceId)
    {
        $this->instanceId = $instanceId;
        $this->onQueue('delete');
    }

    public function uniqueId(): string
    {
        return sprintf('purge-inactive-instance-finalize:%d', $this->instanceId);
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

        if (! InstancePurgeService::isStillInactive($instance->id)) {
            Log::info('FinalizePurgeInactiveInstanceJob: instância não está mais inativa', [
                'instance_id' => $instance->id,
                'domain' => $instance->domain,
            ]);

            return;
        }

        $remainingProfiles = Profile::query()->where('domain', $instance->domain)->count();

        if ($remainingProfiles > 0) {
            Log::info('FinalizePurgeInactiveInstanceJob: aguardando limpeza completa de perfis', [
                'instance_id' => $instance->id,
                'domain' => $instance->domain,
                'remaining_profiles' => $remainingProfiles,
                'attempt' => $this->attempts(),
            ]);

            $this->release(600);

            return;
        }

        try {
            if (InstancePurgeService::hasModerationData($instance)) {
                InstancePurgeService::persistModerationRule($instance);
            }

            $instance->delete();
            InstanceService::refresh();

            Log::info('FinalizePurgeInactiveInstanceJob: instância removida com sucesso', [
                'instance_id' => $instance->id,
                'domain' => $instance->domain,
            ]);
        } catch (Throwable $e) {
            Log::error('FinalizePurgeInactiveInstanceJob: falha ao finalizar purge da instância', [
                'instance_id' => $instance->id,
                'domain' => $instance->domain,
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('FinalizePurgeInactiveInstanceJob: job finalizador falhou', [
            'instance_id' => $this->instanceId,
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
        ]);
    }
}
