<?php

namespace App\Util\ActivityPub\Handlers;

use App\Util\ActivityPub\Contracts\ActivityHandler;
use App\Util\ActivityPub\Validator\Follow as FollowValidator;
use App\Follower;
use App\FollowRequest;
use App\Services\AccountService;
use App\Services\FollowerService;
use App\Services\RelationshipService;
use App\Services\UserFilterService;
use App\Jobs\FollowPipeline\FollowPipeline;
use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Facades\Cache;

class FollowHandler implements ActivityHandler
{

    public function __construct(protected array $headers, protected $profile, protected array $payload) {}

    public function handle(): void
    {
        if (FollowValidator::validate($this->payload) == false) {
            return;
        }

        $actor = Helpers::profileFetch($this->payload['actor']);
        $target = Helpers::profileFetch($this->payload['object']);
        if (! $actor || ! $target) {
            return;
        }

        if ($actor->domain == null || $target->domain !== null) {
            return;
        }

        if (AccountService::blocksDomain($target->id, $actor->domain) == true) {
            return;
        }

        if (
            Follower::whereProfileId($actor->id)
            ->whereFollowingId($target->id)
            ->exists() ||
            FollowRequest::whereFollowerId($actor->id)
            ->whereFollowingId($target->id)
            ->exists()
        ) {
            return;
        }

        $blocks = UserFilterService::blocks($target->id);
        if ($blocks && in_array($actor->id, $blocks)) {
            return;
        }

        if ($target->is_private == true) {
            FollowRequest::updateOrCreate([
                'follower_id' => $actor->id,
                'following_id' => $target->id,
            ], [
                'activity' => collect($this->payload)->only(['id', 'actor', 'object', 'type'])->toArray(),
            ]);
        } else {
            $follower = new Follower();
            $follower->profile_id = $actor->id;
            $follower->following_id = $target->id;
            $follower->local_profile = empty($actor->domain);
            $follower->save();

            FollowPipeline::dispatch($follower);
            FollowerService::add($actor->id, $target->id);

            // send Accept to remote profile
            $accept = [
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'id' => $target->permalink() . '#accepts/follows/' . $follower->id,
                'type' => 'Accept',
                'actor' => $target->permalink(),
                'object' => [
                    'id' => $this->payload['id'],
                    'actor' => $actor->permalink(),
                    'type' => 'Follow',
                    'object' => $target->permalink(),
                ],
            ];
            Helpers::sendSignedObject($target, $actor->inbox_url, $accept);
            Cache::forget('profile:follower_count:' . $target->id);
            Cache::forget('profile:follower_count:' . $actor->id);
            Cache::forget('profile:following_count:' . $target->id);
            Cache::forget('profile:following_count:' . $actor->id);
        }
        RelationshipService::refresh($actor->id, $target->id);
        AccountService::del($actor->id);
        AccountService::del($target->id);
    }
}
