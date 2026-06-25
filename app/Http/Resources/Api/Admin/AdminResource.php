<?php

namespace App\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'full_name'    => $this->user->full_name ?? null,
            'email'        => $this->user->email ?? null,
            'phone_number' => $this->user->phone_number ?? null,
            'avatar_url'   => $this->user->avatar_url ? asset($this->user->avatar_url) : null,
            'is_active'    => (bool) ($this->user->is_active ?? false),
        ];
    }
}