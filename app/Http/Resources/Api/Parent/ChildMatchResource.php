<?php

namespace App\Http\Resources\Api\Parent;

use Illuminate\Http\Resources\Json\JsonResource;

class ChildMatchResource extends JsonResource
{
    public function toArray($request)
    {
        // حساب المسافة بين المنزل (address) والمدرسة (school)
        $distanceKm = 0;
        if ($this->address && $this->school) {
            $distanceKm = $this->calculateHaversineDistance(
                $this->address->lat, $this->address->lng,
                $this->school->lat, $this->school->lng
            );
        }

        return [
            'id'                 => $this->id,
            'full_name'          => $this->full_name,
            'school_name'        => $this->school->name ?? null,
            'distance_to_school' => round($distanceKm, 2) . ' km', // المسافة مقربة لرقمين
        ];
    }

    /**
     * معادلة رياضية لحساب المسافة بدقة بين نقطتين جغرافيتين
     */
    private function calculateHaversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // نصف قطر الأرض بالكيلومتر
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
}