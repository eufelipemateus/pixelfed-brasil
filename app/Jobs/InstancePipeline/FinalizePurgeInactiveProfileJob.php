<?php

namespace App\Jobs\InstancePipeline;

use App\Jobs\MediaPipeline\MediaDeletePipeline;
use App\Jobs\StatusPipeline\RemoteStatusDelete;
use App\Models\Media;
use App\Models\Profile;
use App\Models\Status;
use App\Services\AccountService;
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

class FinalizePurgeInactiveProfileJob implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 0;

    public $timeout = 300;

    public $uniqueFor = 3600;

    public function __construct(
        public int $profileId,
        public int $instanceId,
        public string $generation,
    ) {
        $this->onQueue('delete');
    }

    public function uniqueId(): string
    {
        return "purge-inactive-profile-finalize:{$this->profileId}:{$this->generation}";
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->uniqueId()))->shared()->dontRelease()];
    }

    public function handle(): void
    {
        if (! InstancePurgeService::isPurgeAuthorizedForProfile(
            $this->instanceId,
            $this->profileId,
            $this->generation
        )) {
            Log::info('FinalizePurgeInactiveProfileJob: purge não está mais autorizado', [
                'instance_id' => $this->instanceId,
                'profile_id' => $this->profileId,
            ]);

            return;
        }

        $profile = Profile::withTrashed()->find($this->profileId);

        if (! $profile) {
            return;
        }

        if (! $profile->trashed() || $profile->deleted_at->gt(InstancePurgeService::profileRetentionThreshold())) {
            return;
        }

        $statusesDispatched = 0;

        Status::withTrashed()
            ->whereProfileId($this->profileId)
            ->orderBy('id')
            ->limit(InstancePurgeService::statusBatchSize())
            ->get()
            ->each(function ($status) use (&$statusesDispatched) {
                RemoteStatusDelete::dispatch(
                    $status,
                    $this->instanceId,
                    $this->generation
                )->onQueue('delete');
                $statusesDispatched++;
            });

        if ($statusesDispatched > 0) {
            Log::info('FinalizePurgeInactiveProfileJob: exclusões de posts recuperadas', [
                'instance_id' => $this->instanceId,
                'profile_id' => $this->profileId,
                'statuses_dispatched' => $statusesDispatched,
            ]);

            $this->dispatchNextBatch();

            return;
        }

        $mediaDispatched = 0;
        $mediaProcessed = 0;

        Media::whereProfileId($this->profileId)
            ->where(function ($query) {
                $query->whereNull('status_id')
                    ->orWhereNotExists(function ($statuses) {
                        $statuses->selectRaw('1')
                            ->from('statuses')
                            ->whereColumn('statuses.id', 'media.status_id');
                    });
            })
            ->orderBy('id')
            ->limit(InstancePurgeService::mediaBatchSize())
            ->get()
            ->each(function (Media $media) use (&$mediaDispatched, &$mediaProcessed) {
                if ($media->status_id !== null) {
                    $detached = Media::whereKey($media->id)
                        ->where('status_id', $media->status_id)
                        ->whereNotExists(function ($statuses) {
                            $statuses->selectRaw('1')
                                ->from('statuses')
                                ->whereColumn('statuses.id', 'media.status_id');
                        })
                        ->update(['status_id' => null]);

                    if ($detached !== 1) {
                        return;
                    }

                    $media->status_id = null;
                }

                if ($media->media_path) {
                    MediaDeletePipeline::dispatch(
                        $media,
                        $this->instanceId,
                        $this->generation
                    )->onQueue('mmo');
                    $mediaDispatched++;
                } else {
                    $this->forceDeletePathlessMedia($media);
                }

                $mediaProcessed++;
            });

        if (Media::whereProfileId($this->profileId)->exists()) {
            Log::info('FinalizePurgeInactiveProfileJob: exclusões de mídia recuperadas', [
                'instance_id' => $this->instanceId,
                'profile_id' => $this->profileId,
                'media_dispatched' => $mediaDispatched,
            ]);

            if ($mediaProcessed > 0) {
                $this->dispatchNextBatch();
            }

            return;
        }

        $trashedMedia = Media::onlyTrashed()
            ->whereProfileId($this->profileId)
            ->orderBy('id')
            ->limit(InstancePurgeService::mediaBatchSize())
            ->get();

        if ($trashedMedia->isNotEmpty()) {
            $trashedMedia->each(function (Media $media) {
                if ($media->media_path) {
                    MediaDeletePipeline::dispatch(
                        $media,
                        $this->instanceId,
                        $this->generation
                    )->onQueue('mmo');
                } else {
                    $this->forceDeletePathlessMedia($media);
                }
            });

            $this->dispatchNextBatch();

            return;
        }

        InstancePurgeService::authorizeDestructiveProfileStep(
            $this->instanceId,
            $this->profileId,
            $this->generation,
            function (Profile $storedProfile) {
                AccountService::del($this->profileId);
                $storedProfile->forceDelete();
            }
        );
    }

    public function failed(Throwable $exception): void
    {
        InstancePurgeService::recordProfileFailure(
            $this->profileId,
            $this->instanceId,
            $this->generation,
            $exception
        );

        Log::error('FinalizePurgeInactiveProfileJob: recuperação do perfil falhou', [
            'instance_id' => $this->instanceId,
            'profile_id' => $this->profileId,
            'attempt' => $this->attempts(),
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
        ]);
    }

    private function dispatchNextBatch(): void
    {
        self::dispatch($this->profileId, $this->instanceId, $this->generation)
            ->delay(now()->addMinutes(10))
            ->onQueue('delete');
    }

    private function forceDeletePathlessMedia(Media $media): void
    {
        InstancePurgeService::authorizeDestructiveProfileStep(
            $this->instanceId,
            $this->profileId,
            $this->generation,
            fn () => Media::withTrashed()->whereKey($media->id)->forceDelete()
        );
    }
}
