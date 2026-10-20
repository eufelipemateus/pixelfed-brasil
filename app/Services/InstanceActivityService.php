<?php

namespace App\Services;

use App\Models\Instance;
use App\Models\Profile;
use Illuminate\Support\Facades\DB;

class InstanceActivityService
{
    public static function record(?string $domain): void
    {
        if ($domain === null) {
            return;
        }

        $domain = strtolower(trim($domain));

        if ($domain === '') {
            return;
        }

        $instanceId = Instance::firstOrCreate(['domain' => $domain])->id;

        DB::transaction(function () use ($domain, $instanceId) {
            $instance = Instance::whereKey($instanceId)->lockForUpdate()->firstOrFail();

            if ($instance->purge_candidate_at !== null || $instance->purge_started_at !== null) {
                Profile::withTrashed()
                    ->where('domain', $domain)
                    ->where('purge_instance_id', $instance->id)
                    ->update([
                        'purge_instance_id' => null,
                        'purge_generation' => null,
                        'purge_queued_at' => null,
                        'purge_retry_count' => 0,
                        'purge_last_error' => null,
                    ]);
            }

            if (
                $instance->last_active_at?->gte(now()->subHour())
                && $instance->purge_candidate_at === null
                && $instance->purge_started_at === null
                && ! $instance->delivery_timeout
                && (int) $instance->delivery_failures === 0
            ) {
                return;
            }

            $instance->forceFill([
                'last_active_at' => now(),
                'purge_candidate_at' => null,
                'purge_started_at' => null,
                'purge_generation' => null,
                'delivery_failures' => 0,
                'delivery_timeout' => false,
                'delivery_next_after' => null,
            ])->save();
        });
    }
}
