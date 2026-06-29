<?php

namespace App\Http\Resources\Api\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverPreferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'driver_id'         => $this->id,
            'shift'             => $this->shift->value,
            'shift_txt'         => $this->shift->label(), // النص العربي للفترة من الـ Enum
            'subscription_type' => $this->subscription_type, // الحقل الجديد المضاف
            
            // عرض المناطق الجغرافية مصحوبة ببياناتها الهرمية الكاملة (إيجر لودينج)
            'zones'             => $this->zones->map(function ($zone) {
                return [
                    'zone_id'              => $zone->id,
                    'zone_name'            => $zone->name,
                    'sub_municipality_id'  => $zone->sub_municipality_id,
                    'sub_municipality_name'=> $zone->subMunicipality?->name,
                    'municipality_id'      => $zone->subMunicipality?->municipality_id,
                    'municipality_name'    => $zone->subMunicipality?->municipality?->name,
                ];
            })
        ];
    }
}