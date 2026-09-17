<?php

use AndreasElia\Analytics\Http\Middleware\Analytics;
use App\Http\Middleware\RefreshSessionActivity;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Routing\Router;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Laravel\Passport\Http\Middleware\CreateFreshApiToken;
use Tests\TestCase;

uses(TestCase::class);

function middlewareGroups(): array
{
    $property = new ReflectionProperty(Router::class, 'middlewareGroups');
    $property->setAccessible(true);

    return $property->getValue(app(Router::class));
}

it('preserves local web middleware', function () {
    $web = middlewareGroups()['web'];

    expect($web)
        ->toContain(Analytics::class)
        ->toContain(RefreshSessionActivity::class);
});

it('contains each oauth web state middleware exactly once', function () {
    $oauthWeb = middlewareGroups()['oauth-web'];

    foreach ([
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        SubstituteBindings::class,
        CreateFreshApiToken::class,
    ] as $middleware) {
        expect(array_count_values($oauthWeb)[$middleware] ?? 0)->toBe(1);
    }
});
