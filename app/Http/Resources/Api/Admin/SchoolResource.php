<?php

namespace App\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'lat'          => (float) $this->lat,
            'lng'          => (float) $this->lng,
            'address_text' => $this->address_text,
            'status'       => $this->status, // يظهر ليعرف المطور حالة المدرسة
        ];
    }
}