<?php

/**
 * LabelService.php
 *
 * Service class for handling user label operations, including caching.
 *
 * @category Services
 *
 * @author   Felipe Mateus <eu@felipemateus.com>
 * @license  AGPL-3.0-or-later https://opensource.org/licenses/AGPL-3.0
 *
 * @link     https://github.com/eufelipemateus/pixelfed
 */

namespace App\Services;

use App\Models\UserLabel;
use Illuminate\Support\Facades\Cache;

/**
 * Service class for handling user label operations, including caching.
 *
 * @category Services
 *
 * @author   Felipe Mateus <eu@felipemateus.com>
 * @license  AGPL-3.0-or-later https://opensource.org/licenses/AGPL-3.0
 *
 * @link     https://github.com/eufelipemateus/pixelfed
 */
class LabelService
{
    const CACHE_KEY = 'pf:services:label:';

    /**
     * Retrieve a UserLabel by name, using cache for performance.
     *
     * @param  string  $name  label
     * @return UserLabel|null
     */
    public static function get($name)
    {
        return Cache::remember(
            self::CACHE_KEY.$name,
            86400,
            function () use ($name) {
                return UserLabel::where('name', $name)->first();
            }
        );
    }
}
