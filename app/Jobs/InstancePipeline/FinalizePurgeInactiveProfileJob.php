<?php

namespace App\Jobs\InstancePipeline;

use App\Jobs\MediaPipeline\MediaDeletePipeline;
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
            $this->release(600);

            return;
        }

        if (Status::withTrashed()->whereProfileId($this->profileId)->exists()) {
            $this->release(600);

            return;
        }

        Media::whereProfileId($this->profileId)
            ->whereNull('status_id')
            ->cursor()
            ->each(function (Media $media) {
                if ($media->media_path) {
                    MediaDeletePipeline::dispatch($media)->onQueue('mmo');
                } else {
                    $media->forceDelete();
                }
            });

        if (Media::whereProfileId($this->profileId)->exists()) {
            $this->release(600);

            return;
        }

        Media::onlyTrashed()->whereProfileId($this->profileId)->forceDelete();
        AccountService::del($this->profileId);
        $profile->forceDelete();
    }
}
