<?php

namespace App\Util\ActivityPub\Handlers;

use App\Jobs\ProfilePipeline\HandleUpdateActivity;
use App\Jobs\StatusPipeline\StatusRemoteUpdatePipeline;
use App\Profile;
use App\Status;
use App\Util\ActivityPub\ActivityObjectNormalizer;
use App\Util\ActivityPub\Contracts\ActivityHandler;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\Validator\UpdatePersonValidator;

class UpdateHandler implements ActivityHandler
{
    public function __construct(
        protected array $headers,
        protected $profile,
        protected array $payload,
        protected ?ActivityObjectNormalizer $normalizer = null
    ) {
        $this->normalizer = $this->normalizer ?? app(ActivityObjectNormalizer::class);
    }

    public function handle(): void
    {
        if (! isset($this->payload['object']) || ! is_array($this->payload['object'])) {
            return;
        }

        $activity = $this->payload['object'];

        if (! isset($activity['type'], $activity['id'])) {
            return;
        }

        if (! Helpers::validateUrl($activity['id'])) {
            return;
        }

        if (in_array($activity['type'], ['Note', 'Article', 'Video'])) {
            $this->handleStatusUpdate($activity);
        } elseif ($activity['type'] === 'Person') {
            if (UpdatePersonValidator::validate($this->payload)) {
                HandleUpdateActivity::dispatch($this->payload)->onQueue('low');
            }
        }
    }

    protected function handleStatusUpdate(array $activity): void
    {
        $actorUrl = Helpers::validateUrl($this->payload['actor'] ?? null);
        if (! $actorUrl) {
            return;
        }

        $objectId = Helpers::validateUrl($activity['id'] ?? null);
        if (! $objectId) {
            return;
        }

        if (parse_url($actorUrl, PHP_URL_HOST) !== parse_url($objectId, PHP_URL_HOST)) {
            return;
        }

        $status = Status::whereObjectUrl($objectId)
            ->orWhere('url', $objectId)
            ->first();

        if (! $status) {
            return;
        }

        $actor = Profile::find($status->profile_id);
        if (! $actor || $actor->domain === null || Helpers::validateUrl($actor->remote_url) !== $actorUrl) {
            return;
        }

        $normalized = null;
        if (in_array($activity['type'], ['Article', 'Video'])) {
            $normalized = $this->normalizer->normalizeForIngest($actor, $activity);
            if (! $normalized) {
                return;
            }
            if ($normalized['object_type'] === 'video' && ! $normalized['has_video_attachment']) {
                return;
            }
        }

        StatusRemoteUpdatePipeline::dispatch([
            'actor' => $actorUrl,
            'object' => $activity,
            'normalized' => $normalized,
        ]);
    }
}
