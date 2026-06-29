<?php

namespace App\Http\Resources\Api\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverPreferenceResource extends JsonResource
{
    // app/Http/Resources/Api/Driver/DriverPreferenceResource.php

    public function toArray($request): array
    {
        // الحصول على المناطق مع بياناتها (يجب التأكد من وجود العلاقة zones)
        $zones = $this->zones; 
    
        // تجميع المناطق حسب اسم البلدية الفرعية
        $groupedZones = $zones->groupBy('subMunicipality.name')->map(function ($zonesGroup) {
            $subMuni = $zonesGroup->first()->subMunicipality;
            return [
                'municipality_name'     => $subMuni->municipality->name,
                'sub_municipality_name' => $subMuni->name,
                'zones' => $zonesGroup->map(fn($z) => ['id' => $z->id, 'name' => $z->name])
            ];
        });
    
        return [
            'driver_id' => $this->id,
            'shift'     => $this->shift,
            'subscription_type' => $this->subscription_type,
            'coverage' => $groupedZones // هنا تظهر الهيكلية الجديدة
        ];
    }
}