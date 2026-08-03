<?php

use App\Services\ActivityPubFetchService;
use Tests\TestCase;

uses(TestCase::class);

it('rejects unsafe activitypub destinations', function (string $url) {
    expect(ActivityPubFetchService::validateUrl($url))->toBeFalse();
})->with([
    'http' => ['http://93.184.216.34/activity'],
    'credentials' => ['https://user:password@93.184.216.34/activity'],
    'localhost' => ['https://localhost/activity'],
    'ipv4 loopback' => ['https://127.0.0.1/activity'],
    'ipv6 loopback' => ['https://[::1]/activity'],
    'rfc1918 class a' => ['https://10.0.0.1/activity'],
    'rfc1918 class b' => ['https://172.16.0.1/activity'],
    'rfc1918 class c' => ['https://192.168.1.1/activity'],
    'metadata link-local' => ['https://169.254.169.254/latest/meta-data'],
    'ipv6 private' => ['https://[fd00::1]/activity'],
    'ipv6 link-local' => ['https://[fe80::1]/activity'],
    'unspecified ipv4' => ['https://0.0.0.0/activity'],
    'unspecified ipv6' => ['https://[::]/activity'],
    'multicast ipv4' => ['https://224.0.0.1/activity'],
    'multicast ipv6' => ['https://[ff02::1]/activity'],
]);

it('accepts a normalized public https destination', function () {
    expect(ActivityPubFetchService::validateUrl('HTTPS://93.184.216.34/activity?x=1'))
        ->toBe('https://93.184.216.34/activity?x=1');
});

it('validates destinations even when fetchRequest is called directly', function () {
    expect(ActivityPubFetchService::fetchRequest('https://127.0.0.1/private'))->toBeNull();
});
