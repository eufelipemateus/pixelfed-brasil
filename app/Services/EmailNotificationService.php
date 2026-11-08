<?php

namespace App\Services;

use App\Models\DmMessage;
use App\Models\Notification;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Notifications\CommentNotification;
use App\Notifications\DirectMessageNotification;
use App\Notifications\FollowNotification;
use App\Notifications\LikeNotification;
use App\Notifications\MentionNotification;
use App\Notifications\ShareNotification;
use Illuminate\Support\Facades\Log;

class EmailNotificationService
{
    /** Notification action => user_settings column that opts the recipient in. */
    const SETTINGS = [
        'like' => 'send_email_on_like',
        'comment' => 'send_email_on_comment',
        'follow' => 'send_email_new_follower',
        'share' => 'send_email_on_share',
        'mention' => 'send_email_on_mention',
        'dm' => 'send_email_on_dm',
    ];

    public static function send(Notification $notification): void
    {
        $setting = self::SETTINGS[$notification->action] ?? null;

        if (! $setting || (int) $notification->profile_id === (int) $notification->actor_id) {
            return;
        }

        try {
            $settings = AccountService::getAccountSettings($notification->profile_id);

            if (empty($settings[$setting])) {
                return;
            }

            $user = User::whereProfileId($notification->profile_id)->whereNull('status')->first();

            if (! $user || ! $user->email || ! Profile::whereKey($notification->actor_id)->exists()) {
                return;
            }

            $mail = self::build($notification);

            if ($mail) {
                $user->notify($mail);
            }
        } catch (\Throwable $e) {
            Log::warning('EmailNotificationService: failed to send email notification', [
                'notification_id' => $notification->id,
                'action' => $notification->action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected static function build(Notification $notification)
    {
        $actorId = $notification->actor_id;
        $itemId = $notification->item_id;
        $isStatus = in_array($notification->item_type, ['App\Status', Status::class], true);

        if ($isStatus && ! Status::whereKey($itemId)->exists()) {
            return null;
        }

        return match ($notification->action) {
            'like' => $isStatus ? new LikeNotification($actorId, $itemId) : null,
            'comment' => $isStatus ? new CommentNotification($actorId, $itemId) : null,
            'share' => $isStatus ? new ShareNotification($actorId, $itemId) : null,
            'mention' => $isStatus ? new MentionNotification($actorId, $itemId) : null,
            'follow' => new FollowNotification($actorId),
            'dm' => $notification->item_type === DmMessage::class ? new DirectMessageNotification($actorId) : null,
            default => null,
        };
    }
}
