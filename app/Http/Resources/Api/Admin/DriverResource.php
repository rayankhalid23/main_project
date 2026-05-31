<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    public array $with = ['status' => true];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'national_id' => $this->national_id,
            'license_number' => $this->license_number,
            'license_expiry' => $this->license_expiry,
            'status' => $this->status,
            'user' => [
                'id' => $this->user->id ?? null,
                'full_name' => $this->user->full_name ?? null,
                'phone_number' => $this->user->phone_number ?? null,
                'is_active' => $this->user->is_active ?? 0,
                'avatar_url' => $this->user->avatar_url ?? null,
            ],
            'rating_avg' => (float) $this->rating_avg,
            'completed_trips_count' => (int) $this->completed_trips_count,
        ];
    }
}