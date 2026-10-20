<?php

namespace App\Services;

use App\Models\Instance;
use App\Models\InstanceModerationRule;
use App\Models\Profile;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    public static function statusBatchSize(): int
    {
        return min(1000, max(1, (int) config('federation.purge_inactive_statuses_per_run', 100)));
    }

    public static function mediaBatchSize(): int
    {
        return min(1000, max(1, (int) config('federation.purge_inactive_media_per_run', 100)));
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

    public static function profilesReadyForDispatch(string $domain, int $instanceId, string $generation): Builder
    {
        return self::applyProfileClaimConstraint(
            Profile::query()->where('domain', $domain),
            $instanceId,
            $generation
        );
    }

    public static function profilesReadyForFinalization(string $domain, int $instanceId, string $generation): Builder
    {
        return self::applyProfileClaimConstraint(
            Profile::onlyTrashed()
                ->where('domain', $domain)
                ->where('deleted_at', '<=', self::profileRetentionThreshold()),
            $instanceId,
            $generation
        );
    }

    public static function claimProfileForPurge(Profile $profile, int $instanceId, string $generation): bool
    {
        if (! self::isPurgeAuthorized($instanceId, $generation)) {
            return false;
        }

        return self::applyProfileClaimConstraint(
            Profile::withTrashed()
                ->where('domain', $profile->domain)
                ->whereKey($profile->id),
            $instanceId,
            $generation
        )
            ->increment('purge_retry_count', 1, [
                'purge_instance_id' => $instanceId,
                'purge_generation' => $generation,
                'purge_queued_at' => now(),
                'purge_last_error' => null,
            ]) === 1;
    }

    public static function releaseProfileClaim(
        int $profileId,
        int $instanceId,
        string $generation,
        ?Throwable $exception = null
    ): void {
        Profile::withTrashed()
            ->whereKey($profileId)
            ->where('purge_instance_id', $instanceId)
            ->where('purge_generation', $generation)
            ->update([
                'purge_queued_at' => null,
                'purge_last_error' => $exception?->getMessage(),
            ]);
    }

    public static function recordProfileFailure(
        int $profileId,
        int $instanceId,
        string $generation,
        Throwable $exception
    ): void {
        Profile::withTrashed()
            ->whereKey($profileId)
            ->where('purge_instance_id', $instanceId)
            ->where('purge_generation', $generation)
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

    public static function beginPurge(int $instanceId): ?string
    {
        return DB::transaction(function () use ($instanceId) {
            $instance = self::readyForPurgeQuery($instanceId)
                ->lockForUpdate()
                ->first();

            if (! $instance) {
                return null;
            }

            if ($instance->purge_started_at === null || $instance->purge_generation === null) {
                $instance->forceFill([
                    'purge_started_at' => now(),
                    'purge_generation' => (string) Str::uuid(),
                ])->save();
            }

            return $instance->purge_generation;
        });
    }

    public static function isPurgeAuthorized(int $instanceId, string $generation): bool
    {
        return self::readyForPurgeQuery($instanceId)
            ->whereNotNull('purge_started_at')
            ->where('purge_generation', $generation)
            ->exists();
    }

    public static function isPurgeAuthorizedForProfile(
        int $instanceId,
        int $profileId,
        string $generation
    ): bool {
        $instance = self::readyForPurgeQuery($instanceId)
            ->whereNotNull('purge_started_at')
            ->where('purge_generation', $generation)
            ->first(['id', 'domain']);

        if (! $instance) {
            return false;
        }

        $profile = Profile::withTrashed()
            ->whereKey($profileId)
            ->where('purge_instance_id', $instanceId)
            ->where('purge_generation', $generation)
            ->first(['id', 'domain']);

        return $profile
            && $profile->domain
            && strtolower($profile->domain) === strtolower($instance->domain);
    }

    /**
     * Atomically validates a purge generation and marks one destructive step.
     * Activity recording locks the same instance row, so either reactivation
     * wins and this step is rejected, or this step becomes the linearization
     * point before database or storage cleanup continues.
     */
    public static function authorizeDestructiveProfileStep(
        int $instanceId,
        int $profileId,
        string $generation,
        Closure $markDestructive
    ): bool {
        return DB::transaction(function () use ($instanceId, $profileId, $generation, $markDestructive) {
            $instance = self::readyForPurgeQuery($instanceId)
                ->whereNotNull('purge_started_at')
                ->where('purge_generation', $generation)
                ->lockForUpdate()
                ->first(['id', 'domain']);

            if (! $instance) {
                return false;
            }

            $profile = Profile::withTrashed()
                ->whereKey($profileId)
                ->where('purge_instance_id', $instanceId)
                ->where('purge_generation', $generation)
                ->lockForUpdate()
                ->first();

            if (
                ! $profile
                || ! $profile->domain
                || strtolower($profile->domain) !== strtolower($instance->domain)
            ) {
                return false;
            }

            $result = $markDestructive($profile);

            return $result !== false;
        });
    }

    public static function authorizeDestructiveInstanceStep(
        int $instanceId,
        string $generation,
        Closure $destructiveStep
    ): bool {
        return DB::transaction(function () use ($instanceId, $generation, $destructiveStep) {
            $instance = self::readyForPurgeQuery($instanceId)
                ->whereNotNull('purge_started_at')
                ->where('purge_generation', $generation)
                ->lockForUpdate()
                ->first();

            if (! $instance) {
                return false;
            }

            $result = $destructiveStep($instance);

            return $result !== false;
        });
    }

    public static function resetProgress(int $instanceId): void
    {
        DB::transaction(function () use ($instanceId) {
            $instance = Instance::whereKey($instanceId)
                ->lockForUpdate()
                ->first(['id', 'domain']);

            if (! $instance) {
                return;
            }

            Profile::withTrashed()
                ->where('domain', $instance->domain)
                ->where('purge_instance_id', $instanceId)
                ->update([
                    'purge_instance_id' => null,
                    'purge_generation' => null,
                    'purge_queued_at' => null,
                    'purge_retry_count' => 0,
                    'purge_last_error' => null,
                ]);
            Instance::whereKey($instanceId)->update([
                'purge_candidate_at' => null,
                'purge_started_at' => null,
                'purge_generation' => null,
            ]);
        });
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

    private static function applyProfileClaimConstraint(
        Builder $query,
        int $instanceId,
        string $generation
    ): Builder {
        return $query->where(function (Builder $query) use ($instanceId, $generation) {
            $query->whereNull('purge_instance_id')
                ->orWhere('purge_instance_id', '!=', $instanceId)
                ->orWhereNull('purge_generation')
                ->orWhere('purge_generation', '!=', $generation)
                ->orWhereNull('purge_queued_at')
                ->orWhere('purge_queued_at', '<=', self::profileDispatchRetryThreshold());
        });
    }
}
