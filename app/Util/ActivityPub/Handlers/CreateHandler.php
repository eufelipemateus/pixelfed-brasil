<?php

namespace App\Util\ActivityPub\Handlers;

use App\Models\Conversation;
use App\DirectMessage;
use App\Jobs\PushNotificationPipeline\MentionPushNotifyPipeline;
use App\Media;
use App\Notification;
use App\Services\FollowerService;
use App\Services\NotificationAppGatewayService;
use App\Services\PollService;
use App\Services\PushNotificationService;
use App\Status;
use App\User;
use App\UserFilter;
use App\Util\ActivityPub\Contracts\ActivityHandler;
use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Str;
use Purify;
use App\Profile;
use App\Services\AccountService;
use App\Models\PollVote;

class CreateHandler implements ActivityHandler
{
    public function __construct(protected array $headers, protected $profile, protected array $payload) {}

    public function handle(): void
    {
        $activity = $this->payload['object'];
        if (config('autospam.live_filters.enabled')) {
            $filters = config('autospam.live_filters.filters');
            if (! empty($filters) && isset($activity['content']) && ! empty($activity['content']) && strlen($filters) > 3) {
                $filters = array_map('trim', explode(',', $filters));
                $content = $activity['content'];
                foreach ($filters as $filter) {
                    $filter = trim(strtolower($filter));
                    if (! $filter || ! strlen($filter)) {
                        continue;
                    }
                    if (str_contains(strtolower($content), $filter)) {
                        return;
                    }
                }
            }
        }
        $actor = Helpers::profileFetch($this->payload['actor']);
        if (! $actor || $actor->domain == null) {
            return;
        }

        if (! isset($activity['to'])) {
            return;
        }
        $to = isset($activity['to']) ? $activity['to'] : [];
        $cc = isset($activity['cc']) ? $activity['cc'] : [];

        if ($activity['type'] == 'Question') {
            // $this->handlePollCreate();

            return;
        }

        if (
            is_array($to) &&
            is_array($cc) &&
            count($to) == 1 &&
            count($cc) == 0 &&
            parse_url($to[0], PHP_URL_HOST) == config('pixelfed.domain.app')
        ) {
            $this->handleDirectMessage();

            return;
        }

        if ($activity['type'] == 'Note' && ! empty($activity['inReplyTo'])) {
            $this->handleNoteReply();
        } elseif ($activity['type'] == 'Note' && ! empty($activity['attachment'])) {
            if (! $this->verifyNoteAttachment()) {
                return;
            }
            $this->handleNoteCreate();
        }
    }


    public function handleNoteCreate()
    {
        $activity = $this->payload['object'];
        $actor = Helpers::profileFetch($this->payload['actor']);
        if (! $actor || $actor->domain == null) {
            return;
        }

        if (
            isset($activity['inReplyTo']) &&
            isset($activity['name']) &&
            ! isset($activity['content']) &&
            ! isset($activity['attachment']) &&
            Helpers::validateLocalUrl($activity['inReplyTo'])
        ) {
            $this->handlePollVote();

            return;
        }

        if ($actor->followers_count == 0) {
            if (config('federation.activitypub.ingest.store_notes_without_followers')) {
            } elseif (FollowerService::followerCount($actor->id, true) == 0) {
                return;
            }
        }

        $hasUrl = isset($activity['url']);
        $url = isset($activity['url']) ? $activity['url'] : $activity['id'];

        if ($hasUrl) {
            if (Status::whereUri($url)->exists()) {
                return;
            }
        } else {
            if (Status::whereObjectUrl($url)->exists()) {
                return;
            }
        }

        Helpers::storeStatus(
            $url,
            $actor,
            $activity
        );
    }


    public function handleNoteReply()
    {
        $activity = $this->payload['object'];
        $actor = Helpers::profileFetch($this->payload['actor']);
        if (! $actor || $actor->domain == null) {
            return;
        }

        $inReplyTo = $activity['inReplyTo'];
        $url = isset($activity['url']) ? $activity['url'] : $activity['id'];

        Helpers::statusFirstOrFetch($url, true);
    }


