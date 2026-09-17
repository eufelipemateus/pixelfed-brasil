<?php

namespace App\Http\Controllers;

use App\Models\InstanceActor;
use App\Models\Profile;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class InstanceActorController extends Controller
{
    public function profile(): Response
    {
        $res = Cache::rememberForever(InstanceActor::PROFILE_KEY, function () {
            $res = (new InstanceActor)->first()->getActor();

            return json_encode($res, JSON_UNESCAPED_SLASHES);
        });

        return response($res)->header('Content-Type', 'application/activity+json');
    }

    public function inbox(Request $request): Response
    {
        abort_if(! (bool) config_cache('federation.activitypub.enabled'), 404);
        abort_if(! config('federation.activitypub.sharedInbox'), 404);

        $payload = $request->getContent();
        if ($payload === '' || strlen($payload) > 1024 * 1024) {
            return response('', 202);
        }

        $activity = json_decode($payload, true, 16);
        if (! is_array($activity) || ($activity['type'] ?? null) !== 'Follow') {
            return response('', 202);
        }

        if (($activity['object'] ?? null) !== InstanceActor::first()?->permalink()) {
            return response('', 202);
        }

        if (! $this->verifySignature($request, $payload, $activity)) {
            return response('', 202);
        }

        $this->sendAcceptForFollow($activity);

        return response('', 202);
    }

    public function outbox(): Response
    {
        $res = json_encode([
            '@context' => [
                'https://www.w3.org/ns/activitystreams',
                'https://w3id.org/security/v1',
                [
                    'manuallyApprovesFollowers' => 'as:manuallyApprovesFollowers',
                    'toot' => 'http://joinmastodon.org/ns#',
                    'featured' => [
                        '@id' => 'toot:featured',
                        '@type' => '@id',
                    ],
                    'featuredTags' => [
                        '@id' => 'toot:featuredTags',
                        '@type' => '@id',
                    ],
                    'alsoKnownAs' => [
                        '@id' => 'as:alsoKnownAs',
                        '@type' => '@id',
                    ],
                    'movedTo' => [
                        '@id' => 'as:movedTo',
                        '@type' => '@id',
                    ],
                    'schema' => 'http://schema.org#',
                    'PropertyValue' => 'schema:PropertyValue',
                    'value' => 'schema:value',
                    'discoverable' => 'toot:discoverable',
                    'Device' => 'toot:Device',
                    'Ed25519Signature' => 'toot:Ed25519Signature',
                    'Ed25519Key' => 'toot:Ed25519Key',
                    'Curve25519Key' => 'toot:Curve25519Key',
                    'EncryptedMessage' => 'toot:EncryptedMessage',
                    'publicKeyBase64' => 'toot:publicKeyBase64',
                    'deviceId' => 'toot:deviceId',
                    'claim' => [
                        '@type' => '@id',
                        '@id' => 'toot:claim',
                    ],
                    'fingerprintKey' => [
                        '@type' => '@id',
                        '@id' => 'toot:fingerprintKey',
                    ],
                    'identityKey' => [
                        '@type' => '@id',
                        '@id' => 'toot:identityKey',
                    ],
                    'devices' => [
                        '@type' => '@id',
                        '@id' => 'toot:devices',
                    ],
                    'messageFranking' => 'toot:messageFranking',
                    'messageType' => 'toot:messageType',
                    'cipherText' => 'toot:cipherText',
                    'suspended' => 'toot:suspended',
                ],
            ],
            'id' => config('app.url').'/i/actor/outbox',
            'type' => 'OrderedCollection',
            'totalItems' => 0,
            'first' => config('app.url').'/i/actor/outbox?page=true',
            'last' => config('app.url').'/i/actor/outbox?min_id=0&page=true',
        ], JSON_UNESCAPED_SLASHES);

        return response($res)->header('Content-Type', 'application/activity+json');
    }

    /** @param array<string, mixed> $activity */
    private function verifySignature(Request $request, string $rawPayload, array $activity): bool
    {
        $signature = $request->header('Signature');
        $date = $request->header('Date');
        if (! $signature || ! $date) {
            return false;
        }

        try {
            $requestDate = new DateTimeImmutable($date);
        } catch (\Exception) {
            return false;
        }

        if ($requestDate < now()->subDay() || $requestDate > now()->addDay()) {
            return false;
        }

        $signatureData = HttpSignature::parseSignatureHeader($signature);
        if (isset($signatureData['error']) || ! isset($signatureData['keyId'], $signatureData['signature'], $signatureData['headers'])) {
            return false;
        }

        $keyId = Helpers::validateUrl($signatureData['keyId']);
        $activityId = Helpers::validateUrl($activity['id'] ?? '');
        $actorUrl = Helpers::pluckval($activity['actor'] ?? null);
        if (! $keyId || ! $activityId || ! is_string($actorUrl) || ! Helpers::validateUrl($actorUrl)) {
            return false;
        }

        $keyDomain = parse_url($keyId, PHP_URL_HOST);
        $activityDomain = parse_url($activityId, PHP_URL_HOST);
        $actorDomain = parse_url($actorUrl, PHP_URL_HOST);
        if (! $keyDomain || $keyDomain !== $activityDomain || $keyDomain !== $actorDomain) {
            return false;
        }

        $actor = Profile::whereKeyId($keyId)->first() ?? Helpers::profileFirstOrNew($actorUrl);
        if (! $actor || ! $actor->domain || ! $actor->public_key) {
            return false;
        }

        $publicKey = openssl_pkey_get_public($actor->public_key);
        if (! $publicKey) {
            return false;
        }

        [$verified] = HttpSignature::verify(
            $publicKey,
            $signatureData,
            $request->headers->all(),
            '/i/actor/inbox',
            $rawPayload
        );

        return $verified === 1;
    }

    /** @param array<string, mixed> $follow */
    private function sendAcceptForFollow(array $follow): void
    {
        $actorUrl = Helpers::pluckval($follow['actor'] ?? null);
        $followId = Helpers::pluckval($follow['id'] ?? null);
        if (! is_string($actorUrl) || ! is_string($followId) || ! Helpers::validateUrl($actorUrl) || ! Helpers::validateUrl($followId)) {
            return;
        }

        $relay = Helpers::profileFirstOrNew($actorUrl);
        $instanceActor = InstanceActor::first();
        if (! $relay || ! $relay->inbox_url || ! Helpers::validateUrl($relay->inbox_url) || ! $instanceActor?->private_key) {
            return;
        }

        $instanceActorUrl = $instanceActor->permalink();
        $accept = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $instanceActor->permalink('#accepts/follows/'.hash('sha256', $followId)),
            'type' => 'Accept',
            'actor' => $instanceActorUrl,
            'object' => [
                'id' => $followId,
                'type' => 'Follow',
                'actor' => $actorUrl,
                'object' => $instanceActorUrl,
            ],
        ];

        try {
            $body = json_encode($accept, JSON_THROW_ON_ERROR);
            $digest = base64_encode(hash('sha256', $body, true));
            $headers = HttpSignature::instanceActorSignWithDigest(
                $relay->inbox_url,
                $digest,
                [
                    'Accept' => 'application/activity+json',
                    'Content-Type' => 'application/activity+json',
                    'User-Agent' => '(Pixelfed/'.config('pixelfed.version').'; +'.config('app.url').')',
                ]
            );

            Http::withHeaders($headers)
                ->timeout((int) config('federation.activitypub.delivery.timeout', 30))
                ->connectTimeout(10)
                ->withBody($body, 'application/activity+json')
                ->post($relay->inbox_url);
        } catch (\Throwable) {
            // Relay delivery is best-effort; invalid or unreachable relays must not fail the inbox request.
        }
    }
}
