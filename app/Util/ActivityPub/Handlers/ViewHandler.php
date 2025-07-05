<?php

namespace App\Util\ActivityPub\Handlers;

use App\Util\ActivityPub\Contracts\ActivityHandler;
use App\Util\ActivityPub\Helpers;
use App\Story;
use App\StoryView;
use App\Services\AccountService;
use App\Services\FollowerService;
use Illuminate\Support\Str;

class ViewHandler implements ActivityHandler
{
    public function __construct(protected array $headers, protected $profile, protected array $payload) {}

    public function handle(): void
    {

        if (
            !isset(
                $this->payload['actor'],
                $this->payload['object']
            )
        ) {
            return;
        }

        $actor = $this->payload['actor'];
        $obj = $this->payload['object'];

        if (! Helpers::validateUrl($actor)) {
            return;
        }

        if (! $obj || ! is_array($obj)) {
            return;
        }

        if (! isset($obj['type']) || ! isset($obj['object']) || $obj['type'] != 'Story') {
            return;
        }

        if (! Helpers::validateLocalUrl($obj['object'])) {
            return;
        }

        $profile = Helpers::profileFetch($actor);
        $storyId = Str::of($obj['object'])->explode('/')->last();

        $story = Story::whereActive(true)
            ->whereLocal(true)
            ->find($storyId);

        if (! $story) {
            return;
        }

        if (AccountService::blocksDomain($story->profile_id, $profile->domain) == true) {
            return;
        }

        if (! FollowerService::follows($profile->id, $story->profile_id)) {
            return;
        }

        $view = StoryView::firstOrCreate([
            'story_id' => $story->id,
            'profile_id' => $profile->id,
        ]);

        if ($view->wasRecentlyCreated == true) {
            $story->view_count++;
            $story->save();
        }
    }
}
