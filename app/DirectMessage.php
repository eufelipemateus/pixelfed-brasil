<?php

namespace App;

use App\Services\DirectMessageService;
use Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class DirectMessage extends Model
{
    protected static function booted(): void
    {
        static::creating(function (DirectMessage $dm) {
            if (! Status::whereKey($dm->status_id)->exists()) {
                throw new LogicException('A direct message must reference an existing status.');
            }
        });

        static::saved(function (DirectMessage $dm) {
            DirectMessageService::updateConversation($dm);
        });

        static::deleted(function (DirectMessage $dm) {
            DirectMessageService::reconcileBetween($dm->from_id, $dm->to_id);
        });
    }

    public function scopeBetweenProfiles(Builder $query, $firstProfileId, $secondProfileId): Builder
    {
        return $query->where(function (Builder $query) use ($firstProfileId, $secondProfileId) {
            $query->where(function (Builder $query) use ($firstProfileId, $secondProfileId) {
                $query->where('from_id', $firstProfileId)
                    ->where('to_id', $secondProfileId);
            })->orWhere(function (Builder $query) use ($firstProfileId, $secondProfileId) {
                $query->where('from_id', $secondProfileId)
                    ->where('to_id', $firstProfileId);
            });
        });
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id', 'id');
    }

    public function url()
    {
        return config('app.url').'/account/direct/m/'.$this->status_id;
    }

    public function author()
    {
        return $this->belongsTo(Profile::class, 'from_id', 'id');
    }

    public function recipient()
    {
        return $this->belongsTo(Profile::class, 'to_id', 'id');
    }

    public function me()
    {
        return Auth::user()->profile->id === $this->from_id;
    }
}
