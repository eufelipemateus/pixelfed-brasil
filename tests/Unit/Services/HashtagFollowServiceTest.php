<?php

use App\Services\HashtagFollowService;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

uses(TestCase::class);

it('reports a populated hashtag follow cache as warm', function () {
    Redis::shouldReceive('zcard')
        ->once()
        ->with(HashtagFollowService::CACHE_KEY.'123')
        ->andReturn(1);
    Redis::shouldNotReceive('zscore');

    expect(HashtagFollowService::isWarm(123))->toBeTrue();
});

it('uses the warmed marker for an empty hashtag follow cache', function () {
    Redis::shouldReceive('zcard')->once()->andReturn(0);
    Redis::shouldReceive('zscore')
        ->once()
        ->with(HashtagFollowService::CACHE_WARMED, 123)
        ->andReturn('123');

    expect(HashtagFollowService::isWarm(123))->toBeTrue();
});

it('reports an empty unmarked hashtag follow cache as cold', function () {
    Redis::shouldReceive('zcard')->once()->andReturn(0);
    Redis::shouldReceive('zscore')->once()->andReturnNull();

    expect(HashtagFollowService::isWarm(123))->toBeFalse();
});

it('does not warm the database again when an empty cache has a marker', function () {
    Redis::shouldReceive('zcard')->once()->andReturn(0);
    Redis::shouldReceive('zscore')->once()->andReturn('123');
    Redis::shouldReceive('zrange')
        ->once()
        ->with(HashtagFollowService::CACHE_KEY.'123', 0, -1)
        ->andReturn([]);

    expect(HashtagFollowService::getPidByHid(123))->toBe([]);
});
