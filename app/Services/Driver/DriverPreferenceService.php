<?php

namespace App\Services\Driver;

use App\Models\Driver\Driver;
use App\Enums\driver\DriverShift;
use App\Models\Shared\Zone;
use App\Models\Municipality;
use Illuminate\Support\Facades\DB;
use Exception;

class DriverPreferenceService
{
    public function getPreferences(Driver $driver): Driver
    {
        return $driver->load('zones.subMunicipality.municipality');
    }

    /**
     * تحديث التفضيلات الشاملة مع فحص شرط "البلدية الفرعية الموحدة"
     */
    public function updatePreferences(Driver $driver, array $data): Driver
    {
        return DB::transaction(function () use ($driver, $data) {
            
            $driver->update([
                'shift'             => $data['shift'] ?? $driver->shift,
                'subscription_type' => $data['subscription_type'] ?? $driver->subscription_type,
            ]);

            $zoneIds = $data['zones'] ?? $data['zone_ids'] ?? [];

            if (!empty($zoneIds)) {
                // استخراج معرفات البلديات الفرعية (sub_municipality_id) للمناطق المرسلة
                $subMunicipalityIds = Zone::whereIn('id', $zoneIds)
                    ->pluck('sub_municipality_id')
                    ->unique();

                // إذا كانت النتيجة تحتوي على أكثر من ID، فهذا يعني أن السائق اختار مناطق في بلديات فرعية مختلفة
                if ($subMunicipalityIds->count() > 1) {
                    throw new Exception('عذراً، يجب أن تكون جميع المناطق المختارة تابعة لنفس البلدية الفرعية الموحدة.');
                }
            }
            
            $driver->zones()->sync($zoneIds);
            return $driver->load('zones.subMunicipality.municipality');
        ]);
    }

    /**
     * إضافة منطقة واحدة مع التحقق من مطابقتها للبلدية الفرعية للمناطق الحالية
     */
    public function addZoneToDriver(Driver $driver, int $zoneId): Driver
    {
        $targetZone = Zone::findOrFail($zoneId);
        $currentZones = $driver->zones;

        if ($currentZones->isNotEmpty()) {
            // جلب البلدية الفرعية للمناطق التي يمتلكها السائق حالياً
            $currentSubMunicipalityId = $currentZones->first()->sub_municipality_id;

            // التحقق من أن المنطقة الجديدة تملك نفس الـ sub_municipality_id
            if ($targetZone->sub_municipality_id !== $currentSubMunicipalityId) {
                throw new Exception('لا يمكن إضافة هذه المنطقة؛ لأنها تتبع بلدية فرعية مختلفة عن منطقتك الحالية.');
            }
        }

        $driver->zones()->syncWithoutDetaching([$zoneId]);
        return $driver->load('zones.subMunicipality.municipality');
    }

    public function removeZoneFromDriver(Driver $driver, int $zoneId): Driver
    {
        $driver->zones()->detach($zoneId);
        return $driver->load('zones.subMunicipality.municipality');
    }

    public function getSystemDefaults(): array
    {
        return [
            'available_shifts' => collect(DriverShift::cases())->map(fn($shift) => [
                'value' => $shift->value,
                'label' => $shift->label()
            ]),
            'available_subscription_types' => [
                ['value' => 'daily', 'label' => 'يومي فقط'],
                ['value' => 'monthly', 'label' => 'شهري فقط'],
                ['value' => 'both', 'label' => 'كلاهما (يومي وشهري)']
            ],
            'geography_tree' => Municipality::with('subMunicipalities.zones')->get()->map(fn($municipality) => [
                'id' => $municipality->id,
                'name' => $municipality->name,
                'sub_municipalities' => $municipality->subMunicipalities->map(fn($sub) => [
                    'id' => $sub->id,
                    'name' => $sub->name,
                    'zones' => $sub->zones->map(fn($zone) => [
                        'id' => $zone->id,
                        'name' => $zone->name
                    ])
                ])
            ])
        ];
    }
}