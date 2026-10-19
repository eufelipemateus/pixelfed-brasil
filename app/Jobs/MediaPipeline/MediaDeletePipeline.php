<?php

namespace App\Jobs\MediaPipeline;

use App\Models\Media;
use App\Services\InstancePurgeService;
use App\Services\Media\MediaHlsService;
use App\Services\UserStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MediaDeletePipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $media;

    protected ?int $purgeInstanceId = null;

    public $timeout = 300;

    public $tries = 3;

    public $maxExceptions = 1;

    public $failOnTimeout = true;

    public $deleteWhenMissingModels = true;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 3600;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'media:purge-job:id-'.$this->media->id;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("media:purge-job:id-{$this->media->id}"))->shared()->dontRelease()];
    }

    public function __construct(Media $media, ?int $purgeInstanceId = null)
    {
        $this->media = $media;
        $this->purgeInstanceId = $purgeInstanceId;
    }

    public function handle()
    {
        $media = $this->media;

        // Verify media exists
        if (! $media) {
            Log::info('MediaDeletePipeline: Media no longer exists, skipping job');

            return 1;
        }

        if (
            $this->purgeInstanceId !== null
            && ! InstancePurgeService::isPurgeAuthorizedForProfile(
                $this->purgeInstanceId,
                $media->profile_id
            )
        ) {
            Log::info('MediaDeletePipeline: purge cancelado após revalidação', [
                'instance_id' => $this->purgeInstanceId,
                'profile_id' => $media->profile_id,
                'media_id' => $media->id,
            ]);

            return 1;
        }

        // Verify media is still orphaned before deleting
        if ($media->status_id !== null) {
            Log::info('MediaDeletePipeline: Media is attached to a status, skipping deletion', [
                'media_id' => $media->id,
                'status_id' => $media->status_id,
                'profile_id' => $media->profile_id,
                'user_id' => $media->user_id,
                'mime' => $media->mime,
                'size' => $media->size,
                'order' => $media->order,
                'media_path' => $media->media_path,
                'thumbnail_path' => $media->thumbnail_path,
                'hls_path' => $media->hls_path,
                'remote_media' => (bool) $media->remote_media,
                'created_at' => $media->created_at?->toDateTimeString(),
                'updated_at' => $media->updated_at?->toDateTimeString(),
            ]);

            return 1;
        }

        $ownerUserId = $media->user_id;
        $ownerMediaSize = (int) $media->size;
        $path = $media->media_path;
        $thumb = $media->thumbnail_path;

        if (! $path) {
            Log::info('MediaDeletePipeline: Media has no path, skipping deletion', [
                'media_id' => $media->id,
                'status_id' => $media->status_id,
                'profile_id' => $media->profile_id,
                'user_id' => $media->user_id,
                'mime' => $media->mime,
                'thumbnail_path' => $media->thumbnail_path,
                'hls_path' => $media->hls_path,
            ]);

            return 1;
        }

        $e = explode('/', $path);
        array_pop($e);
        $i = implode('/', $e);

        try {
            if ((bool) config_cache('pixelfed.cloud_storage') === true) {
                $disk = Storage::disk(config('filesystems.cloud'));

                if ($path && $disk->exists($path)) {
                    $disk->delete($path);
                }

                if ($thumb && $disk->exists($thumb)) {
                    $disk->delete($thumb);
                }
            }

            $disk = Storage::disk(config('filesystems.local'));

            if ($path && $disk->exists($path)) {
                $disk->delete($path);
            }

            if ($thumb && $disk->exists($thumb)) {
                $disk->delete($thumb);
            }

            if ($media->hls_path != null) {
                $files = MediaHlsService::allFiles($media);
                if ($files && count($files)) {
                    foreach ($files as $file) {
                        $disk->delete($file);
                    }
                }
            }

            $media->delete();

            if ($ownerUserId) {
                UserStorageService::decrementStorageUsed($ownerUserId, $ownerMediaSize);
            }
        } catch (\Exception $e) {
            Log::warning('MediaDeletePipeline: Failed to delete media', [
                'media_id' => $media->id,
                'status_id' => $media->status_id,
                'media_path' => $path,
                'thumbnail_path' => $thumb,
                'hls_path' => $media->hls_path,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return 1;
    }

    public function failed(Throwable $exception): void
    {
        if ($this->purgeInstanceId !== null && $this->media) {
            InstancePurgeService::recordProfileFailure(
                $this->media->profile_id,
                $this->purgeInstanceId,
                $exception
            );
        }

        Log::error('MediaDeletePipeline: exclusão da mídia falhou', [
            'instance_id' => $this->purgeInstanceId,
            'profile_id' => $this->media?->profile_id,
            'media_id' => $this->media?->id,
            'attempt' => $this->attempts(),
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
        ]);
    }
}
