<?php

use App\Models\DmMessage;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Models\UserSetting;
use App\Notifications\CommentNotification;
use App\Notifications\DirectMessageNotification;
use App\Notifications\FollowNotification;
use App\Notifications\LikeNotification;
use App\Services\AccountService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Redis::spy();
    Notification::fake();
});

function emailRecipient(array $settings = []): User
{
    $user = User::factory()->create();
    $user->refresh();
    UserSetting::updateOrCreate(['user_id' => $user->id], $settings);
    AccountService::forgetAccountSettings($user->profile_id);

    return $user;
}

function emailActor(): User
{
    $actor = User::factory()->create();
    $actor->refresh();

    return $actor;
}

function emailStatus(User $owner): Status
{
    return Status::factory()->create([
        'profile_id' => $owner->profile_id,
        'type' => 'photo',
        'local' => true,
        'url' => null,
    ]);
}

it('emails the author when a post is liked', function () {
    $user = emailRecipient(['send_email_on_like' => true]);
    $status = emailStatus($user);

    NotificationService::firstOrCreateNotification($user->profile_id, emailActor()->profile_id, 'like', $status->id, Status::class);

    Notification::assertSentTo($user, LikeNotification::class);
});

it('emails the author when a post is commented', function () {
    $user = emailRecipient(['send_email_on_comment' => true]);
    $actor = emailActor();
    $comment = emailStatus($actor);

    NotificationService::createNotification($user->profile_id, $actor->profile_id, 'comment', $comment->id, Status::class);

    Notification::assertSentTo($user, CommentNotification::class);
});

it('emails the user on a new follower', function () {
    $user = emailRecipient(['send_email_new_follower' => true]);

    NotificationService::createNotification($user->profile_id, emailActor()->profile_id, 'follow', $user->profile_id, Profile::class);

    Notification::assertSentTo($user, FollowNotification::class);
});

it('emails the user on a new direct message', function () {
    $user = emailRecipient(['send_email_on_dm' => true]);

    NotificationService::createNotification($user->profile_id, emailActor()->profile_id, 'dm', 1, DmMessage::class);

    Notification::assertSentTo($user, DirectMessageNotification::class);
});

it('respects disabled email settings', function () {
    $user = emailRecipient([
        'send_email_on_like' => false,
        'send_email_on_dm' => false,
    ]);
    $status = emailStatus($user);
    $actor = emailActor();

    NotificationService::createNotification($user->profile_id, $actor->profile_id, 'like', $status->id, Status::class);
    NotificationService::createNotification($user->profile_id, $actor->profile_id, 'dm', 1, DmMessage::class);

    Notification::assertNothingSentTo($user);
});
