<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class DirectMessage extends Model
{
    #[Scope]
    protected function betweenProfiles(Builder $query, int $firstProfileId, int $secondProfileId): Builder
    {
        return $query->where(function (Builder $query) use ($firstProfileId, $secondProfileId) {
            $query->where('from_id', $firstProfileId)->where('to_id', $secondProfileId)
                ->orWhere(function (Builder $query) use ($firstProfileId, $secondProfileId) {
                    $query->where('from_id', $secondProfileId)->where('to_id', $firstProfileId);
                });
        });
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id', 'id');
    }

    public function url()
    {
        return config('app.url').'/account/direct/m/'.$this->status_id;
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'from_id', 'id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'to_id', 'id');
    }

    public function me()
    {
        return Auth::user()->profile->id === $this->from_id;
    }
}
