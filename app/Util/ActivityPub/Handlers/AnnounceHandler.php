<?php

namespace App\Util\ActivityPub\Handlers;

use App\Util\ActivityPub\Contracts\ActivityHandler;
use App\Util\ActivityPub\Validator\Announce as AnnounceValidator;
use App\Status;
use App\Notification;
use App\Services\AccountService;
use App\Services\ReblogService;
use App\Services\UserFilterService;
use App\Util\ActivityPub\Helpers;

class AnnounceHandler implements ActivityHandler
{

    public function __construct(protected array $headers, protected $profile, protected array $payload) {}

    public function handle(): void
    {
        if (AnnounceValidator::validate($this->payload) == false) {
            return;
        }


        $actor = Helpers::profileFetch($this->payload['actor']);
        $activity = $this->payload['object'];

        if (! $actor || $actor->domain == null) {
            return;
        }

        $parent = Helpers::statusFetch($activity);

        if (! $parent || empty($parent)) {
            return;
        }

        if (AccountService::blocksDomain($parent->profile_id, $actor->domain) == true) {
            return;
        }

        $blocks = UserFilterService::blocks($parent->profile_id);
        if ($blocks && in_array($actor->id, $blocks)) {
            return;
        }

        $status = Status::firstOrCreate([
            'profile_id' => $actor->id,
            'reblog_of_id' => $parent->id,
            'type' => 'share',
            'local' => false,
        ]);

        Notification::firstOrCreate(
            [
                'profile_id' => $parent->profile_id,
                'actor_id' => $actor->id,
                'action' => 'share',
                'item_id' => $parent->id,
                'item_type' => 'App\Status',
            ]
        );

        $parent->reblogs_count = $parent->reblogs_count + 1;
        $parent->save();

        ReblogService::addPostReblog($parent->profile_id, $status->id);
    }
}
