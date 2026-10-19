<?php

namespace App\Casts;

use App\Enums\StatusEnums;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Class StatusEnumCast
 *
 * This class handles the casting of StatusEnums values for Eloquent models.
 *
 * @category Casting
 *
 * @author   Felipe Mateus <eu@felipemateus.com>
 * @license  AGPL-3.0-or-later https://opensource.org/licenses/GPL-3.0
 *
 * @link     https://github.com/eufelipemateus/pixelfed
 */
class StatusEnumCast implements CastsAttributes
{
    /**
     * Cast the stored value into a StatusEnums enum.
     *
     * @param  Model  $model
     * @param  mixed  $value
     */
    public function get($model, string $key, $value, array $attributes): ?StatusEnums
    {
        return StatusEnums::fromValue($value);
    }

    /**
     * Prepare the StatusEnums enum for storage.
     *
     * @param  Model  $model
     * @param  StatusEnums|null  $value
     */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        return $value instanceof StatusEnums ? $value->value() : $value;
    }
}
