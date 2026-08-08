<?php

namespace App\Services;

use App\DirectMessage;
use App\Models\Conversation;
use App\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DirectMessageService
{
    public static function updateConversation(DirectMessage $dm, bool $force = false): void
    {
        if (! $force && Conversation::whereFromId($dm->from_id)
            ->whereToId($dm->to_id)
            ->where('dm_id', '>', $dm->id)
            ->exists()) {
            return;
        }

        Conversation::updateOrInsert(
            ['from_id' => $dm->from_id, 'to_id' => $dm->to_id],
            [
                'dm_id' => $dm->id,
                'status_id' => $dm->status_id,
                'type' => $dm->type,
                'is_hidden' => $dm->is_hidden,
                'updated_at' => $dm->updated_at ?? now(),
            ]
        );
    }

    public static function reconcileDirection($fromId, $toId): ?DirectMessage
    {
        $latest = DirectMessage::query()
            ->where('from_id', $fromId)
            ->where('to_id', $toId)
            ->whereHas('status')
            ->orderByDesc('id')
            ->first();

        if (! $latest) {
            Conversation::whereFromId($fromId)->whereToId($toId)->delete();

            return null;
        }

        self::updateConversation($latest, true);

        return $latest;
    }

    public static function reconcileBetween($firstProfileId, $secondProfileId): void
    {
        self::reconcileDirection($firstProfileId, $secondProfileId);
        self::reconcileDirection($secondProfileId, $firstProfileId);
    }

    public static function deleteDm(DirectMessage $dm): void
    {
        $fromId = $dm->from_id;
        $toId = $dm->to_id;

        DB::transaction(function () use ($dm, $fromId, $toId) {
            $notifications = Notification::whereItemType('App\\DirectMessage')
                ->whereItemId($dm->id)
                ->get();
            foreach ($notifications as $notification) {
                NotificationService::del($notification->profile_id, $notification->id);
                $notification->forceDeleteQuietly();
            }
            $dm->delete();
            self::reconcileBetween($fromId, $toId);
        });
    }

    public static function logOrphan(DirectMessage $dm, ?int $conversationId = null): void
    {
        Log::warning('Direct message references a missing status', [
            'dm_id' => $dm->id,
            'status_id' => $dm->status_id,
            'conversation_id' => $conversationId,
            'from_id' => $dm->from_id,
            'to_id' => $dm->to_id,
        ]);
    }
}
