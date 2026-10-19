<?php

namespace App\Services;

use App\Models\Instance;
use App\Models\InstanceModerationRule;

class InstanceModerationService
{
    public static function applyPersistedRule(Instance $instance): void
    {
        $rule = InstanceModerationRule::whereDomain($instance->domain)->first();

        if (! $rule) {
            return;
        }

        $instance->banned = (bool) $rule->banned;
        $instance->unlisted = (bool) $rule->unlisted;
        $instance->auto_cw = (bool) $rule->auto_cw;

        if ($rule->notes !== null) {
            $decoded = json_decode($rule->notes, true);
            $instance->notes = json_last_error() === JSON_ERROR_NONE ? $decoded : $rule->notes;
        }
    }

    public static function sync(Instance $instance): void
    {
        if (! self::hasModerationData($instance)) {
            InstanceModerationRule::whereDomain($instance->domain)->delete();

            return;
        }

        InstanceModerationRule::updateOrCreate(
            ['domain' => $instance->domain],
            [
                'banned' => (bool) $instance->banned,
                'unlisted' => (bool) $instance->unlisted,
                'auto_cw' => (bool) $instance->auto_cw,
                'notes' => self::normalizeNotes($instance),
            ]
        );
    }

    public static function hasModerationData(Instance $instance): bool
    {
        return (bool) $instance->banned
            || (bool) $instance->unlisted
            || (bool) $instance->auto_cw
            || self::normalizeNotes($instance) !== null;
    }

    private static function normalizeNotes(Instance $instance): ?string
    {
        $notes = $instance->getRawOriginal('notes');

        if ($notes === null || (is_string($notes) && trim($notes) === '')) {
            return null;
        }

        if (is_array($notes)) {
            $encoded = json_encode($notes);

            return $encoded === '[]' ? null : $encoded;
        }

        return (string) $notes;
    }
}
