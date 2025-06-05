<?php

namespace App\Jobs\ImageOptimizePipeline;

use App\Media;
use FFMpeg;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
            error("Media not found in ImageGifThumbnail job.\n");
            return;
        }

        $path = storage_path('app/' . $media->media_path);

        if (!is_file($path)) {
            error("Tried gerenerate thmbnail to media that does not exist or is not readable: $path \n");
            return;
        }

        $pathInfo = pathinfo($media->media_path);
        $thumbPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_thumb.png';

        FFMpeg::fromDisk(config('filesystems.default'))
            ->open($media->media_path)
            ->getFrameFromSeconds(1)
            ->export()
            ->save($thumbPath);

        $media->update([
            'thumbnail_path' => $thumbPath
        ]);
    }
}
