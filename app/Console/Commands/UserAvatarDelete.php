<?php

namespace App\Console\Commands;

use App\Avatar;
use App\Services\AccountService;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class UserAvatarDelete extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:avatar-delete {username}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete user avatar';

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array
     */
    protected function promptForMissingArgumentsUsing()
    {
        return [
            'username' => 'Which username should we delete the avatar for?',
        ];
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::whereUsername($this->argument('username'))->first();

        if (! $user) {
            $this->error('Could not find any user with that username');
            return Command::FAILURE;
        }

        if (! $user->profile_id) {
            $this->error('Could not find the profile with that username');
            return Command::FAILURE;
        }

        $pid = $user->profile_id;

        $avatarModel = Avatar::where('profile_id', $pid)->first();

        if (! $avatarModel) {
            $this->error('No avatar model found');
            Cache::forget('avatar:'.$pid);
            return Command::FAILURE;
        }

        $defaultPaths = ['public/avatars/default.jpg', 'public/avatars/default.png'];
        $mediaPath = $avatarModel->media_path;

        if (in_array($mediaPath, $defaultPaths, true)) {
            $this->info('Default avatar already used, aborting...');
            Cache::forget('avatar:'.$pid);
            return Command::SUCCESS;
        }

        if (! $this->isAllowedAvatarPath($mediaPath)) {
            $this->error('Refusing to delete an avatar outside the allowed storage prefix.');

            return Command::FAILURE;
        }

        $disks = array_values(array_unique(array_filter([
            config('filesystems.cloud'),
            'local',
        ])));
        $existing = collect($disks)
            ->filter(fn (string $disk): bool => Storage::disk($disk)->exists($mediaPath));

        if ($existing->isNotEmpty() && ! $this->confirm('Delete all stored copies of '.$mediaPath.'?', false)) {
            return Command::SUCCESS;
        }

        $backups = [];
        foreach ($existing as $disk) {
            $backups[$disk] = Storage::disk($disk)->get($mediaPath);
        }

        $deleted = [];
        foreach ($existing as $disk) {
            if (! Storage::disk($disk)->delete($mediaPath)) {
                foreach ($deleted as $deletedDisk) {
                    Storage::disk($deletedDisk)->put($mediaPath, $backups[$deletedDisk]);
                }
                $this->error('Avatar deletion failed; previously removed copies were restored.');

                return Command::FAILURE;
            }
            $deleted[] = $disk;
        }

        $avatarModel->media_path = 'public/avatars/default.jpg';
        $avatarModel->cdn_url = null;
        $avatarModel->save();
        Cache::forget('avatar:'.$pid);
        AccountService::del($pid);

        $this->info('Successfully deleted user avatar!');

        return Command::SUCCESS;
    }

    private function isAllowedAvatarPath(?string $path): bool
    {
        return is_string($path)
            && str_starts_with($path, 'public/avatars/')
            && ! str_contains($path, '..')
            && ! str_contains($path, '\\')
            && ! str_contains($path, "\0");
    }
}
