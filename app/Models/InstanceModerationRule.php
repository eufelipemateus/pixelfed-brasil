<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstanceModerationRule extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'banned' => 'boolean',
        'unlisted' => 'boolean',
        'auto_cw' => 'boolean',
    ];
}
