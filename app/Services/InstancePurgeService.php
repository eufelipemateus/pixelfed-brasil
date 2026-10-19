<?php

namespace App\Services;

use App\Models\Instance;
use App\Models\InstanceModerationRule;
use App\Models\Profile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

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

    public static function profileDispatchRetryThreshold(): Carbon
    {
        $hours = max(1, (int) config('federation.purge_inactive_profile_retry_hours', 24));

        return now()->subHours($hours);
    }

    public static function profilesReadyForDispatch(string $domain, int $instanceId): Builder
    {
        return self::applyProfileClaimConstraint(
            Profile::query()->where('domain', $domain),
            $instanceId
        );
    }

    public static function profilesReadyForFinalization(string $domain, int $instanceId): Builder
    {
        return self::applyProfileClaimConstraint(
            Profile::onlyTrashed()
                ->where('domain', $domain)
                ->where('deleted_at', '<=', self::profileRetentionThreshold()),
            $instanceId
        );
    }

    public static function claimProfileForPurge(Profile $profile, int $instanceId): bool
    {
        return self::applyProfileClaimConstraint(
            Profile::withTrashed()
                ->where('domain', $profile->domain)
                ->whereKey($profile->id),
            $instanceId
        )
            ->increment('purge_retry_count', 1, [
                'purge_instance_id' => $instanceId,
                'purge_queued_at' => now(),
                'purge_last_error' => null,
            ]) === 1;
    }

    public static function releaseProfileClaim(int $profileId, int $instanceId, ?Throwable $exception = null): void
    {
        Profile::withTrashed()
            ->whereKey($profileId)
            ->where('purge_instance_id', $instanceId)
            ->update([
                'purge_queued_at' => null,
                'purge_last_error' => $exception?->getMessage(),
            ]);
    }

    public static function recordProfileFailure(int $profileId, int $instanceId, Throwable $exception): void
    {
        Profile::withTrashed()
            ->whereKey($profileId)
            ->where('purge_instance_id', $instanceId)
            ->update(['purge_last_error' => $exception->getMessage()]);
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

    public static function isPurgeAuthorizedForProfile(int $instanceId, int $profileId): bool
    {
        $instance = self::readyForPurgeQuery($instanceId)
            ->whereNotNull('purge_started_at')
            ->first(['id', 'domain']);

        if (! $instance) {
            return false;
        }

        $profile = Profile::withTrashed()->find($profileId, ['id', 'domain']);

        return $profile
            && $profile->domain
            && strtolower($profile->domain) === strtolower($instance->domain);
    }

    public static function resetProgress(int $instanceId): void
    {
        $instance = Instance::find($instanceId, ['id', 'domain']);

        if ($instance) {
            Profile::withTrashed()
                ->where('domain', $instance->domain)
                ->where('purge_instance_id', $instanceId)
                ->update([
                    'purge_instance_id' => null,
                    'purge_queued_at' => null,
                    'purge_retry_count' => 0,
                    'purge_last_error' => null,
                ]);
        }

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

    private static function applyProfileClaimConstraint(Builder $query, int $instanceId): Builder
    {
        return $query->where(function (Builder $query) use ($instanceId) {
            $query->whereNull('purge_instance_id')
                ->orWhere('purge_instance_id', '!=', $instanceId)
                ->orWhereNull('purge_queued_at')
                ->orWhere('purge_queued_at', '<=', self::profileDispatchRetryThreshold());
        });
    }
}
