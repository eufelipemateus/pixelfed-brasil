<?php

use App\Models\Poll;
use App\Models\PollVote;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
| POST /api/v1/polls/{id}/votes lives in the `web` group and is no longer
| exempt from CSRF. The first-party client (PollCard.vue via axios) sends
| X-CSRF-TOKEN from the page meta tag, so it keeps working; session-authed
| callers without a token now get 419.
*/

beforeEach(function () {
    config(['instance.polls.enabled' => true]);

    // The framework skips CSRF while running in console + unit tests.
    $property = new ReflectionProperty($this->app, 'isRunningInConsole');
    $property->setValue($this->app, false);
});

function csrfPoll(User $owner): Poll
{
    $status = Status::factory()->create([
        'profile_id' => $owner->profile_id,
        'type' => 'poll',
        'scope' => 'public',
        'visibility' => 'public',
    ]);

    $poll = new Poll;
    $poll->status_id = $status->id;
    $poll->profile_id = $owner->profile_id;
    $poll->poll_options = ['Yes', 'No'];
    $poll->cached_tallies = [0, 0];
    $poll->votes_count = 0;
    $poll->expires_at = now()->addDay();
    $poll->save();

    return $poll;
}

it('rejects a session-authenticated vote without a CSRF token', function () {
    $owner = User::factory()->create();
    $owner->refresh();
    $voter = User::factory()->create();
    $voter->refresh();
    $poll = csrfPoll($owner);

    $this->actingAs($voter)
        ->postJson("/api/v1/polls/{$poll->id}/votes", ['choices' => [0]])
        ->assertStatus(419);

    expect(PollVote::wherePollId($poll->id)->exists())->toBeFalse();
});

it('accepts a vote that carries the CSRF token, as the web client sends it', function () {
    $owner = User::factory()->create();
    $owner->refresh();
    $voter = User::factory()->create();
    $voter->refresh();
    $poll = csrfPoll($owner);

    $this->actingAs($voter)
        ->withSession(['_token' => 'csrf-test'])
        ->withHeader('X-CSRF-TOKEN', 'csrf-test')
        ->postJson("/api/v1/polls/{$poll->id}/votes", ['choices' => [0]])
        ->assertOk();

    expect(PollVote::wherePollId($poll->id)->whereProfileId($voter->profile_id)->exists())->toBeTrue();
});
