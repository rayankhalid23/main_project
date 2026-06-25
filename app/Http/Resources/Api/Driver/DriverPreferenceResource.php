<?php

namespace App\Http\Resources\Api\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\Shared\ZoneResource;

class DriverPreferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'driver_id' => $this->id,
            'shift'     => $this->shift->value,
            'shift_txt' => $this->shift->label(), // جلب النص العربي من الـ Enum
            'zones'     => ZoneResource::collection($this->whenLoaded('zones'))
        ];
    }
}