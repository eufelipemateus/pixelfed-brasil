<?php

namespace App\Util\ActivityPub\Handlers;

use App\Status;
use App\Media;
use App\Profile;
use Illuminate\Support\Str;

class VideoHandler
{
    protected $headers;
    protected $profile;
    protected $payload;

    public function __construct($headers, $profile, $payload)
    {
        $this->headers = $headers;
        $this->profile = $profile;
        $this->payload = $payload;
    }

    public function handle()
    {
        $object = $this->payload['object'] ?? $this->payload;
        if (strtolower($object['type'] ?? '') !== 'video') {
            return;
        }
        $actor = $object['actor'] ?? $this->payload['actor'] ?? null;
        $profile = Profile::where('actor', $actor)->first();
        if (! $profile) {
            if (method_exists(Profile::class, 'firstOrCreateByActor')) {
                $profile = Profile::firstOrCreateByActor($actor);
            }
        }
        if (! $profile || $profile->domain == null) {
            return;
        }
        $url = $object['url'] ?? $object['id'] ?? null;
        if ($url && Status::where('uri', $url)->exists()) {
            return;
        }
        $status = new Status();
        $status->remote = true;
        $status->activity_type = $this->payload['type'] ?? null;
        $status->type = 'video';
        $status->content = $object['content'] ?? $object['summary'] ?? $object['name'] ?? '';
        $status->published_at = $object['published'] ?? now();
        $status->visibility = $this->extractVisibility($object);
        $status->profile_id = $profile->id;
        $status->uri = $url;
        $status->save();
        $this->saveMedia($status, $object);
    }

    protected function extractVisibility($object)
    {
        $to = $object['to'] ?? [];
        $cc = $object['cc'] ?? [];
        $aud = array_merge((array)$to, (array)$cc);
        if (in_array('https://www.w3.org/ns/activitystreams#Public', $aud)) {
            return 'public';
        }
        return 'unlisted';
    }

    protected function resolveUserId($actor)
    {
        if (!$actor) return null;
        $profile = Profile::where('actor', $actor)->first();
        if ($profile) return $profile->user_id;
        if (method_exists(Profile::class, 'firstOrCreateByActor')) {
            $profile = Profile::firstOrCreateByActor($actor);
            return $profile ? $profile->user_id : null;
        }
        return null;
    }

    protected function saveMedia($status, $object)
    {
        $mediaItems = $this->extractMedia($object);
        foreach ($mediaItems as $media) {
            $mediaModel = new Media();
            $mediaModel->status_id = $status->id;
            $mediaModel->type = $media['type'];
            $mediaModel->url = $media['url'];
            $mediaModel->mime = $media['mime_type'];
            $mediaModel->remote_media = true;
            $mediaModel->save();
        }
    }

    protected function extractMedia($object)
    {
        $media = [];
        if (!empty($object['attachment']) && is_array($object['attachment'])) {
            foreach ($object['attachment'] as $att) {
                $media = array_merge($media, $this->normalizeMedia($att));
            }
        }
        if (!empty($object['url'])) {
            $media = array_merge($media, $this->normalizeMedia($object['url']));
        }
        if (!empty($object['image'])) {
            $media = array_merge($media, $this->normalizeMedia($object['image'], 'image'));
        }
        if (!empty($object['icon'])) {
            $media = array_merge($media, $this->normalizeMedia($object['icon'], 'image'));
        }
        if (!empty($object['preview'])) {
            $media = array_merge($media, $this->normalizeMedia($object['preview']));
        }
        return $media;
    }

    protected function normalizeMedia($item, $forceType = null)
    {
        $result = [];
        if (is_array($item) && isset($item['url'])) {
            $url = is_array($item['url']) ? $item['url'][0] : $item['url'];
            $type = $forceType ?? $this->guessType($item);
            $mime = $item['mediaType'] ?? null;
            $result[] = [
                'url' => $url,
                'type' => $type,
                'mime_type' => $mime,
            ];
        } elseif (is_string($item)) {
            $type = $forceType ?? $this->guessType(['url' => $item]);
            $result[] = [
                'url' => $item,
                'type' => $type,
                'mime_type' => null,
            ];
        }
        return $result;
    }

    protected function guessType($item)
    {
        $mime = $item['mediaType'] ?? '';
        if (Str::startsWith($mime, 'image/')) return 'image';
        if (Str::startsWith($mime, 'audio/')) return 'audio';
        if (Str::startsWith($mime, 'video/')) return 'video';
        $url = $item['url'] ?? '';
        if (is_array($url)) $url = $url[0];
        if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $url)) return 'image';
        if (preg_match('/\.(mp3|ogg|wav)$/i', $url)) return 'audio';
        if (preg_match('/\.(mp4|webm|mov)$/i', $url)) return 'video';
        return 'video';
    }
}
