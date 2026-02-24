<?php

namespace App\Jobs\InboxPipeline;

use App\Jobs\DeletePipeline\DeleteRemoteProfilePipeline;
use App\Profile;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;
use Cache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeleteWorker implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $headers;

    protected $payload;

    public $timeout = 300;

    public $tries = 1;

    public $maxExceptions = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($headers, $payload)
    {
        $this->headers = $headers;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $profile = null;
        $headers = $this->headers;
        $payload = $this->payload;

        // Verify headers exist
        if (! $headers) {
            Log::info('DeleteWorker: Headers not provided, skipping job');

            return;
        }

        // Verify payload exists
        if (! $payload) {
            Log::info('DeleteWorker: Payload not provided, skipping job');

            return;
        }

        $payload = json_decode($payload, true, 8);

        if (! isset($headers['signature']) || ! isset($headers['date'])) {
            Log::info('DeleteWorker: Missing signature or date in headers, skipping job');

            return;
        }

        if (! $headers || ! $payload) {
            Log::info('DeleteWorker: Empty headers or payload, skipping job');

            return;
        }

        if ($payload['type'] === 'Delete' &&
            ((is_string($payload['object']) &&
                $payload['object'] === $payload['actor']) ||
            (is_array($payload['object']) &&
              isset($payload['object']['id'], $payload['object']['type']) &&
              $payload['object']['type'] === 'Person' &&
              $payload['actor'] === $payload['object']['id']
            ))
        ) {
            $actor = $payload['actor'];
            if ($this->verifySignature($headers, $payload) == true) {
                $actorDelete = Profile::whereRemoteUrl($actor)->exists();
                if ($actorDelete) {
                    if ($this->verifySignature($headers, $payload) == true) {
                        // Nota: Verifique se a variável $key está definida no seu escopo global ou se deveria vir do payload
                        Cache::set($actor, false);
                        $profile = Profile::whereNotNull('domain')
                            ->whereNull('status')
                            ->whereRemoteUrl($actor)
                            ->first();
                        if ($profile) {
                            DeleteRemoteProfilePipeline::dispatch($profile)->onQueue('inbox');
                        }

                        return 1;
                    } else {
                        return 1;
                    }
                } else {
                    return 1;
                }
            } else {
                return 1;
            }
        } else {
            $profile = null;

            if ($this->verifySignature($headers, $payload) == true) {
                ActivityHandler::dispatch($headers, $profile, $payload)->onQueue('delete');

                return 1;
            } else {
                return 1;
            }
        }
    }

    protected function verifySignature($headers, $payload)
    {
        $body = $this->payload;
        $bodyDecoded = $payload;
        $signature = is_array($headers['signature']) ? $headers['signature'][0] : $headers['signature'];
        $date = is_array($headers['date']) ? $headers['date'][0] : $headers['date'];

        if (! $signature || ! $date) {
            return false;
        }

        if (! now()->parse($date)->gt(now()->subDays(1)) ||
           ! now()->parse($date)->lt(now()->addDays(1))
        ) {
            return false;
        }

        $signatureData = HttpSignature::parseSignatureHeader($signature);

        if (! isset($signatureData['keyId'], $signatureData['signature'], $signatureData['headers']) || isset($signatureData['error'])) {
            return false;
        }

        $keyId = Helpers::validateUrl($signatureData['keyId']);
        $id = Helpers::validateUrl($bodyDecoded['id']);
        $keyDomain = parse_url($keyId, PHP_URL_HOST);
        $idDomain = parse_url($id, PHP_URL_HOST);

        if (isset($bodyDecoded['object'])
            && is_array($bodyDecoded['object'])
            && isset($bodyDecoded['object']['attributedTo'])
        ) {
            $attr = Helpers::pluckval($bodyDecoded['object']['attributedTo']);
            if (is_array($attr)) {
                $attr = $attr['id'] ?? '';
            }
            if (parse_url($attr, PHP_URL_HOST) !== $keyDomain) {
                return false;
            }
        }

        if (! $keyDomain || ! $idDomain || $keyDomain !== $idDomain) {
            return false;
        }

        $actor = Profile::whereKeyId($keyId)->first();
        if (! $actor) {
            $actorUrl = is_array($bodyDecoded['actor']) ? $bodyDecoded['actor'][0] : $bodyDecoded['actor'];
            $actor = Helpers::profileFirstOrNew($actorUrl);
        }

        if (! $actor) {
            return false;
        }

        $pkey = openssl_pkey_get_public($actor->public_key);
        if (! $pkey) {
            return false;
        }

        $inboxPath = '/f/inbox';
        [$verified, $headers] = HttpSignature::verify($pkey, $signatureData, $headers, $inboxPath, $body);

        return $verified == 1;
    }

    protected function blindKeyRotation($headers, $payload)
    {
        $signature = is_array($headers['signature']) ? $headers['signature'][0] : $headers['signature'];
        $date = is_array($headers['date']) ? $headers['date'][0] : $headers['date'];

        if (! $signature || ! $date) {
            return;
        }

        if (! now()->parse($date)->gt(now()->subDays(1)) ||
           ! now()->parse($date)->lt(now()->addDays(1))
        ) {
            return;
        }

        $signatureData = HttpSignature::parseSignatureHeader($signature);

        if (! isset($signatureData['keyId'], $signatureData['signature'], $signatureData['headers']) || isset($signatureData['error'])) {
            return;
        }

        $keyId = Helpers::validateUrl($signatureData['keyId']);
        $actor = Profile::whereKeyId($keyId)->whereNotNull('remote_url')->first();

        if (! $actor || Helpers::validateUrl($actor->remote_url) == false) {
            return;
        }

        try {
            $res = Http::timeout(20)->withHeaders([
                'Accept' => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
                'User-Agent' => 'PixelfedBot v0.1 - https://pixelfed.org',
            ])->get($actor->remote_url);
        } catch (ConnectionException $e) {
            return false;
        }

        if (! $res->ok()) {
            return false;
        }

        $data = json_decode($res->body(), true, 8);
        if (! isset($data['publicKey'], $data['publicKey']['id']) || $data['publicKey']['id'] !== $actor->key_id) {
            return;
        }

        $actor->public_key = $data['publicKey']['publicKeyPem'];
        $actor->save();

        return $this->verifySignature($headers, $payload);
    }
}
