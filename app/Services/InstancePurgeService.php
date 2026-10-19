<?php

namespace App\Services;

use App\Models\Instance;
use App\Models\InstanceModerationRule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class InstancePurgeService
{
    public static function inactivityYears(): int
    {
        return max(1, (int) config('federation.purge_inactive_after_years', 2));
    }

    public static function threshold(): Carbon
    {
        return now()->subYears(self::inactivityYears());
    }

    public static function graceDays(): int
    {
        return max(1, (int) config('federation.purge_inactive_grace_days', 30));
    }

    public static function profileBatchSize(): int
    {
        return min(1000, max(1, (int) config('federation.purge_inactive_profiles_per_run', 100)));
    }

    public static function profileRetentionDays(): int
    {
        return max(0, (int) config('federation.purge_inactive_profile_retention_days', 30));
    }

    public static function profileRetentionThreshold(): Carbon
    {
        return now()->subDays(self::profileRetentionDays());
    }

    public static function inactiveInstancesQuery(?Carbon $threshold = null): Builder
    {
        $threshold = $threshold ?: self::threshold();

        return self::applyInactiveConstraint(Instance::query(), $threshold);
    }

    public static function applyInactiveConstraint(Builder $query, Carbon $threshold): Builder
    {
        return $query
            ->whereNotNull('last_active_at')
            ->where('last_active_at', '<', $threshold)
            ->whereNotNull('nodeinfo_last_fetched')
            ->where('nodeinfo_last_fetched', '<', $threshold)
            ->where('delivery_timeout', true)
            ->where('delivery_failures', '>=', self::deliveryFailureThreshold());
    }

    public static function isStillInactive(int $instanceId, ?Carbon $threshold = null): bool
    {
        $threshold = $threshold ?: self::threshold();

        return self::applyInactiveConstraint(
            Instance::query()->whereKey($instanceId),
            $threshold
        )->exists();
    }

    public static function markCandidate(int $instanceId): bool
    {
        return self::applyInactiveConstraint(
            Instance::query()->whereKey($instanceId),
            self::threshold()
        )
            ->whereNull('purge_candidate_at')
            ->update(['purge_candidate_at' => now()]) === 1;
    }

    public static function isReadyForPurge(int $instanceId): bool
    {
        return self::readyForPurgeQuery($instanceId)->exists();
    }

    public static function beginPurge(int $instanceId): bool
    {
        self::readyForPurgeQuery($instanceId)
            ->whereNull('purge_started_at')
            ->update(['purge_started_at' => now()]);

        return self::isPurgeAuthorized($instanceId);
    }

    public static function isPurgeAuthorized(int $instanceId): bool
    {
        return self::readyForPurgeQuery($instanceId)
            ->whereNotNull('purge_started_at')
            ->exists();
    }

    public static function resetProgress(int $instanceId): void
    {
        Instance::whereKey($instanceId)->update([
            'purge_candidate_at' => null,
            'purge_started_at' => null,
        ]);
    }

    public static function hasModerationData(Instance $instance): bool
    {
        return InstanceModerationService::hasModerationData($instance);
    }

    public static function persistModerationRule(Instance $instance)
    {
        InstanceModerationService::sync($instance);

        return InstanceModerationRule::whereDomain($instance->domain)->first();
    }

    private static function readyForPurgeQuery(int $instanceId): Builder
    {
        return self::applyInactiveConstraint(
            Instance::query()->whereKey($instanceId),
            self::threshold()
        )
            ->whereNotNull('purge_candidate_at')
            ->where('purge_candidate_at', '<=', now()->subDays(self::graceDays()));
    }

    private static function deliveryFailureThreshold(): int
    {
        return max(1, (int) config('federation.activitypub.delivery.failure_threshold', 5));
    }
}
