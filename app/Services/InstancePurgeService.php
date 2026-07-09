<?php

namespace App\Services;

use App\Instance;
use App\Models\InstanceModerationRule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class InstancePurgeService
{
    private static ?bool $hasLastActiveAtColumn = null;

    public static function inactivityYears(): int
    {
        return max(1, (int) config('federation.purge_inactive_after_years', 2));
    }

    public static function threshold(): Carbon
    {
        return now()->subYears(self::inactivityYears());
    }

    public static function inactiveInstancesQuery(?Carbon $threshold = null): Builder
    {
        $threshold = $threshold ?: self::threshold();

        return self::applyInactiveConstraint(Instance::query(), $threshold);
    }

    public static function applyInactiveConstraint(Builder $query, Carbon $threshold): Builder
    {
        $query->whereNotNull('nodeinfo_last_fetched');

        if (self::hasLastActiveAtColumn()) {
            $query->whereNotNull('last_active_at')
                ->where('last_active_at', '<', $threshold);

            return $query;
        }

        return $query->where('nodeinfo_last_fetched', '<', $threshold);
    }

    public static function isStillInactive(int $instanceId, ?Carbon $threshold = null): bool
    {
        $threshold = $threshold ?: self::threshold();

        return self::applyInactiveConstraint(
            Instance::query()->whereKey($instanceId),
            $threshold
        )->exists();
    }

    public static function hasModerationData(Instance $instance): bool
    {
        return (bool) $instance->banned
            || (bool) $instance->unlisted
            || (bool) $instance->auto_cw
            || self::normalizeNotes($instance) !== null;
    }

    public static function persistModerationRule(Instance $instance)
    {
        return InstanceModerationRule::updateOrCreate(
            ['domain' => $instance->domain],
            [
                'banned' => (bool) $instance->banned,
                'unlisted' => (bool) $instance->unlisted,
                'auto_cw' => (bool) $instance->auto_cw,
                'notes' => self::normalizeNotes($instance),
            ]
        );
    }

    private static function normalizeNotes(Instance $instance): ?string
    {
        $notes = $instance->getRawOriginal('notes');

        if ($notes === null) {
            return null;
        }

        if (is_string($notes) && trim($notes) === '') {
            return null;
        }

        if (is_array($notes)) {
            $encoded = json_encode($notes);

            return $encoded === '[]' ? null : $encoded;
        }

        return (string) $notes;
    }

    private static function hasLastActiveAtColumn(): bool
    {
        if (self::$hasLastActiveAtColumn !== null) {
            return self::$hasLastActiveAtColumn;
        }

        self::$hasLastActiveAtColumn = Schema::hasColumn('instances', 'last_active_at');

        return self::$hasLastActiveAtColumn;
    }
}
