<?php

namespace App\Console\Commands;

use App\Models\InstanceActor;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class RelayFollow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'relay:follow
                            {actor : Relay actor URL (ex: https://relay.intahnet.co.uk/actor)}
                            {--undo : Send Undo for an existing Follow instead of Follow}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Follow or unfollow an ActivityPub relay using the instance actor';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $actorUrl = trim((string) $this->argument('actor'));

        if (! Helpers::validateUrl($actorUrl)) {
            $this->error('Invalid relay actor URL. It must be a valid https URL.');

            return Command::FAILURE;
        }

        $instanceActor = InstanceActor::first();
        if (! $instanceActor) {
            $this->error('Instance actor not found. Run: php artisan instance:actor');

            return Command::FAILURE;
        }

        $relay = Helpers::profileFirstOrNew($actorUrl);
        if (! $relay || ! Helpers::validateUrl($relay->inbox_url)) {
            $this->error('Unable to resolve relay inbox from actor URL.');

            return Command::FAILURE;
        }

        $instanceActorUrl = $instanceActor->permalink();
        $followId = $instanceActor->permalink('#follows/relay/' . hash('sha256', $actorUrl));
        $isUndo = (bool) $this->option('undo');

        $payload = $isUndo
            ? $this->buildUndoPayload($instanceActorUrl, $followId, $actorUrl)
            : $this->buildFollowPayload($instanceActorUrl, $followId, $actorUrl);
        $payloadJson = json_encode($payload);

        if (! $payloadJson) {
            $this->error('Failed to encode relay payload as JSON.');

            return Command::FAILURE;
        }

        $version = config('pixelfed.version');
        $appUrl = config('app.url');

        $headers = HttpSignature::instanceActorSign($relay->inbox_url, $payloadJson, [
            'Accept' => 'application/activity+json',
            'Content-Type' => 'application/activity+json',
            'User-Agent' => "(Pixelfed/{$version}; +{$appUrl})",
        ]);

        $response = Http::withHeaders($headers)
            ->timeout(config('federation.activitypub.delivery.timeout', 30))
            ->withBody(
                $payloadJson,
                'application/activity+json'
            )
            ->send('POST', $relay->inbox_url);

        if (! $response->successful()) {
            $this->error('Relay request failed with HTTP ' . $response->status());
            $this->line('Response: ' . mb_substr($response->body(), 0, 800));

            return Command::FAILURE;
        }

        $this->info(($isUndo ? 'Undo sent to relay inbox: ' : 'Follow sent to relay inbox: ') . $relay->inbox_url);

        return Command::SUCCESS;
    }

    protected function buildFollowPayload(string $instanceActorUrl, string $followId, string $actorUrl): array
    {
        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $followId,
            'type' => 'Follow',
            'actor' => $instanceActorUrl,
            'object' => $actorUrl,
        ];
    }

    protected function buildUndoPayload(string $instanceActorUrl, string $followId, string $actorUrl): array
    {
        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $instanceActorUrl . '#undo/follows/' . hash('sha256', $followId),
            'type' => 'Undo',
            'actor' => $instanceActorUrl,
            'object' => [
                'id' => $followId,
                'type' => 'Follow',
                'actor' => $instanceActorUrl,
                'object' => $actorUrl,
            ],
        ];
    }
}
