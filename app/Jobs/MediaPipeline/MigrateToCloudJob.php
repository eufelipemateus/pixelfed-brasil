<?php

namespace App\Jobs\MediaPipeline;

use App\Avatar;
use App\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;

class MigrateToCloudJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle()
    {
        info('Migrate to Cloud started.');
        info('Migrate Media to Cloud started.');

        Media::whereNotNull("id")->chunk(
            100, function ($medias) {
                foreach ($medias as $media) {
                    $this->migrateFile($media->media_path);
                    if (!empty($media->thumbnail_path)) {
                        $thumbnail_url = $this->migrateFile($media->thumbnail_path);
                        $media->thumbnail_url = $thumbnail_url;
                        $media->save();
                    }
                }
            }
        );

        info('Migrate Media to Cloud finished.');
        info('Migrate Avatars to Cloud started.');

        Avatar::chunk(
            100, function ($avatars) {
                foreach ($avatars as $avatar) {
                    $path = $avatar->media_path;
                    if (!Storage::disk("spaces")->exists($path)) {
                        $cdn_url = $this->migrateFile($path);
                        $avatar->cdn_url = $cdn_url;
                        $avatar->save();
                    }
                }
            }
        );

        info('Migrate Avatars to Cloud finished.');
        info('Migrate to Cloud finished.');
    }

    /**
     * Migrate a single file to the cloud storage.
     */
    private function migrateFile(string $path)
    {
        if (!Storage::disk('local')->exists($path)) {
            info('File not found on local disk: ' . $path);
            return null;
        }

        $fileContent = Storage::disk('local')->get($path);
        $uploaded = Storage::disk('spaces')->put($path, $fileContent);

        if ($uploaded) {
            info('File successfully uploaded to cloud: ' . $path);
        } else {
            info('Failed to upload file to cloud: ' . $path);
        }

        return Storage::disk('spaces')->url($path);
    }
}
