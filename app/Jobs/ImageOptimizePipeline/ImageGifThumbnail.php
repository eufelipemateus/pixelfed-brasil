<?php

namespace App\Jobs\ImageOptimizePipeline;

use App\Media;
use FFMpeg;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Storage;

class ImageGifThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $media;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Media $media)
    {
        $this->media = $media;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $media = $this->media;

        if (!$media) {
            error_log("Media not found in ImageGifThumbnail job.\n");
            return;
        }

        $pathInfo = pathinfo($media->media_path);
        $thumbPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_thumb.jpg';

        $url = $media->remote_media? $media->media_path : Storage::disk(config('filesystems.cloud'))->url($media->media_path);

        try {
            FFMpeg::openUrl($url)
                ->getFrameFromSeconds(1)
                ->export()
                ->toDisk(config('filesystems.default'))
                ->save($thumbPath);

            $media->update([
                'thumbnail_path' => $thumbPath,
                'thumbnail_url' => Storage::disk(config('filesystems.default'))->url($thumbPath),
            ]);
        } catch (\Exception $e) {
            error_log("ImageGifThumbnail job failed: " . $e->getMessage());
        }
    }
}
