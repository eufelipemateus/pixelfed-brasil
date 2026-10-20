<?php

namespace App\Jobs\InstancePipeline;

use App\Jobs\DeletePipeline\DeleteRemoteProfilePipeline;
use App\Models\Instance;
use App\Models\Profile;
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
            InstancePurgeService::resetProgress($instance->id);

            Log::info('PurgeInactiveInstanceJob: instância voltou a ficar ativa, ignorando', [
                'instance_id' => $instance->id,
                'domain' => $instance->domain,
            ]);

            return;
        }

        if ($instance->purge_candidate_at === null) {
            InstancePurgeService::markCandidate($instance->id);

            Log::info('PurgeInactiveInstanceJob: instância marcada como candidata', [
                'instance_id' => $instance->id,
                'domain' => $instance->domain,
                'grace_days' => InstancePurgeService::graceDays(),
            ]);

            return;
        }

        $generation = InstancePurgeService::beginPurge($instance->id);

        if ($generation === null) {
            Log::info('PurgeInactiveInstanceJob: instância ainda está na janela de carência', [
                'instance_id' => $instance->id,
                'domain' => $instance->domain,
                'candidate_at' => $instance->purge_candidate_at?->toDateTimeString(),
            ]);

            return;
        }

        $dispatched = 0;
        $batchSize = InstancePurgeService::profileBatchSize();

        InstancePurgeService::profilesReadyForDispatch($instance->domain, $instance->id, $generation)
            ->orderBy('id')
            ->limit($batchSize)
            ->get()
            ->each(function (Profile $profile) use ($instance, $generation, &$dispatched) {
                if (! InstancePurgeService::claimProfileForPurge($profile, $instance->id, $generation)) {
                    return;
                }

                try {
                    DeleteRemoteProfilePipeline::dispatch($profile, $instance->id, $generation)->onQueue('delete');
                    $dispatched++;
                } catch (Throwable $e) {
                    InstancePurgeService::releaseProfileClaim($profile->id, $instance->id, $generation, $e);

                    Log::error('PurgeInactiveInstanceJob: falha ao despachar exclusão de perfil remoto', [
                        'instance_id' => $instance->id,
                        'domain' => $instance->domain,
                        'profile_id' => $profile->id,
                        'exception_class' => get_class($e),
                        'exception_message' => $e->getMessage(),
                    ]);

                    throw $e;
                }
            });

        $remainingCapacity = $batchSize - $dispatched;

        if ($remainingCapacity > 0) {
            InstancePurgeService::profilesReadyForFinalization($instance->domain, $instance->id, $generation)
                ->orderBy('id')
                ->limit($remainingCapacity)
                ->get()
                ->each(function (Profile $profile) use ($instance, $generation, &$dispatched) {
                    if (! InstancePurgeService::claimProfileForPurge($profile, $instance->id, $generation)) {
                        return;
                    }

                    try {
                        FinalizePurgeInactiveProfileJob::dispatch($profile->id, $instance->id, $generation)
                            ->onQueue('delete');
                        $dispatched++;
                    } catch (Throwable $e) {
                        InstancePurgeService::releaseProfileClaim($profile->id, $instance->id, $generation, $e);

                        throw $e;
                    }
                });
        }

        Log::info('PurgeInactiveInstanceJob: lote de perfis despachado', [
            'instance_id' => $instance->id,
            'domain' => $instance->domain,
            'profiles_dispatched' => $dispatched,
        ]);

        FinalizePurgeInactiveInstanceJob::dispatch($instance->id, $generation)
            ->delay(now()->addMinutes(10))
            ->onQueue('delete');
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
