<?php

namespace App\Jobs\StatusPipeline;

use App\Media;
use App\Models\StatusEdit;
use App\ModLog;
use App\Profile;
use App\Services\SanitizeService;
use App\Services\StatusService;
use App\Status;
use App\Util\ActivityPub\Helpers;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StatusRemoteUpdatePipeline implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $activity;

    /**
     * Create a new job instance.
     */
    public function __construct($activity)
    {
        $this->activity = $activity;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $payload = $this->activity;
        $activity = isset($payload['object']) && is_array($payload['object']) ? $payload['object'] : null;
        $normalized = isset($payload['normalized']) && is_array($payload['normalized']) ? $payload['normalized'] : null;

        // Verify activity exists and has required fields
        if (! $activity) {
            Log::info('StatusRemoteUpdatePipeline: Activity not found, skipping job');

            return;
        }
        if (! isset($activity['id'])) {
            Log::info('StatusRemoteUpdatePipeline: Invalid activity data, skipping job');

            return;
        }

        $status = Status::with('media')->whereObjectUrl($activity['id'])->first();
        if (! $status) {
            $status = Status::with('media')->where('url', $activity['id'])->first();
        }
        if (! $status) {
            Log::info("StatusRemoteUpdatePipeline: Status not found for activity {$activity['id']}, skipping job");

            return;
        }

        try {
            $this->createPreviousEdit($status);
            $this->updateMedia($status, $activity, $normalized);
            $this->updateImmediateAttributes($status, $activity);
            $this->updateStatusType($status, $normalized);
            $this->createEdit($status, $activity);
        } catch (\Exception $e) {
            Log::warning(
                "StatusRemoteUpdatePipeline: Failed to update status {$status->id}: ".$e->getMessage()
            );
            throw $e;
        }
    }

    protected function createPreviousEdit($status)
    {
        try {
            if (! $status->edits()->count()) {
                StatusEdit::create([
                    'status_id' => $status->id,
                    'profile_id' => $status->profile_id,
                    'caption' => $status->caption,
                    'spoiler_text' => $status->cw_summary,
                    'is_nsfw' => $status->is_nsfw,
                    'ordered_media_attachment_ids' => $status->media()->orderBy('order')->pluck('id')->toArray(),
                    'created_at' => $status->created_at,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning(
                'StatusRemoteUpdatePipeline: Failed to create previous edit for status '.
                $status->id.': '.
                $e->getMessage()
            );
            throw $e;
        }
    }

    protected function updateMedia($status, $activity, ?array $normalized)
    {
        if (! isset($activity['attachment']) && ! isset($normalized['attachment'])) {
            return;
        }

        $attachments = $normalized['attachment'] ?? $activity['attachment'] ?? [];
        if (! is_array($attachments)) {
            return;
        }

        $nm = collect($attachments)
            ->filter(function ($item) {
                return isset($item['type'], $item['mediaType'], $item['url']) &&
                    in_array($item['type'], ['Document', 'Image', 'Video']) &&
                    in_array($item['mediaType'], explode(',', config_cache('pixelfed.media_types')));
            })
            ->values();

        $ogm = $status->media->count() ? $status->media()->orderBy('order')->get() : collect([]);

        // Skip when no media
        if (! $ogm->count() && ! $nm->count()) {
            return;
        }

        DB::transaction(function () use ($status, $nm, $ogm) {
            if ($ogm->count()) {
                $status->media()->update(['status_id' => null]);
            }

            $newMedia = [];
            foreach ($nm as $key => $n) {
                $newMedia[] = $this->persistRemoteMedia($status, $n, $key + 1);
            }

            foreach ($newMedia as $media) {
                if ($media instanceof Media) {
                    $media->save();
                }
            }
        });
    }

    protected function persistRemoteMedia(Status $status, array $attachment, int $order): Media
    {
        $m = new Media;
        $m->status_id = $status->id;
        $m->profile_id = $status->profile_id;
        $m->remote_media = true;
        $m->media_path = $attachment['url'];
        $m->mime = $attachment['mediaType'];
        $m->size = null;
        $m->caption = isset($attachment['name']) && ! empty($attachment['name'])
            ? app(SanitizeService::class)->html($attachment['name'])
            : null;
        $m->remote_url = $attachment['url'];
        $m->blurhash = isset($attachment['blurhash']) && (strlen($attachment['blurhash']) < 50)
            ? $attachment['blurhash']
            : null;
        $m->width = isset($attachment['width']) && ! empty($attachment['width']) ? (int) $attachment['width'] : null;
        $m->height = isset($attachment['height']) && ! empty($attachment['height'])
            ? (int) $attachment['height']
            : null;
        $m->skip_optimize = true;
        $m->order = $order;

        if (isset($attachment['thumbnail_url']) && is_string($attachment['thumbnail_url'])) {
            $m->thumbnail_url = $attachment['thumbnail_url'];
        }

        return $m;
    }

    public static function htmlToPlainTextWithLineBreaks(string $html): string
    {
        // Força UTF-8 e normaliza quebras
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Converte <br> e </p> para \n
        $html = preg_replace(['/<br\s*\/?>/i', '/<\/p>/i'], "\n", $html);

        // Remove o restante das tags
        $text = strip_tags($html);

        // Normaliza quebras consecutivas
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }

    protected function updateImmediateAttributes($status, $activity)
    {
        if (isset($activity['content'])) {
            $cleanedCaption = app(SanitizeService::class)->html($activity['content']);
            $status->caption = strip_tags($cleanedCaption);
        }

        if (isset($activity['sensitive'])) {
            if ((bool) $activity['sensitive'] == false) {
                $status->is_nsfw = false;
                $exists = ModLog::whereObjectType('App\Status::class')
                    ->whereObjectId($status->id)
                    ->whereAction('admin.status.moderate')
                    ->exists();
                if ($exists == true) {
                    $status->is_nsfw = true;
                }
                $profile = Profile::find($status->profile_id);
                if (! $profile || $profile->cw == true) {
                    $status->is_nsfw = true;
                }
            } else {
                $status->is_nsfw = true;
            }
        }

        if (isset($activity['summary'])) {
            $status->cw_summary = app(SanitizeService::class)->html($activity['summary']);
        } else {
            $status->cw_summary = null;
        }

        $status->edited_at = now();
        $status->save();
        StatusService::del($status->id);
    }

    protected function updateStatusType(Status $status, ?array $normalized): void
    {
        if (! $normalized) {
            return;
        }

        if (isset($normalized['status_type']) && $status->type !== $normalized['status_type']) {
            $status->type = $normalized['status_type'];
        }

        if (isset($normalized['object_type'])) {
            $status->entities = Helpers::mergeStatusEntities($status, [
                'ap_object_type' => $normalized['object_type'],
            ]);
        }

        $status->save();
    }

    protected function createEdit($status, $activity)
    {
        $cleaned = isset($activity['content']) ? app(SanitizeService::class)->html($activity['content']) : null;
        $spoiler_text = isset($activity['summary']) ? app(SanitizeService::class)->html($activity['summary']) : null;
        $sensitive = isset($activity['sensitive']) ? $activity['sensitive'] : null;
        $mids = $status->media()->count() ? $status->media()->orderBy('order')->pluck('id')->toArray() : null;
        StatusEdit::create([
            'status_id' => $status->id,
            'profile_id' => $status->profile_id,
            'caption' => $cleaned,
            'spoiler_text' => $spoiler_text,
            'is_nsfw' => $sensitive,
            'ordered_media_attachment_ids' => $mids,
        ]);
    }
}
