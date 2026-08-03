<?php

namespace App\Util\ActivityPub;

use App\Profile;
use App\Services\InstanceService;
use App\Status;

class CreateObjectProcessor
{
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

        if (! $this->isValidAttributedTo($payload, $object)) {
            return;
        }

        $normalized = $this->normalize($object);
        if (! $normalized) {
            return;
        }

        if (! $this->isPublicScope($normalized)) {
            return;
        }

        if ($objectType === 'video' && ! $this->hasValidVideoAttachment($normalized)) {
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
            'summary' => $object['summary'] ?? null,
            'sensitive' => (bool) ($object['sensitive'] ?? false),
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

        Helpers::storeStatus($canonicalUrl, $actor, $activity);
    }

    private function normalize(array $object): ?array
    {
        $objectId = isset($object['id']) && is_string($object['id']) ? Helpers::validateUrl($object['id']) : false;
        if (! $objectId) {
            return null;
        }

        $published = $object['published'] ?? null;
        if (! is_string($published) || ! Helpers::validateTimestamp($published)) {
            return null;
        }

        $to = $this->normalizeAudienceField($object['to'] ?? []);
        $cc = $this->normalizeAudienceField($object['cc'] ?? []);
        if (empty($to) && empty($cc)) {
            return null;
        }

        $canonicalUrl = $this->extractCanonicalUrl($object['url'] ?? null, $objectId, strtolower((string) ($object['type'] ?? '')));
        if (! $canonicalUrl) {
            return null;
        }

        $domain = parse_url($canonicalUrl, PHP_URL_HOST);
        if ($domain && in_array($domain, InstanceService::getBannedDomains())) {
            return null;
        }

        $objectDomain = parse_url($objectId, PHP_URL_HOST);
        if ($objectDomain && in_array($objectDomain, InstanceService::getBannedDomains())) {
            return null;
        }

        return [
            'object_id' => $objectId,
            'canonical_url' => $canonicalUrl,
            'published' => $published,
            'to' => $to,
            'cc' => $cc,
            'content' => $this->extractContent($object),
            'attachment' => $this->buildAttachments($object),
        ];
    }

    private function normalizeAudienceField(mixed $field): array
    {
        if (is_string($field)) {
            return [$field];
        }

        if (is_array($field)) {
            return array_values(array_filter($field, fn ($v) => is_string($v) && strlen($v) > 0));
        }

        return [];
    }

    private function isPublicScope(array $normalized): bool
    {
        $scope = Helpers::getScope([
            'id' => $normalized['object_id'],
            'url' => $normalized['canonical_url'],
            'to' => $normalized['to'],
            'cc' => $normalized['cc'],
        ], $normalized['canonical_url']);

        return in_array($scope, ['public', 'unlisted']);
    }

    private function isValidAttributedTo(array $payload, array $object): bool
    {
        if (! isset($object['attributedTo'])) {
            return true;
        }

        $activityActor = Helpers::validateUrl($payload['actor'] ?? null);
        if (! $activityActor) {
            return false;
        }

        $attributedTo = $object['attributedTo'];
        $candidates = [];

        if (is_string($attributedTo)) {
            $candidates[] = $attributedTo;
        } elseif (is_array($attributedTo)) {
            foreach ($attributedTo as $item) {
                if (is_string($item)) {
                    $candidates[] = $item;

                    continue;
                }

                if (is_array($item) && isset($item['id']) && is_string($item['id'])) {
                    $candidates[] = $item['id'];
                }
            }
        }

        foreach ($candidates as $candidate) {
            if (Helpers::validateUrl($candidate) === $activityActor) {
                return true;
            }
        }

        return false;
    }

    private function extractCanonicalUrl(mixed $urlField, string $objectId, string $objectType): ?string
    {
        foreach ($this->extractUrlCandidates($urlField) as $candidate) {
            if (($candidate['mediaType'] ?? null) === 'text/html' && ($candidate['url'] ?? null)) {
                $valid = Helpers::validateUrl($candidate['url']);
                if ($valid) {
                    return $valid;
                }
            }
        }

        if ($objectType === 'video' && ! is_string($urlField) && is_array($urlField)) {
            return null;
        }

        $fallback = Helpers::validateUrl($objectId);

        return $fallback ?: null;
    }

