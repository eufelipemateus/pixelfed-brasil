<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'group_id',
    ];

    protected function casts(): array
    {
        return [
            'limits' => 'json',
            'metadata' => 'json',
        ];
    }
}
