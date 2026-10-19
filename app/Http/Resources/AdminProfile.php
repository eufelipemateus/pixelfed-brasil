<?php

namespace App\Http\Resources;

use App\Enums\StatusEnums;
use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string|null $domain
 * @property StatusEnums|null $status
 * @property bool $cw
 * @property bool $unlisted
 * @property bool $no_autolink
 */
class AdminProfile extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $res = AccountService::get($this->id, true);
        $res['domain'] = $this->domain;
        $res['status'] = $this->status?->value();
        $res['limits'] = [
            'exist' => $this->cw || $this->unlisted || $this->no_autolink,
            'autocw' => (bool) $this->cw,
            'unlisted' => (bool) $this->unlisted,
            'no_autolink' => (bool) $this->no_autolink,
            'banned' => $this->status === StatusEnums::BANNED,
        ];

        return $res;
    }
}
