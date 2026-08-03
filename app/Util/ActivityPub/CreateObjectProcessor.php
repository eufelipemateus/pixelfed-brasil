<?php

namespace App\Util\ActivityPub;

use App\Profile;
use App\Status;

class CreateObjectProcessor
{
    public function __construct(protected ActivityObjectNormalizer $normalizer) {}

    public function process(Profile $actor, array $payload): void
    {
        if (($payload['type'] ?? null) !== 'Create') {
            return;
        }

        $object = $payload['object'] ?? null;
        if (! is_array($object)) {
            return;
        }

        $objectType = strtolower((string) ($object['type'] ?? ''));
        if (! in_array($objectType, ['article', 'video'])) {
            return;
        }

        $normalized = $this->normalizer->normalizeForIngest($actor, $object);
        if (! $normalized) {
            return;
        }

        if ($normalized['object_type'] === 'video' && ! $normalized['has_video_attachment']) {
            return;
        }

        $activity = [
            'id' => $normalized['object_id'],
            'type' => 'Note',
            'published' => $normalized['published'],
            'attributedTo' => $payload['actor'],
            'to' => $normalized['to'],
            'cc' => $normalized['cc'],
            'content' => $normalized['content'],
            'summary' => $normalized['summary'],
            'sensitive' => $normalized['sensitive'],
            'url' => $normalized['canonical_url'],
            'attachment' => $normalized['attachment'],
        ];

        if (isset($object['tag']) && is_array($object['tag'])) {
            $activity['tag'] = $object['tag'];
        }

        $canonicalUrl = $normalized['canonical_url'];

        if (Status::whereUri($canonicalUrl)->exists() || Status::whereObjectUrl($normalized['object_id'])->exists()) {
            return;
        }

        $status = Helpers::storeStatus($canonicalUrl, $actor, $activity);
        if ($status->type !== $normalized['status_type']) {
            $status->type = $normalized['status_type'];
        }

        $status->entities = Helpers::mergeStatusEntities($status, [
            'ap_object_type' => $normalized['object_type'],
        ]);
        $status->save();
    }
}