    private function extractContent(array $object): string
    {
        foreach (['content', 'summary', 'name'] as $key) {
            if (isset($object[$key]) && is_string($object[$key])) {
                return $object[$key];
            }
        }

        return '';
    }

    private function buildAttachments(array $object): array
    {
        $attachments = [];
        $allowedMime = array_map('trim', explode(',', config_cache('pixelfed.media_types')));

        $rawAttachments = $object['attachment'] ?? [];
        if (! is_array($rawAttachments)) {
            $rawAttachments = [];
        }

        foreach ($rawAttachments as $attachment) {
            $normalized = $this->normalizeAttachment($attachment, $allowedMime);
            if ($normalized) {
                $attachments[] = $normalized;
            }
        }

        foreach ($this->extractUrlCandidates($object['url'] ?? null) as $candidate) {
            $mediaType = $candidate['mediaType'] ?? null;
            $url = $candidate['url'] ?? null;

            if (! is_string($mediaType) || ! str_starts_with($mediaType, 'video/')) {
                continue;
            }

            if (! in_array($mediaType, $allowedMime)) {
                continue;
            }

            $validUrl = Helpers::validateUrl($url);
            if (! $validUrl) {
                continue;
            }

            $alreadyAdded = collect($attachments)->contains(function ($att) use ($validUrl) {
                $attachmentUrl = $att['url'] ?? null;
                if (! is_string($attachmentUrl)) {
                    return false;
                }

                return Helpers::validateUrl($attachmentUrl) === $validUrl;
            });
            if ($alreadyAdded) {
                continue;
            }

            $attachments[] = [
                'type' => 'Video',
                'mediaType' => $mediaType,
                'url' => $validUrl,
            ];
        }

        return array_slice($attachments, 0, (int) config_cache('pixelfed.max_album_length'));
    }

    private function normalizeAttachment(mixed $attachment, array $allowedMime): ?array
    {
        if (! is_array($attachment)) {
            return null;
        }

        $mediaType = $attachment['mediaType'] ?? null;
        $url = $attachment['url'] ?? null;

        if (! is_string($mediaType) || ! in_array($mediaType, $allowedMime)) {
            return null;
        }

        if (! str_starts_with($mediaType, 'image/') && ! str_starts_with($mediaType, 'video/')) {
            return null;
        }

        if (is_array($url)) {
            $url = head($url);
        }

        if (! is_string($url)) {
            return null;
        }

        $validUrl = Helpers::validateUrl($url);
        if (! $validUrl) {
            return null;
        }

        $normalizedType = str_starts_with($mediaType, 'video/') ? 'Video' : 'Image';

        $normalized = [
            'type' => $normalizedType,
            'mediaType' => $mediaType,
            'url' => $validUrl,
        ];

        foreach (['name', 'blurhash', 'width', 'height', 'license', 'thumbnail_url'] as $optionalField) {
            if (array_key_exists($optionalField, $attachment)) {
                $normalized[$optionalField] = $attachment[$optionalField];
            }
        }

        return $normalized;
    }

    private function extractUrlCandidates(mixed $urlField): array
    {
        $items = [];

        if (is_string($urlField)) {
            $items[] = ['url' => $urlField, 'mediaType' => null];

            return $items;
        }

        if (is_array($urlField)) {
            $iterable = array_is_list($urlField) ? $urlField : [$urlField];

            foreach ($iterable as $item) {
                if (is_string($item)) {
                    $items[] = ['url' => $item, 'mediaType' => null];

                    continue;
                }

                if (! is_array($item)) {
                    continue;
                }

                $href = $item['href'] ?? $item['url'] ?? null;
                if (is_array($href)) {
                    $href = head($href);
                }
                if (! is_string($href)) {
                    continue;
                }

                $items[] = [
                    'url' => $href,
                    'mediaType' => isset($item['mediaType']) && is_string($item['mediaType']) ? $item['mediaType'] : null,
                ];
            }
        }

        return $items;
    }

    private function hasValidVideoAttachment(array $normalized): bool
    {
        foreach ($normalized['attachment'] as $attachment) {
            $mediaType = $attachment['mediaType'] ?? null;
            if (is_string($mediaType) && str_starts_with($mediaType, 'video/')) {
                return true;
            }
        }

        return false;
    }
}
