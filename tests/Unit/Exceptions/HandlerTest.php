<?php

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

uses(TestCase::class);

function renderJsonException(Throwable $exception, bool $debug = false)
{
    config()->set('app.debug', $debug);
    $uri = '/api/exception-test/'.Str::uuid();
    Route::get($uri, fn () => throw $exception);

    return test()->getJson($uri);
}

it('hides unexpected exception details when debug is disabled', function () {
    $response = renderJsonException(new RuntimeException('SQL password at /private/path'), false);

    expect($response->getStatusCode())->toBe(500)
        ->and($response->getContent())->toContain('An unexpected error occurred.')
        ->not->toContain('SQL password')
        ->not->toContain('/private/path');
});

it('shows unexpected exception details when debug is enabled', function () {
    $response = renderJsonException(new RuntimeException('local diagnostic'), true);

    expect($response->getStatusCode())->toBe(500)
        ->and($response->getContent())->toContain('local diagnostic');
});

it('preserves safe http exception statuses', function (Throwable $exception, int $status) {
    $response = renderJsonException($exception, false);

    expect($response->getStatusCode())->toBe($status);
})->with([
    'not found' => [new NotFoundHttpException('Not Found'), 404],
    'forbidden' => [new AccessDeniedHttpException('Forbidden'), 403],
]);

it('preserves explicit http responses', function () {
    $expected = response()->json(['message' => 'safe'], 409);
    $response = renderJsonException(new HttpResponseException($expected), false);

    expect($response->getStatusCode())->toBe(409)
        ->and($response->json())->toBe(['message' => 'safe']);
});

it('preserves validation errors', function () {
    $validator = validator(['name' => ''], ['name' => 'required']);
    $response = renderJsonException(new ValidationException($validator), false);

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getContent())->toContain('name');
});
