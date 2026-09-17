<?php

namespace App\Services;

use App\Models\Status;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class StatusLabelService
{
    public const CACHE_KEY = 'pf:services:status_label:_v0:';

    public static function get(?Status $status): array
    {
        if (! config('instance.label.covid.enabled', false) || ! $status) {
            return ['covid' => false];
        }

        return Cache::remember(self::CACHE_KEY.$status->id, now()->addDays(7), function () use ($status): array {
            if (! $status->caption) {
                return ['covid' => false];
            }

            return [
                'covid' => Str::of(strtolower($status->caption))->contains([
                    'covid', 'corona', 'coronavirus', 'vaccine', 'vaxx', 'vaccination', 'plandemic',
                ]),
            ];
        });
    }
}
