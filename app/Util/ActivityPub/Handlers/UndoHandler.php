<?php

namespace App\Util\ActivityPub\Handlers;

use App\Util\ActivityPub\Contracts\ActivityHandler;
use App\FollowRequest;
use App\Services\RelationshipService;
use App\Util\ActivityPub\Helpers;
use App\Status;
use App\Notification;
use App\Services\AccountService;
use App\Services\FollowerService;
use App\Jobs\HomeFeedPipeline\FeedRemoveRemotePipeline;
use App\Follower;
use App\Like;
use App\Services\ReblogService;

class UndoHandler implements ActivityHandler
{

    public function __construct(protected array $headers, protected $profile, protected array $payload) {}

    public function handle(): void
    {

        $actor = $this->payload['actor'];
        $profile = Helpers::profileFetch($actor);
        $obj = $this->payload['object'];

        if (! $profile) {
            return;
        }
        // TODO: Some implementations do not inline the object, skip for now
        if (! $obj || ! is_array($obj) || ! isset($obj['type'])) {
            return;
        }

        switch ($obj['type']) {
            case 'Accept':
                break;

            case 'Announce':
                if (is_array($obj) && isset($obj['object'])) {
                    $obj = $obj['object'];
                }
                if (! is_string($obj)) {
                    return;
                }
                if (Helpers::validateLocalUrl($obj)) {
                    $parsedId = last(explode('/', $obj));
                    $status = Status::find($parsedId);
                } else {
                    $status = Status::whereUri($obj)->first();
                }
                if (! $status) {
                    return;
                }
                if (AccountService::blocksDomain($status->profile_id, $profile->domain) == true) {
                    return;
                }
                FeedRemoveRemotePipeline::dispatch($status->id, $status->profile_id)->onQueue('feed');
                Status::whereProfileId($profile->id)
                    ->whereReblogOfId($status->id)
                    ->delete();
                ReblogService::removePostReblog($profile->id, $status->id);
                Notification::whereProfileId($status->profile_id)
                    ->whereActorId($profile->id)
                    ->whereAction('share')
                    ->whereItemId($status->reblog_of_id)
                    ->whereItemType('App\Status')
                    ->forceDelete();
                break;

            case 'Block':
                break;

            case 'Follow':
                $following = Helpers::profileFetch($obj['object']);
                if (! $following) {
                    return;
                }
                if (AccountService::blocksDomain($following->id, $profile->domain) == true) {
                    return;
                }
                Follower::whereProfileId($profile->id)
                    ->whereFollowingId($following->id)
                    ->delete();
                FollowRequest::whereFollowingId($following->id)
                    ->whereFollowerId($profile->id)
                    ->forceDelete();
                Notification::whereProfileId($following->id)
                    ->whereActorId($profile->id)
                    ->whereAction('follow')
                    ->whereItemId($following->id)
                    ->whereItemType('App\Profile')
                    ->forceDelete();
                FollowerService::remove($profile->id, $following->id);
                RelationshipService::refresh($following->id, $profile->id);
                AccountService::del($profile->id);
                AccountService::del($following->id);
                break;

            case 'Like':
                $objectUri = $obj['object'];
                if (! is_string($objectUri)) {
                    if (is_array($objectUri) && isset($objectUri['id']) && is_string($objectUri['id'])) {
                        $objectUri = $objectUri['id'];
                    } else {
                        return;
                    }
                }
                $status = Helpers::statusFirstOrFetch($objectUri);
                if (! $status) {
                    return;
                }
                if (AccountService::blocksDomain($status->profile_id, $profile->domain) == true) {
                    return;
                }
                Like::whereProfileId($profile->id)
                    ->whereStatusId($status->id)
                    ->forceDelete();
                Notification::whereProfileId($status->profile_id)
                    ->whereActorId($profile->id)
                    ->whereAction('like')
                    ->whereItemId($status->id)
                    ->whereItemType('App\Status')
                    ->forceDelete();
                break;
        }
    }
}
