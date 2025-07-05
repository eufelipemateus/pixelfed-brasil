<?php

namespace App\Util\ActivityPub\Handlers;

use App\Util\ActivityPub\Contracts\ActivityHandler;
use App\Util\ActivityPub\Validator\Accept as AcceptValidator;
use App\Follower;
use App\FollowRequest;
use App\Services\AccountService;
use App\Services\RelationshipService;
use App\Jobs\FollowPipeline\FollowPipeline;
use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Facades\Cache;

class AcceptHandler implements ActivityHandler
{
    public function __construct(protected array $headers, protected $profile, protected array $payload) {}

    public function handle(): void
    {
        if (AcceptValidator::validate($this->payload) == false) {
            return;
        }

        $actor = $this->payload['object']['actor'];
        $obj = $this->payload['object']['object'];
        $type = $this->payload['object']['type'];

        if ($type !== 'Follow') {
            return;
        }

        $actor = Helpers::validateLocalUrl($actor);
        $target = Helpers::validateUrl($obj);

        if (! $actor || ! $target) {
            return;
        }

        $actor = Helpers::profileFetch($actor);
        $target = Helpers::profileFetch($target);

        if (! $actor || ! $target) {
            return;
        }

        if (AccountService::blocksDomain($target->id, $actor->domain) == true) {
            return;
        }

        $request = FollowRequest::whereFollowerId($actor->id)
            ->whereFollowingId($target->id)
            ->whereIsRejected(false)
            ->first();

        if (! $request) {
            return;
        }

        $follower = Follower::firstOrCreate([
            'profile_id' => $actor->id,
            'following_id' => $target->id,
        ]);
        FollowPipeline::dispatch($follower)->onQueue('high');
        RelationshipService::refresh($actor->id, $target->id);
        Cache::forget('profile:following:' . $target->id);
        Cache::forget('profile:followers:' . $target->id);
        Cache::forget('profile:following:' . $actor->id);
        Cache::forget('profile:followers:' . $actor->id);
        Cache::forget('profile:follower_count:' . $target->id);
        Cache::forget('profile:follower_count:' . $actor->id);
        Cache::forget('profile:following_count:' . $target->id);
        Cache::forget('profile:following_count:' . $actor->id);
        AccountService::del($actor->id);
        AccountService::del($target->id);
        RelationshipService::get($actor->id, $target->id);
        $request->delete();
    }
}
