<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\ClientRepository;

uses(LazilyRefreshDatabase::class);

it('completes the authorization redirect with a current consent token', function () {
    $user = User::factory()->create();
    $user->refresh();
    $redirectUri = 'pixelfed://login';
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        'OAuth redirect test',
        [$redirectUri],
    );
    $authorizeUrl = '/oauth/authorize?'.http_build_query([
        'client_id' => $client->getKey(),
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => 'read write follow push',
        'state' => 'test-state',
    ]);

    $this->actingAs($user)
        ->get($authorizeUrl)
        ->assertOk();

    $authToken = session('authToken');

    expect($authToken)->toBeString()->not->toBeEmpty();

    $response = $this->actingAs($user)->post('/oauth/authorize', [
        'auth_token' => $authToken,
        'client_id' => (string) $client->getKey(),
        'state' => 'test-state',
    ]);

    $response->assertRedirect();

    $location = $response->headers->get('Location');

    expect($location)->toStartWith($redirectUri.'?');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
    expect($query)
        ->toHaveKey('code')
        ->and($query['state'] ?? null)->toBe('test-state');
});

it('restarts a same-origin authorization request when the consent token is stale', function () {
    $user = User::factory()->create();
    $authorizeUrl = '/oauth/authorize?client_id=7708&response_type=code&state=test-state';

    $this->actingAs($user)
        ->withSession(['authToken' => 'current-token'])
        ->withHeader('Referer', url($authorizeUrl))
        ->post('/oauth/authorize', [
            'auth_token' => 'stale-token',
            'client_id' => '7708',
            'state' => 'test-state',
        ])
        ->assertRedirect($authorizeUrl);
});

it('does not redirect a stale consent token to an external referer', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['authToken' => 'current-token'])
        ->withHeader('Referer', 'https://attacker.example/oauth/authorize?client_id=7708')
        ->post('/oauth/authorize', [
            'auth_token' => 'stale-token',
            'client_id' => '7708',
        ])
        ->assertForbidden();
});

it('keeps stale consent token errors as json for api clients', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['authToken' => 'current-token'])
        ->withHeader('Referer', url('/oauth/authorize?client_id=7708'))
        ->postJson('/oauth/authorize', [
            'auth_token' => 'stale-token',
            'client_id' => '7708',
        ])
        ->assertForbidden();
});
