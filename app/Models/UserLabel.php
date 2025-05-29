<?php
/**
 * UserLabel model file.
 *
 * This file contains the UserLabel model definition for the application.
 *
 * @category Models
 * @package  App\Models
 * @author   Felipe Mateus <eu@felipemateus.com>
 * @license  AGPL-3.0-or-later https://opensource.org/licenses/GPL-3.0
 * @link     https://github.com/eufelipemateus/pixelfed
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserLabel
 *
 * Represents a label assigned to a user.
 *
 * @category Models
 * @package  App\Models
 * @author   Felipe Mateus <eu@felipemateus.com>
 * @license  AGPL-3.0-or-later https://opensource.org/licenses/GPL-3.0
 * @link     https://github.com/eufelipemateus/pixelfed
 */
class UserLabel extends Model
{
    protected $table = 'users_labels';

    protected $fillable = [
        'name',
        'background_color',
        'text_color'
    ];

    protected $casts = [
        'background_color' => 'string',
        'text_color' => 'string'
    ];

    protected $visible = [
        'label',
        'background_color',
        'text_color'
    ];
}
