<?php

namespace App\Http\Controllers;

use App\Models\InstanceActor;
use App\Profile;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class InstanceActorController extends Controller
{
    public function profile()
    {
        $res = Cache::rememberForever(InstanceActor::PROFILE_KEY, function () {
            $res = InstanceActor::query()->first()->getActor();

            return json_encode($res, JSON_UNESCAPED_SLASHES);
        });

        return response($res)->header('Content-Type', 'application/activity+json');
    }

    public function inbox(Request $request)
    {
        abort_if(! (bool) config_cache('federation.activitypub.enabled'), 404);
        abort_if(! config('federation.activitypub.sharedInbox'), 404);

        $headers = $request->headers->all();
        $payload = $request->getContent();

        if (empty($headers) || empty($payload) || ! isset($headers['signature']) || ! isset($headers['date'])) {
            return response('', 202);
        }

        $obj = json_decode($payload, true, 8);

        if (! is_array($obj) || ! isset($obj['type']) || ! isset($obj['id'])) {
            return response('', 202);
        }

        if (! $this->verifySignature($headers, $payload, $obj)) {
            return response('', 202);
        }

        if ($obj['type'] === 'Follow') {
            $this->sendAcceptForFollow($obj);
        }

        return response('', 202);
    }

    public function outbox()
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
            'id' => config('app.url') . '/i/actor/outbox',
            'type' => 'OrderedCollection',
            'totalItems' => 0,
            'first' => config('app.url') . '/i/actor/outbox?page=true',
            'last' => config('app.url') . '/i/actor/outbox?min_id=0page=true',
        ], JSON_UNESCAPED_SLASHES);

        return response($res)->header('Content-Type', 'application/activity+json');
    }

    protected function verifySignature(array $headers, string $rawPayload, array $payload): bool
    {
        $signature = is_array($headers['signature']) ? $headers['signature'][0] : $headers['signature'];
        $date = is_array($headers['date']) ? $headers['date'][0] : $headers['date'];

        if (! $signature || ! $date) {
            return false;
        }

        if (! now()->parse($date)->gt(now()->subDays(1)) || ! now()->parse($date)->lt(now()->addDays(1))) {
            return false;
        }

        $signatureData = HttpSignature::parseSignatureHeader($signature);
        if (
            ! isset($signatureData['keyId'], $signatureData['signature'], $signatureData['headers']) ||
            isset($signatureData['error'])
        ) {
            return false;
        }

        $keyId = Helpers::validateUrl($signatureData['keyId']);
        $id = Helpers::validateUrl($payload['id']);
        if (! $keyId || ! $id) {
            return false;
        }

        $keyDomain = parse_url($keyId, PHP_URL_HOST);
        $idDomain = parse_url($id, PHP_URL_HOST);
        if (! $keyDomain || ! $idDomain || $keyDomain !== $idDomain) {
            return false;
        }

        $actor = Profile::whereKeyId($keyId)->first();
        if (! $actor) {
            $actorUrl = Helpers::pluckval($payload['actor'] ?? null);
            if (! is_string($actorUrl) || ! $actorUrl) {
                return false;
            }
            $actor = Helpers::profileFirstOrNew($actorUrl);
        }

        if (! $actor || ! $actor->public_key) {
            return false;
        }

        $publicKey = openssl_pkey_get_public($actor->public_key);
        if (! $publicKey) {
            return false;
        }

        [$verified, ] = HttpSignature::verify($publicKey, $signatureData, $headers, '/i/actor/inbox', $rawPayload);

        return $verified === 1;
    }

    protected function sendAcceptForFollow(array $follow): void
    {
        $actorUrl = Helpers::pluckval($follow['actor'] ?? null);
        if (! is_string($actorUrl) || ! Helpers::validateUrl($actorUrl)) {
            return;
        }

        $relay = Helpers::profileFirstOrNew($actorUrl);
        if (! $relay || ! $relay->inbox_url || ! Helpers::validateUrl($relay->inbox_url)) {
            return;
        }

        $instanceActor = InstanceActor::first();
        if (! $instanceActor) {
            return;
        }

        $instanceActorUrl = $instanceActor->permalink();
        $followId = Helpers::pluckval($follow['id'] ?? null);
        $accept = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $instanceActor->permalink('#accepts/follows/' . hash('sha256', (string) $followId)),
            'type' => 'Accept',
            'actor' => $instanceActorUrl,
            'object' => [
                'id' => $followId,
                'type' => 'Follow',
                'actor' => $actorUrl,
                'object' => $instanceActorUrl,
            ],
        ];
        $acceptJson = json_encode($accept);

        if (! $acceptJson) {
            return;
        }

        $version = config('pixelfed.version');
        $appUrl = config('app.url');
        $headers = HttpSignature::instanceActorSign($relay->inbox_url, $acceptJson, [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/activity+json',
            'User-Agent' => "(Pixelfed/{$version}; +{$appUrl})",
        ]);

        Http::withHeaders($headers)
            ->timeout(config('federation.activitypub.delivery.timeout', 30))
            ->withBody(
                $acceptJson,
                'application/activity+json'
            )
            ->send('POST', $relay->inbox_url);
    }
}
