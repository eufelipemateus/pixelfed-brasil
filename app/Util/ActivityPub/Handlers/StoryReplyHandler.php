<?php

namespace App\Util\ActivityPub\Handlers;

use App\Util\ActivityPub\Contracts\ActivityHandler;
use App\Util\ActivityPub\Helpers;
use App\Story;
use App\Status;
use App\DirectMessage;
use App\Models\Conversation;
use App\Notification;
use App\Services\AccountService;
use App\Services\FollowerService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Purify;

class StoryReplyHandler implements ActivityHandler
{
    public function __construct(protected array $headers, protected $profile, protected array $payload) {}

    public function handle(): void
    {

        if (
            !isset(
                $this->payload['actor'],
                $this->payload['id'],
                $this->payload['inReplyTo'],
                $this->payload['content']
            )
        ) {
            return;
        }

        $id = $this->payload['id'];
        $actor = $this->payload['actor'];
        $storyUrl = $this->payload['inReplyTo'];
        $to = $this->payload['to'];
        $text = Purify::clean($this->payload['content']);

        if (parse_url($id, PHP_URL_HOST) !== parse_url($actor, PHP_URL_HOST)) {
            return;
        }

        if (! Helpers::validateUrl($id) || ! Helpers::validateUrl($actor)) {
            return;
        }

        if (! Helpers::validateLocalUrl($storyUrl)) {
            return;
        }

        if (! Helpers::validateLocalUrl($to)) {
            return;
        }

        if (Status::whereObjectUrl($id)->exists()) {
            return;
        }

        $storyId = Str::of($storyUrl)->explode('/')->last();
        $targetProfile = Helpers::profileFetch($to);

        $story = Story::whereProfileId($targetProfile->id)
            ->find($storyId);

        if (! $story) {
            return;
        }

        if ($story->can_react == false) {
            return;
        }

        $actorProfile = Helpers::profileFetch($actor);

        if (AccountService::blocksDomain($targetProfile->id, $actorProfile->domain) == true) {
            return;
        }

        if (! FollowerService::follows($actorProfile->id, $targetProfile->id)) {
            return;
        }

        $url = $id;

        if (str_ends_with($url, '/activity')) {
            $url = substr($url, 0, -9);
        }

        $status = new Status();
        $status->profile_id = $actorProfile->id;
        $status->type = 'story:reply';
        $status->caption = $text;
        $status->url = $url;
        $status->uri = $url;
        $status->object_url = $url;
        $status->scope = 'direct';
        $status->visibility = 'direct';
        $status->in_reply_to_profile_id = $story->profile_id;
        $status->entities = json_encode([
            'story_id' => $story->id,
            'caption' => $text,
        ]);
        $status->save();

        $dm = new DirectMessage();
        $dm->to_id = $story->profile_id;
        $dm->from_id = $actorProfile->id;
        $dm->type = 'story:comment';
        $dm->status_id = $status->id;
        $dm->meta = json_encode([
            'story_username' => $targetProfile->username,
            'story_actor_username' => $actorProfile->username,
            'story_id' => $story->id,
            'story_media_url' => url(Storage::url($story->path)),
            'caption' => $text,
        ]);
        $dm->save();

        Conversation::updateOrInsert(
            [
                'to_id' => $story->profile_id,
                'from_id' => $actorProfile->id,
            ],
            [
                'type' => 'story:comment',
                'status_id' => $status->id,
                'dm_id' => $dm->id,
                'is_hidden' => false,
            ]
        );

        $n = new Notification();
        $n->profile_id = $dm->to_id;
        $n->actor_id = $dm->from_id;
        $n->item_id = $dm->id;
        $n->item_type = 'App\DirectMessage';
        $n->action = 'story:comment';
        $n->save();
    }
}
