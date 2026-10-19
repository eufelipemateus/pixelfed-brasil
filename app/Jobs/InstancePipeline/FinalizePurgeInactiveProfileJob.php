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
    ) {
        $this->onQueue('delete');
    }

    public function uniqueId(): string
    {
        return "purge-inactive-profile-finalize:{$this->profileId}";
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->uniqueId()))->shared()->dontRelease()];
    }

    public function handle(): void
    {
        if (! InstancePurgeService::isPurgeAuthorized($this->instanceId)) {
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
            ->chunkById(50, function ($statuses) use (&$statusesDispatched) {
                foreach ($statuses as $status) {
                    RemoteStatusDelete::dispatch($status, $this->instanceId)->onQueue('delete');
                    $statusesDispatched++;
                }
            });

        if ($statusesDispatched > 0) {
            Log::info('FinalizePurgeInactiveProfileJob: exclusões de posts recuperadas', [
                'instance_id' => $this->instanceId,
                'profile_id' => $this->profileId,
                'statuses_dispatched' => $statusesDispatched,
            ]);

            return;
        }

        $mediaDispatched = 0;

        Media::whereProfileId($this->profileId)
            ->whereNull('status_id')
            ->cursor()
            ->each(function (Media $media) use (&$mediaDispatched) {
                if ($media->media_path) {
                    MediaDeletePipeline::dispatch($media, $this->instanceId)->onQueue('mmo');
                    $mediaDispatched++;
                } else {
                    $media->forceDelete();
                }
            });

        if (Media::whereProfileId($this->profileId)->exists()) {
            Log::info('FinalizePurgeInactiveProfileJob: exclusões de mídia recuperadas', [
                'instance_id' => $this->instanceId,
                'profile_id' => $this->profileId,
                'media_dispatched' => $mediaDispatched,
            ]);

            return;
        }

        Media::onlyTrashed()->whereProfileId($this->profileId)->forceDelete();
        AccountService::del($this->profileId);
        $profile->forceDelete();
    }

    public function failed(Throwable $exception): void
    {
        InstancePurgeService::recordProfileFailure(
            $this->profileId,
            $this->instanceId,
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
}
