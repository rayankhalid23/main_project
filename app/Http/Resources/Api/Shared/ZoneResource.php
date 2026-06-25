<?php

namespace App\Http\Resources\Api\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZoneResource extends JsonResource
{
    /**
     * تحويل الموديل إلى مصفوفة JSON منسقة
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'zone_name'  => $this->name,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}