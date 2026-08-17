<?php

namespace App\Util\ActivityPub;

use App\Profile;
use App\Services\InstanceService;

class ActivityObjectNormalizer
{
    public function normalizeForIngest(Profile $actor, array $object): ?array
    {
        $objectType = strtolower((string) ($object['type'] ?? ''));
        if (! in_array($objectType, ['article', 'video'])) {
            return null;
        }

        $actorUrl = Helpers::validateUrl($actor->remote_url);
        if (! $actorUrl) {
            return null;
        }

        if (! $this->isValidAttributedTo($actorUrl, $object['attributedTo'] ?? null)) {
            return null;
        }

        $objectId = isset($object['id']) && is_string($object['id']) ? Helpers::validateUrl($object['id']) : false;
        if (! $objectId) {
            return null;
        }

        if (parse_url($actorUrl, PHP_URL_HOST) !== parse_url($objectId, PHP_URL_HOST)) {
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

        $canonicalUrl = $this->extractCanonicalUrl($object['url'] ?? null, $objectId);
        if (! $canonicalUrl) {
            return null;
        }

        if (parse_url($actorUrl, PHP_URL_HOST) !== parse_url($canonicalUrl, PHP_URL_HOST)) {
            return null;
        }

        $bannedDomains = InstanceService::getBannedDomains();
        $canonicalDomain = parse_url($canonicalUrl, PHP_URL_HOST);
        $objectDomain = parse_url($objectId, PHP_URL_HOST);
        if (
            ($canonicalDomain && in_array($canonicalDomain, $bannedDomains)) ||
            ($objectDomain && in_array($objectDomain, $bannedDomains))
        ) {
            return null;
        }

        $attachment = $this->buildAttachments($object, $objectType);

        $scope = Helpers::getScope([
            'id' => $objectId,
            'url' => $canonicalUrl,
            'to' => $to,
            'cc' => $cc,
        ], $canonicalUrl);

        if (! in_array($scope, ['public', 'unlisted'])) {
            return null;
        }

        return [
            'object_type' => $objectType,
            'status_type' => $objectType === 'video' ? 'video' : 'text',
            'object_id' => $objectId,
            'canonical_url' => $canonicalUrl,
            'published' => $published,
            'to' => $to,
            'cc' => $cc,
            'content' => $this->extractContent($object),
            'summary' => isset($object['summary']) && is_string($object['summary']) ? $object['summary'] : null,
            'sensitive' => (bool) ($object['sensitive'] ?? false),
            'attachment' => $attachment,
            'has_video_attachment' => $this->hasVideoAttachment($attachment),
        ];
    }

    private function isValidAttributedTo(string $actorUrl, mixed $attributedTo): bool
    {
        if (! $attributedTo) {
            return false;
        }

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
            if (Helpers::validateUrl($candidate) === $actorUrl) {
                return true;
            }
        }

        return false;
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

    private function extractCanonicalUrl(mixed $urlField, string $objectId): ?string
    {
        foreach ($this->extractUrlCandidates($urlField) as $candidate) {
            if (($candidate['mediaType'] ?? null) === 'text/html' && ($candidate['url'] ?? null)) {
                $valid = Helpers::validateUrl($candidate['url']);
                if ($valid) {
                    return $valid;
                }
            }
        }

        if (is_string($urlField)) {
            $valid = Helpers::validateUrl($urlField);
            if ($valid) {
                return $valid;
            }
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

    private function buildAttachments(array $object, string $objectType): array
    {
        if ($objectType === 'article') {
            return [];
        }

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

        if (
            ! is_string($mediaType) ||
            ! in_array($mediaType, $allowedMime) ||
            ! str_starts_with($mediaType, 'video/')
        ) {
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

        $normalized = [
            'type' => 'Video',
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
                    'mediaType' => isset($item['mediaType']) && is_string($item['mediaType'])
                        ? $item['mediaType']
                        : null,
                ];
            }
        }

        return $items;
    }

    private function hasVideoAttachment(array $attachments): bool
    {
        foreach ($attachments as $attachment) {
            $mediaType = $attachment['mediaType'] ?? null;
            if (is_string($mediaType) && str_starts_with($mediaType, 'video/')) {
                return true;
            }
        }

        return false;
    }
}
