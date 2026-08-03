<?php

namespace App\Services;

use App\Util\ActivityPub\HttpSignature;
use Cache;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class ActivityPubFetchService
{
    const CACHE_KEY = 'pf:services:apfetchs:';

    const MAX_REDIRECTS = 2;

    const MAX_RESPONSE_BYTES = 2097152;

    public static function get($url, $validateUrl = true)
    {
        $url = self::validateUrl($url);
        if (! $url) {
            return false;
        }
        $domain = parse_url($url, PHP_URL_HOST);
        if (! $domain) {
            return false;
        }
        $domainKey = base64_encode($domain);
        $urlKey = hash('sha256', $url);
        $key = self::CACHE_KEY.$domainKey.':'.$urlKey;

        return Cache::remember($key, 450, function () use ($url) {
            return self::fetchRequest($url);
        });
    }

    public static function validateUrl($url)
    {
        if (is_array($url)) {
            $url = $url[0];
        }

        if (! is_string($url) || substr_count($url, '://') !== 1) {
            return false;
        }

        $parts = parse_url($url);
        if (
            ! is_array($parts)
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
        ) {
            return false;
        }

        $host = rtrim(strtolower($parts['host']), '.');
        if (function_exists('idn_to_ascii')) {
            $asciiHost = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($asciiHost === false) {
                return false;
            }
            $host = $asciiHost;
        }

        if ($host === 'localhost' || ! self::resolvePublicAddresses($host)) {
            return false;
        }

        if (app()->environment() === 'production') {
            $bannedInstances = InstanceService::getBannedDomains();
            if (in_array($host, $bannedInstances, true)) {
                return false;
            }
        }

        $parts['host'] = $host;

        return self::buildUrl($parts);
    }

    public static function fetchRequest($url, $returnJsonFormat = false)
    {
        $url = self::validateUrl($url);
        if (! $url) {
            return;
        }

        for ($redirects = 0; $redirects <= self::MAX_REDIRECTS; $redirects++) {
            $response = self::request($url);
            if (! $response) {
                return;
            }

            if ($response->redirect()) {
                if ($redirects === self::MAX_REDIRECTS || ! $response->hasHeader('Location')) {
                    return;
                }

                $location = $response->header('Location');
                $nextUrl = (string) UriResolver::resolve(new Uri($url), new Uri($location));
                $url = self::validateUrl($nextUrl);
                if (! $url) {
                    return;
                }

                continue;
            }

            return self::validatedBody($response, $returnJsonFormat);
        }
    }

    private static function request(string $url)
    {
        $baseHeaders = [
            'Accept' => 'application/activity+json',
        ];

        $headers = HttpSignature::instanceActorSign($url, false, $baseHeaders, 'get');
        $headers['Accept'] = 'application/activity+json';
        $headers['User-Agent'] = 'PixelFedBot/1.0.0 (Pixelfed/'.config('pixelfed.version').'; +'.config('app.url').')';

        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT) ?: 443;
        $addresses = self::resolvePublicAddresses($host);
        if (! $addresses) {
            return;
        }

        $pinned = array_map(
            fn (string $ip): string => $host.':'.$port.':'.(str_contains($ip, ':') ? '['.$ip.']' : $ip),
            $addresses
        );

        try {
            $res = Http::withOptions([
                'allow_redirects' => false,
                'curl' => [
                    CURLOPT_RESOLVE => $pinned,
                ],
            ])
                ->withHeaders($headers)
                ->timeout(15)
                ->connectTimeout(5)
                ->retry(2, 250)
                ->get($url);
        } catch (RequestException $e) {
            return;
        } catch (ConnectionException $e) {
            return;
        } catch (\Exception $e) {
            return;
        }

        return $res;
    }

    private static function validatedBody($res, bool $returnJsonFormat)
    {
        if (! $res->ok()) {
            return;
        }

        $contentLength = (int) $res->header('Content-Length', 0);
        if ($contentLength > self::MAX_RESPONSE_BYTES || strlen($res->body()) > self::MAX_RESPONSE_BYTES) {
            return;
        }

        if (! $res->hasHeader('Content-Type')) {
            return;
        }

        $contentType = $res->getHeader('Content-Type')[0];

        if (! $contentType) {
            return;
        }

        // Parse Content-Type: extract media type (case-insensitive) and parameters
        $contentTypeParts = array_map('trim', explode(';', $contentType));
        $mediaType = strtolower($contentTypeParts[0]);

        $acceptedMediaTypes = [
            'application/activity+json',
            'application/ld+json',
        ];

        if (! in_array($mediaType, $acceptedMediaTypes, true)) {
            return;
        }

        //// For application/ld+json, verify the ActivityStreams profile parameter
        if ($mediaType === 'application/ld+json') {
            $hasActivityStreamsProfile = false;
            foreach (array_slice($contentTypeParts, 1) as $param) {
                $param = trim($param);
                if (stripos($param, 'profile=') === 0) {
                    $profile = trim(substr($param, strlen('profile=')), ' "\'');
                    if ($profile === 'https://www.w3.org/ns/activitystreams') {
                        $hasActivityStreamsProfile = true;
                        break;
                    }
                }
            }
            if (! $hasActivityStreamsProfile) {
                return;
            }
        }

        return $returnJsonFormat ? $res->json() : $res->body();
    }

    private static function resolvePublicAddresses(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return self::isPublicAddress($host) ? [$host] : [];
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if (! is_array($records) || $records === []) {
            return [];
        }

        $addresses = [];
        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if (! $ip || ! self::isPublicAddress($ip)) {
                return [];
            }
            $addresses[] = $ip;
        }

        return array_values(array_unique($addresses));
    }

    private static function isPublicAddress(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $numeric = ip2long($ip);
            if ($numeric !== false && ($numeric & 0xF0000000) === 0xE0000000) {
                return false;
            }
        }

        if (
            filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            && str_starts_with(strtolower($ip), 'ff')
        ) {
            return false;
        }

        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    private static function buildUrl(array $parts): string
    {
        $host = str_contains($parts['host'], ':') ? '['.$parts['host'].']' : $parts['host'];

        return 'https://'.$host
            .(isset($parts['port']) ? ':'.$parts['port'] : '')
            .($parts['path'] ?? '')
            .(isset($parts['query']) ? '?'.$parts['query'] : '')
            .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }
}
