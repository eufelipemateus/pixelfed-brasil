<?php

namespace App\Util\ActivityPub\Handlers;

use App\Util\ActivityPub\Contracts\ActivityHandler;
use App\Util\ActivityPub\Validator\Like as LikeValidator;
use App\Like;
use App\Services\AccountService;
use App\Services\UserFilterService;
use App\Util\ActivityPub\Helpers;
use App\Jobs\LikePipeline\LikePipeline;

class LikeHandler implements ActivityHandler
{

    public function __construct(protected array $headers, protected $profile, protected array $payload) {}

    public function handle(): void
    {

        if (LikeValidator::validate($this->payload) == false) {
            return;
        }

        $actor = $this->payload['actor'];

        if (! Helpers::validateUrl($actor)) {
            return;
        }

        $profile = Helpers::profileFetch($actor);
        $obj = $this->payload['object'];
        if (! Helpers::validateUrl($obj)) {
            return;
        }
        $status = Helpers::statusFirstOrFetch($obj);
        if (! $status || ! $profile) {
            return;
        }

        if (AccountService::blocksDomain($status->profile_id, $profile->domain) == true) {
            return;
        }

        $blocks = UserFilterService::blocks($status->profile_id);
        if ($blocks && in_array($profile->id, $blocks)) {
            return;
        }

        $like = Like::firstOrCreate([
            'profile_id' => $profile->id,
            'status_id' => $status->id,
        ]);

        if ($like->wasRecentlyCreated == true) {
            $status->likes_count = $status->likes_count + 1;
            $status->save();
            LikePipeline::dispatch($like);
        }
    }
}
