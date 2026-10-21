<?php

use App\Jobs\Federation\DeliverActivityChunk;
use App\Jobs\StatusPipeline\StatusActivityPubDeliver;
use App\Models\Follower;
use App\Models\Instance;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Services\InstanceService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    Cache::forget(InstanceService::CACHE_KEY_SHARED_INBOXES_PUBLIC);
    Cache::forget(InstanceService::CACHE_KEY_BANNED_DOMAINS);

    config([
        'federation.activitypub.delivery.allow_share_all' => true,
        'federation.activitypub.delivery.chunk_size' => 50,
    ]);
});

function createPrivilegedFanoutStatus(array $user = [], array $profile = [], array $status = []): Status
{
    $owner = User::factory()->create($user);
    $owner->refresh();

    if ($profile !== []) {
        $owner->profile->forceFill($profile)->save();
    }

    return Status::factory()->create(array_merge([
        'profile_id' => $owner->profile->id,
        'scope' => 'public',
        'visibility' => 'public',
    ], $status))->refresh();
}

function addKnownFanoutInstance(string $domain, bool $banned = false): string
{
    $inbox = "https://{$domain}/inbox";

    Instance::create([
        'domain' => $domain,
        'shared_inbox' => $inbox,
        'banned' => $banned,
    ]);

    Cache::forget(InstanceService::CACHE_KEY_SHARED_INBOXES_PUBLIC);
    Cache::forget(InstanceService::CACHE_KEY_BANNED_DOMAINS);

    return $inbox;
}

function queuedFanoutContains(string $inbox): bool
{
    $found = false;

    Queue::assertPushed(DeliverActivityChunk::class, function (DeliverActivityChunk $job) use ($inbox, &$found) {
        $found = in_array($inbox, $job->inboxes(), true);

        return $found;
    });

    return $found;
}

it('fans public admin posts out to every known shared inbox', function () {
    $knownInbox = addKnownFanoutInstance('known-admin.example');
    $status = createPrivilegedFanoutStatus(['is_admin' => true]);

    (new StatusActivityPubDeliver($status))->handle();

    expect(queuedFanoutContains($knownInbox))->toBeTrue();
});

it('fans public popular-profile posts out to every known shared inbox', function () {
    $knownInbox = addKnownFanoutInstance('known-popular.example');
    $status = createPrivilegedFanoutStatus([], ['is_popular' => true]);

    (new StatusActivityPubDeliver($status))->handle();

    expect(queuedFanoutContains($knownInbox))->toBeTrue();
});

it('does not globally fan out ordinary public posts', function () {
    addKnownFanoutInstance('known-ordinary.example');
    $status = createPrivilegedFanoutStatus();

    (new StatusActivityPubDeliver($status))->handle();

    Queue::assertNotPushed(DeliverActivityChunk::class);
});

it('does not globally fan out private or unlisted admin posts', function (string $scope) {
    addKnownFanoutInstance("known-{$scope}.example");
    $status = createPrivilegedFanoutStatus(
        ['is_admin' => true],
        [],
        ['scope' => $scope, 'visibility' => $scope]
    );

    (new StatusActivityPubDeliver($status))->handle();

    Queue::assertNotPushed(DeliverActivityChunk::class);
})->with(['private', 'unlisted']);

it('keeps private delivery limited to the actual follower audience', function () {
    $knownInbox = addKnownFanoutInstance('known-private.example');
    $followerInbox = 'https://follower.example/inbox';
    $status = createPrivilegedFanoutStatus(
        ['is_admin' => true],
        [],
        ['scope' => 'private', 'visibility' => 'private']
    );
    $follower = Profile::factory()->remote()->create([
        'inbox_url' => 'https://follower.example/users/follower/inbox',
        'sharedInbox' => $followerInbox,
    ]);

    Follower::create([
        'profile_id' => $follower->id,
        'following_id' => $status->profile_id,
        'local_profile' => false,
    ]);

    (new StatusActivityPubDeliver($status))->handle();

    Queue::assertPushed(DeliverActivityChunk::class, function (DeliverActivityChunk $job) use ($knownInbox, $followerInbox) {
        return in_array($followerInbox, $job->inboxes(), true)
            && ! in_array($knownInbox, $job->inboxes(), true);
    });
});

it('does not globally fan out when the feature flag is disabled', function () {
    config(['federation.activitypub.delivery.allow_share_all' => false]);
    addKnownFanoutInstance('known-disabled.example');
    $status = createPrivilegedFanoutStatus(['is_admin' => true]);

    (new StatusActivityPubDeliver($status))->handle();

    Queue::assertNotPushed(DeliverActivityChunk::class);
});

it('excludes blocked instances from privileged public fanout', function () {
    $allowedInbox = addKnownFanoutInstance('allowed.example');
    $blockedInbox = addKnownFanoutInstance('blocked.example', true);
    $status = createPrivilegedFanoutStatus(['is_admin' => true]);

    (new StatusActivityPubDeliver($status))->handle();

    Queue::assertPushed(DeliverActivityChunk::class, function (DeliverActivityChunk $job) use ($allowedInbox, $blockedInbox) {
        return in_array($allowedInbox, $job->inboxes(), true)
            && ! in_array($blockedInbox, $job->inboxes(), true);
    });
});
