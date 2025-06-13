<?php

namespace App\Console\Commands;

use App\Follower;
use Illuminate\Console\Command;
use App\Profile;
use App\Util\ActivityPub\Helpers;
use App\FollowRequest;

class ServiceManager extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'service:manager';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Service follanger manager';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $this->info('Service Following Manager');
        $this->info('This command allows you to manage followers and following for a service profile.');
        $this->info('You can add or remove followers, view lists of followers and following, manage pending requests, and block or unblock users.');

        $username =  $this->anticipate(
            'Enter the service profile username you want to manage (e.g., @service):',
            function ($input) {
                return Profile::where('username', 'like', '%' . $input . '%')->where('is_service', true)->pluck('username')->toArray();
            }
        );

        if (empty($username)) {
            $this->error('Username cannot be empty. Please enter a valid username.');
            return false;
        }

        $profile = Profile::where('username', $username)->where('is_service', true)->first();
        if (!$profile) {
            $this->error('Username not exists. Please type a service prfoile.');
            return;
        }


        $task = $this->choice(
            'What do you want to do?',
            [
                'Add a remote follwing',
                'View list of followers',
                'View list of following',
                'Remove follower',
                'Remove following',
                'View pending requests',
                'Accept a request',
                'Reject a request',
                'View blocked users',
                'Block a user',
                'Unblock a user',
            ],
            0
        );

        switch ($task) {
            case 'Add a remote follwing':
                $this->addFollowing($profile);
                break;
            case 'View list of followers':
                $this->info(("not implemented yet"));
                Command::INVALID;
                break;
            case 'View list of following':
                $this->info(("not implemented yet"));
                Command::INVALID;
                break;
            case 'Remove follower':
                $this->info(("not implemented yet"));
                Command::INVALID;
                break;
            case 'Remove following':
                $this->info(("not implemented yet"));
                Command::INVALID;

                break;
            case 'View pending requests':
                $this->info(("not implemented yet"));
                Command::INVALID;
                break;
            case 'Accept a request':
                $this->info(("not implemented yet"));
                Command::INVALID;
                break;
            case 'Reject a request':
                $this->info(("not implemented yet"));
                Command::INVALID;
                break;
            case 'View blocked users':

                $this->info(("not implemented yet"));
                Command::INVALID;
                break;
            case 'Block a user':
                $this->info(("not implemented yet"));
                Command::INVALID;
                break;
            case 'Unblock a user':
                $this->info(("not implemented yet"));
                Command::INVALID;
                break;
        }
    }



    function addFollowing($profile)
    {

        if (!config('federation.activitypub.remoteFollow')) {
            $this->error('Remote following is not enabled. Please enable it in the configuration.');
            return;
        }

        $remoteUrl = $this->ask('Enter the url  of the  remote user you want to follow (e.g., https://instnance/users/user):');

        if (empty($remoteUrl)) {
            $this->error('Remote URL cannot be empty. Please enter a valid URL.');
            return;
        }
        if (!filter_var($remoteUrl, FILTER_VALIDATE_URL)) {
            $this->error('Invalid URL format. Please enter a valid URL.');
            return;
        }
        if (strpos($remoteUrl, 'http') !== 0) {
            $this->error('URL must start with http:// or https://');
            return;
        }

        // Logic to add a following
        $this->info("Adding following for profile: {$profile->username} with remote url : {$remoteUrl}");
        // Implement the logic to add a following here


        if (!Helpers::validateUrl($remoteUrl)) {
            return;
        }

        $target =    Helpers::getOrFetchRemoteProfile($remoteUrl);

        if (!$target) {
            $this->error('Failed to fetch the remote profile. Please check the URL and try again.');
            return;
        }




        if (FollowRequest::where([
            'follower_id' => $profile->id,
            'following_id' => $target->id,
        ])->exists() || Follower::where([
            'profile_id' => $profile->id,
            'following_id' => $target->id,
        ])->exists()) {
            $this->error(
                'You are already following this user or a follow request is pending.'
            );
            return;
        }

        FollowRequest::firstOrCreate(
            [
                'follower_id' => $profile->id,
                'following_id' => $target->id,
            ]
        );

        $payload = [
            '@context'  => 'https://www.w3.org/ns/activitystreams',
            'id'        => $profile->permalink('#follow/' . $target->id),
            'type'      => 'Follow',
            'actor'     => $profile->permalink(),
            'object'    => $target->permalink()
        ];

        $inbox = $target->sharedInbox ?? $target->inbox_url;

        Helpers::sendSignedObject($profile, $inbox, $payload);
    }
}
