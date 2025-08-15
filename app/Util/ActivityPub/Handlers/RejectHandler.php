<?php

namespace App\Util\ActivityPub\Handlers;

use App\Util\ActivityPub\Contracts\ActivityHandler;
use App\Util\ActivityPub\Validator\RejectValidator;
use App\FollowRequest;
use App\Services\RelationshipService;
use App\Util\ActivityPub\Helpers;

class RejectHandler implements ActivityHandler
{

    public function __construct(protected array $headers, protected $profile, protected array $payload) {}

    public function handle(): void
    {
        if (RejectValidator::validate($this->payload) == false) {
            return;
        }


        $actorUrl = $this->payload['actor'];
        $obj = $this->payload['object'];
        $profileUrl = $obj['actor'];
        if (! Helpers::validateUrl($actorUrl) || ! Helpers::validateLocalUrl($profileUrl)) {
            return;
        }
        $actor = Helpers::profileFetch($actorUrl);
        $profile = Helpers::profileFetch($profileUrl);

        FollowRequest::whereFollowerId($profile->id)->whereFollowingId($actor->id)->forceDelete();
        RelationshipService::refresh($actor->id, $profile->id);
    }
}
