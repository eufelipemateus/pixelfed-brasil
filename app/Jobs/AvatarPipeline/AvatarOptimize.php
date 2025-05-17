<?php

namespace App\Jobs\AvatarPipeline;

use App\Avatar;
use App\Profile;
use Cache;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Encoders\AvifEncoder;
use Intervention\Image\Encoders\PngEncoder;

class AvatarOptimize implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $profile;

    protected $current;

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
    public function __construct(Profile $profile, $current)
    {
        $this->profile = $profile;
        $this->current = $current;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $avatar = $this->profile->avatar;
        $file = storage_path("app/$avatar->media_path");
        $fileInfo = pathinfo($file);
        $extension = strtolower($fileInfo['extension'] ?? 'jpg');

        $driver = match(config('image.driver')) {
            'imagick' => \Intervention\Image\Drivers\Imagick\Driver::class,
            'vips' => \Intervention\Image\Drivers\Vips\Driver::class,
            default => \Intervention\Image\Drivers\Gd\Driver::class
        };

        $imageManager = new ImageManager(
            $driver,
            autoOrientation: true,
            decodeAnimation: true,
            blendingColor: 'ffffff',
            strip: true
        );

        $quality = config_cache('pixelfed.image_quality');

        $encoder = null;
        switch ($extension) {
            case 'jpeg':
            case 'jpg':
                $encoder = new JpegEncoder($quality);
                break;
            case 'png':
                $encoder = new PngEncoder();
                break;
            case 'webp':
                $encoder = new WebpEncoder($quality);
                break;
            case 'avif':
                $encoder = new AvifEncoder($quality);
                break;
            case 'heic':
                $encoder = new JpegEncoder($quality);
                $extension = 'jpg';
                break;
            default:
                $encoder = new JpegEncoder($quality);
                $extension = 'jpg';
        }

        if ((bool) config_cache('pixelfed.cloud_storage')) {
            $file = Storage::disk(config('filesystems.cloud'))->url($avatar->media_path);
        }

        try {
            $img = Intervention::make($file)->orientate();
            $img->fit(200, 200, function ($constraint) {
                $constraint->upsize();
            });
            $quality = config_cache('pixelfed.image_quality');
            if ((bool) config_cache('pixelfed.cloud_storage')) {
                $tempFile = tempnam(sys_get_temp_dir(), 'avatar');
                $img->save($tempFile, $quality);
                Storage::disk(config('filesystems.cloud'))->put($avatar->media_path, file_get_contents($tempFile));
                unlink($tempFile);
            } else {
                $img->save($file, $quality);
            }

            $avatar = Avatar::whereProfileId($this->profile->id)->firstOrFail();
            $avatar->change_count = ++$avatar->change_count;
            $avatar->last_processed_at = Carbon::now();
            $avatar->save();
            Cache::forget('avatar:'.$avatar->profile_id);
            $this->deleteOldAvatar($avatar->media_path, $this->current);

            $avatar->cdn_url = Storage::disk(config('filesystems.cloud'))->url($avatar->media_path);
            $avatar->save();
        } catch (Exception $e) {
        }
    }

    protected function deleteOldAvatar($new, $current)
    {
        if (storage_path('app/'.$new) == $current ||
             Str::endsWith($current, 'avatars/default.png') ||
             Str::endsWith($current, 'avatars/default.jpg')) {
            return;
        }
        if (is_file($current)) {
            if ((bool) config_cache('pixelfed.cloud_storage')) {
                Storage::disk(config('filesystems.cloud'))->delete($current);
            } else {
                @unlink($current);
            }
        }
    }

    protected function uploadToCloud($avatar)
    {
        $base = 'cache/avatars/'.$avatar->profile_id;
        $disk = Storage::disk(config('filesystems.cloud'));
        $disk->deleteDirectory($base);
        $path = $base.'/'.'avatar_'.strtolower(Str::random(random_int(3, 6))).$avatar->change_count.'.'.pathinfo($avatar->media_path, PATHINFO_EXTENSION);
        $url = $disk->put($path, Storage::get($avatar->media_path));
        $avatar->media_path = $path;
        $avatar->cdn_url = $disk->url($path);
        $avatar->save();
        Storage::delete($avatar->media_path);
        Cache::forget('avatar:'.$avatar->profile_id);
    }
}