    public function handleDirectMessage()
    {
        $activity = $this->payload['object'];
        $actor = Helpers::profileFetch($this->payload['actor']);
        $profile = Profile::whereNull('domain')
            ->whereUsername(array_last(explode('/', $activity['to'][0])))
            ->firstOrFail();

        if (! $actor || in_array($actor->id, $profile->blockedIds()->toArray())) {
            return;
        }

        if (AccountService::blocksDomain($profile->id, $actor->domain) == true) {
            return;
        }

        $msg = Purify::clean($activity['content']);
        $msgText = strip_tags($msg);

        if (Str::startsWith($msgText, '@' . $profile->username)) {
            $len = strlen('@' . $profile->username);
            $msgText = substr($msgText, $len + 1);
        }

        if ($profile->user->settings->public_dm == false || $profile->is_private) {
            if ($profile->follows($actor) == true) {
                $hidden = false;
            } else {
                $hidden = true;
            }
        } else {
            $hidden = false;
        }

        $status = new Status();
        $status->profile_id = $actor->id;
        $status->caption = $msgText;
        $status->visibility = 'direct';
        $status->scope = 'direct';
        $status->url = $activity['id'];
        $status->uri = $activity['id'];
        $status->object_url = $activity['id'];
        $status->in_reply_to_profile_id = $profile->id;
        $status->save();

        $dm = new DirectMessage();
        $dm->to_id = $profile->id;
        $dm->from_id = $actor->id;
        $dm->status_id = $status->id;
        $dm->is_hidden = $hidden;
        $dm->type = 'text';
        $dm->save();

        Conversation::updateOrInsert(
            [
                'to_id' => $profile->id,
                'from_id' => $actor->id,
            ],
            [
                'type' => 'text',
                'status_id' => $status->id,
                'dm_id' => $dm->id,
                'is_hidden' => $hidden,
            ]
        );

        if (count($activity['attachment'])) {
            $photos = 0;
            $videos = 0;
            $allowed = explode(',', config_cache('pixelfed.media_types'));
            $activity['attachment'] = array_slice($activity['attachment'], 0, config_cache('pixelfed.max_album_length'));
            foreach ($activity['attachment'] as $a) {
                $type = $a['mediaType'];
                $url = $a['url'];
                $valid = Helpers::validateUrl($url);
                if (in_array($type, $allowed) == false || $valid == false) {
                    continue;
                }

                $media = new Media();
                $media->remote_media = true;
                $media->status_id = $status->id;
                $media->profile_id = $status->profile_id;
                $media->user_id = null;
                $media->media_path = $url;
                $media->remote_url = $url;
                $media->mime = $type;
                $media->save();
                if (explode('/', $type)[0] == 'image') {
                    $photos = $photos + 1;
                }
                if (explode('/', $type)[0] == 'video') {
                    $videos = $videos + 1;
                }
            }

            if ($photos && $videos == 0) {
                $dm->type = $photos == 1 ? 'photo' : 'photos';
                $dm->save();
            }
            if ($videos && $photos == 0) {
                $dm->type = $videos == 1 ? 'video' : 'videos';
                $dm->save();
            }
        }

        if (filter_var($msgText, FILTER_VALIDATE_URL)) {
            if (Helpers::validateUrl($msgText)) {
                $dm->type = 'link';
                $dm->meta = [
                    'domain' => parse_url($msgText, PHP_URL_HOST),
                    'local' => parse_url($msgText, PHP_URL_HOST) ==
                        parse_url(config('app.url'), PHP_URL_HOST),
                ];
                $dm->save();
            }
        }

        $nf = UserFilter::whereUserId($profile->id)
            ->whereFilterableId($actor->id)
            ->whereFilterableType('App\Profile')
            ->whereFilterType('dm.mute')
            ->exists();

        if ($profile->domain == null && $hidden == false && ! $nf) {
            $notification = new Notification();
            $notification->profile_id = $profile->id;
            $notification->actor_id = $actor->id;
            $notification->action = 'dm';
            $notification->item_id = $dm->id;
            $notification->item_type = "App\DirectMessage";
            $notification->save();

            if (NotificationAppGatewayService::enabled()) {
                if (PushNotificationService::check('mention', $profile->id)) {
                    $user = User::whereProfileId($profile->id)->first();
                    if ($user && $user->expo_token && $user->notify_enabled) {
                        MentionPushNotifyPipeline::dispatch($user->expo_token, $actor->username)->onQueue('pushnotify');
                    }
                }
            }
        }
    }



    public function handlePollVote()
    {
        $activity = $this->payload['object'];
        $actor = Helpers::profileFetch($this->payload['actor']);

        if (! $actor) {
            return;
        }

        $status = Helpers::statusFetch($activity['inReplyTo']);

        if (! $status) {
            return;
        }

        $poll = $status->poll;

        if (! $poll) {
            return;
        }

        if (now()->gt($poll->expires_at)) {
            return;
        }

        $choices = $poll->poll_options;
        $choice = array_search($activity['name'], $choices);

        if ($choice === false) {
            return;
        }

        if (PollVote::whereStatusId($status->id)->whereProfileId($actor->id)->exists()) {
            return;
        }

        $vote = new PollVote();
        $vote->status_id = $status->id;
        $vote->profile_id = $actor->id;
        $vote->poll_id = $poll->id;
        $vote->choice = $choice;
        $vote->uri = isset($activity['id']) ? $activity['id'] : null;
        $vote->save();

        $tallies = $poll->cached_tallies;
        $tallies[$choice] = $tallies[$choice] + 1;
        $poll->cached_tallies = $tallies;
        $poll->votes_count = array_sum($tallies);
        $poll->save();

        PollService::del($status->id);
    }


    public function verifyNoteAttachment()
    {
        $activity = $this->payload['object'];

        if (
            isset($activity['inReplyTo']) &&
            ! empty($activity['inReplyTo']) &&
            Helpers::validateUrl($activity['inReplyTo'])
        ) {
            // reply detected, skip attachment check
            return true;
        }

        $valid = Helpers::verifyAttachments($activity);

        return $valid;
    }
}
