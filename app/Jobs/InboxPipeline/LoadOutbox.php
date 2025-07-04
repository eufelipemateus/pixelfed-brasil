<?php

namespace App\Jobs\InboxPipeline;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use App\Status;

class LoadOutbox implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public $profile;

    public const LIMIT_ACTIVITIES = 10;

    /**
     * Create a new job instance.
     *
     * @param \App\Profile $profile The profile for which to load the outbox.
     */
    public function __construct($profile)
    {
        $this->profile = $profile;
    }

    /**
     * Execute the job.
     *
     * This method fetches all pages of the outbox for the given profile,
     * processes each "Create" activity, and handles the creation of notes.
     *
     */
    public function handle(): void
    {
        Log::info('Starting LoadOutbox job for: ' . $this->profile->username);


        $outboxUrl = $this->profile->outbox_url;

        $allItems = $this->fetchAllOutboxPages($outboxUrl);

        Log::info('Total activities collected: ' . count($allItems));
        $count = 0;
        foreach ($allItems as $item) {
            if ($count >= self::LIMIT_ACTIVITIES) {
                Log::info('Activity limit reached: ' . self::LIMIT_ACTIVITIES);
                break;
            }
            if (isset($item['type']) && $item['type'] === 'Create') {
                $this->handleCreateActivity($item);
                $count++;
            }
        }
    }

    /**
     * Fetches all pages of the outbox and filters for "Create" activities with "Note" objects.
     *
     * This method retrieves all pages of the outbox from the given URL,
     * filtering for activities of type "Create" that contain a "Note" object.
     *
     */
    private function fetchAllOutboxPages(string $url): array
    {
        $filteredItems = [];
        $data = $this->fetchActivityPubJson($url);

        if (!isset($data['first'])) {
            Log::error('The first page of the outbox was not found.');
            return [];
        }

        $nextUrl = is_array($data['first']) ? $data['first']['id'] ?? null : $data['first'];
        while ($nextUrl) {
            $page = $this->fetchActivityPubJson($nextUrl);

            if (!isset($page['orderedItems']) || !is_array($page['orderedItems'])) {
                break;
            }

            foreach ($page['orderedItems'] as $item) {
                if (
                    isset($item['type']) && $item['type'] === 'Create'
                    && isset($item['object']['type']) && $item['object']['type'] === 'Note'
                ) {
                    $filteredItems[] = $item;
                }
            }

            $nextUrl = $page['next'] ?? null;
        }

        return $filteredItems;
    }


    /**
     * Fetches JSON data from an ActivityPub URL.
     *
     * This method uses cURL to retrieve JSON data from the specified URL,
     * setting appropriate headers for ActivityPub requests.
     *
     */
    private function fetchActivityPubJson(string $url): ?array
    {

        $version = config('pixelfed.version');
        $appUrl = config('app.url');

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Accept: application/activity+json',
                'User-Agent' => "(Pixelfed/{$version}; +{$appUrl})",
            ]
        );
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return json_decode($response, true);
        } else {
            Log::info("Error requesting: $url (HTTP $httpCode)");
            return null;
        }
    }


    /**
     * Verifies if the note attachment is valid.
     *
     * This method checks if the note has a valid inReplyTo URL or verifies
     * the attachments of the activity.
     *
     */
    public function verifyNoteAttachment(array $payload)
    {
        $activity = $payload['object'];

        if (
            isset($activity['inReplyTo'])
            && !empty($activity['inReplyTo'])
            && Helpers::validateUrl($activity['inReplyTo'])
        ) {
            return true;
        }

        $valid = Helpers::verifyAttachments($activity);
        return $valid;
    }

    /**
     * Handles the creation of a note activity from the inbox pipeline.
     *
     * This method processes the incoming payload for a note creation,
     * retrieves or creates the actor associated with the activity,
     * and stores the status if it does not already exist.
     *
     */
    public function handleNoteCreate($payload)
    {
        $activity = $payload['object'];

        $hasUrl = isset($activity['url']);
        $url = isset($activity['url']) ? $activity['url'] : $activity['id'];

        if ($hasUrl) {
            if (Status::whereUri($url)->exists()) {
                return;
            }
        } else {
            if (Status::whereObjectUrl($url)->exists()) {
                return;
            }
        }

        $actor = $this->actorFirstOrCreate($payload['actor']);
        if (! $actor || $actor->domain == null) {
            return;
        }
        Helpers::storeStatus(
            $url,
            $actor,
            $activity
        );
    }


    /**
     * Adiciona comentários ao código selecionado.
     *
     * Esta função permite inserir comentários explicativos ou descritivos
     * em trechos de código, facilitando o entendimento e a manutenção.
     *
     */
    public function handleCreateActivity($payload)
    {
        $activity = $payload['object'];
        if (config('autospam.live_filters.enabled')) {
            $filters = config('autospam.live_filters.filters');
            if (
                ! empty($filters) && isset($activity['content'])
                && ! empty($activity['content'])
                && strlen($filters) > 3
            ) {
                $filters = array_map('trim', explode(',', $filters));
                $content = $activity['content'];
                foreach ($filters as $filter) {
                    $filter = trim(strtolower($filter));
                    if (! $filter || ! strlen($filter)) {
                        continue;
                    }
                    if (str_contains(strtolower($content), $filter)) {
                        return;
                    }
                }
            }
        }
        $actor = $this->actorFirstOrCreate($payload['actor']);
        if (! $actor || $actor->domain == null) {
            return;
        }

        if (! isset($activity['to'])) {
            return;
        }

        if ($activity['type'] == 'Note' && ! empty($activity['inReplyTo'])) {
            $this->handleNoteReply($payload);
        } elseif ($activity['type'] == 'Note' && ! empty($activity['attachment'])) {
            if (! $this->verifyNoteAttachment($payload)) {
                return;
            }
            $this->handleNoteCreate($payload);
        }
    }


    /**
     * Handles a "Note" reply activity from the inbox pipeline.
     *
     * This method processes the incoming payload for a reply to a note,
     * retrieves or creates the actor associated with the activity,
     * and ensures the replied-to status is fetched or created.
     *
     */
    public function handleNoteReply($payload)
    {
        $activity = $payload['object'];
        $actor = $this->actorFirstOrCreate($payload['actor']);
        if (! $actor || $actor->domain == null) {
            return;
        }

        $inReplyTo = $activity['inReplyTo'];
        $url = isset($activity['url']) ? $activity['url'] : $activity['id'];

        Helpers::statusFirstOrFetch($url, true);
    }

    /**
     * Retrieves or creates an actor profile based on the given actor URL.
     *
     * This method fetches the actor's profile using the provided URL,
     * creating it if it does not already exist.
     *
     */
    public function actorFirstOrCreate($actorUrl)
    {
        return Helpers::profileFetch($actorUrl);
    }
}
