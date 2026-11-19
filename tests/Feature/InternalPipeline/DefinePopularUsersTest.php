<?php

use App\Jobs\InternalPipeline\DefinePopularUsers;
use App\Models\Like;
use App\Models\ModLog;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['cache.default' => 'array']);
    Redis::spy();
});

function popularTestUser(array $attributes = []): User
{
    return User::factory()->create($attributes)->refresh();
}

function popularTestLike(User $author, User $liker, string $likedAt, array $statusAttributes = []): Like
{
    $status = Status::factory()->photo()->create(array_merge([
        'profile_id' => $author->profile_id,
    ], $statusAttributes));

    return Like::create([
        'profile_id' => $liker->profile_id,
        'status_id' => $status->id,
        'created_at' => $likedAt,
        'updated_at' => $likedAt,
    ]);
}

it('selects at most 20 profiles with deterministic ordering', function () {
    $liker = popularTestUser();
    $authors = collect(range(1, 21))->map(fn () => popularTestUser());

    foreach ($authors as $author) {
        popularTestLike($author, $liker, '2026-10-15 12:00:00');
    }

    // The last profile outranks the others; tied profiles follow by ID.
    popularTestLike($authors->last(), $liker, '2026-10-16 12:00:00');
    $expected = [$authors->last()->profile_id, ...$authors->take(19)->pluck('profile_id')->sort()->values()->all()];

    expect(DefinePopularUsers::popularProfileIds('2026-10'))->toBe($expected);
});

it('counts only eligible likes and profiles from the selected month', function () {
    $liker = popularTestUser();
    $eligible = popularTestUser();
    $private = popularTestUser();
    $admin = popularTestUser(['is_admin' => true]);
    $nsfw = popularTestUser();
    $deletedPost = popularTestUser();
    $deletedLike = popularTestUser();
    $outsideMonth = popularTestUser();

    // An older post is eligible when the like arrived in the selected month.
    popularTestLike($eligible, $liker, '2026-10-01 00:00:00', [
        'created_at' => '2026-09-01 12:00:00',
    ]);
    $private->profile->update(['is_private' => true]);
    popularTestLike($private, $liker, '2026-10-15 12:00:00');
    popularTestLike($admin, $liker, '2026-10-15 12:00:00');
    popularTestLike($nsfw, $liker, '2026-10-15 12:00:00', ['is_nsfw' => true]);
    $removedPostLike = popularTestLike($deletedPost, $liker, '2026-10-15 12:00:00');
    $removedPostLike->status->delete();
    popularTestLike($deletedLike, $liker, '2026-10-15 12:00:00')->delete();
    popularTestLike($outsideMonth, $liker, '2026-11-01 00:00:00');

    expect(DefinePopularUsers::popularProfileIds('2026-10'))->toBe([$eligible->profile_id]);
});

it('updates labels and moderation logs without sending mail or repeating transitions', function () {
    Mail::fake();

    $previous = popularTestUser();
    $incoming = popularTestUser();
    $liker = popularTestUser();

    $previous->profile->update(['is_popular' => true]);
    popularTestLike($incoming, $liker, '2026-10-15 12:00:00');

    foreach ([$previous, $incoming] as $user) {
        Cache::put('pf:services:account:'.$user->profile_id, ['label' => 'stale'], 3600);
    }

    $job = new DefinePopularUsers('2026-10');
    $job->handle();

    expect((bool) $previous->profile->fresh()->is_popular)->toBeFalse()
        ->and((bool) $incoming->profile->fresh()->is_popular)->toBeTrue()
        ->and(ModLog::whereAction('system.user.unpopular')->count())->toBe(1)
        ->and(ModLog::whereAction('system.user.popular')->count())->toBe(1)
        ->and(Cache::has('pf:services:account:'.$previous->profile_id))->toBeFalse()
        ->and(Cache::has('pf:services:account:'.$incoming->profile_id))->toBeFalse();

    $job->handle();

    expect(ModLog::whereAction('system.user.unpopular')->count())->toBe(1)
        ->and(ModLog::whereAction('system.user.popular')->count())->toBe(1);
    Mail::assertNothingOutgoing();

    (new DefinePopularUsers('2026-11'))->handle();

    expect((bool) $incoming->profile->fresh()->is_popular)->toBeFalse()
        ->and(ModLog::whereAction('system.user.unpopular')->count())->toBe(2);
    Mail::assertNothingOutgoing();
});

it('keeps the old command alias and makes test mode a read-only preview', function () {
    Bus::fake();
    Mail::fake();
    $month = now('UTC')->subMonthNoOverflow()->format('Y-m');

    $this->artisan('app:define-popular-users', ['--test' => true])
        ->expectsOutputToContain('Perfis populares para '.$month)
        ->assertExitCode(0);
    Bus::assertNotDispatched(DefinePopularUsers::class);

    $this->artisan('app:send-popular-in-month')->assertExitCode(0);
    Bus::assertDispatched(DefinePopularUsers::class, function ($job) use ($month) {
        return $job->month === $month && $job->queue === 'low';
    });
    Mail::assertNothingOutgoing();
});
