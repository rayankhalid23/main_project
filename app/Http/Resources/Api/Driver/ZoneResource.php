<?php

namespace App\Http\Resources\Api\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZoneResource extends JsonResource
{
    /**
     * Transform the resource into an array with hierarchical municipality data.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,
            'sub_municipality' => [
                'id'   => $this->sub_municipality_id,
                'name' => $this->subMunicipality->name ?? null,
                // هنا نقوم بعرض البلدية الكبرى التي تتبع لها البلدية الصغرى
                'municipality' => [
                    'id'   => $this->subMunicipality->municipality->id ?? null,
                    'name' => $this->subMunicipality->municipality->name ?? null,
                ],
            ],
        ];
    }
}