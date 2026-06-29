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
    /**
     * جلب التفضيلات مع العلاقات الهرمية (المنطقة -> البلدية الفرعية -> البلدية الكبرى)
     */
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

            $zoneIds = $data['zones'] ?? [];

            if (!empty($zoneIds)) {
                // استخراج معرفات البلديات الفرعية للمناطق المرسلة للتحقق من تطابقها
                $subMunicipalityIds = Zone::whereIn('id', $zoneIds)
                    ->pluck('sub_municipality_id')
                    ->filter() // استبعاد القيم الفارغة إن وجدت
                    ->unique();

                if ($subMunicipalityIds->count() > 1) {
                    throw new Exception('عذراً، يجب أن تكون جميع المناطق المختارة تابعة لنفس البلدية الفرعية.');
                }
            }
            
            // تحديث العلاقة باستخدام جدول الربط الصحيح 'driver_zone'
            $driver->zones()->sync($zoneIds);
            
            return $driver->fresh(['zones.subMunicipality.municipality']);
        });
    }

    /**
     * إضافة منطقة واحدة مع التحقق من مطابقتها للبلدية الفرعية للمناطق الحالية
     */
    public function addZoneToDriver(Driver $driver, int $zoneId): Driver
    {
        $targetZone = Zone::findOrFail($zoneId);
        $currentZones = $driver->zones;

        if ($currentZones->isNotEmpty()) {
            $currentSubMunicipalityId = $currentZones->first()->sub_municipality_id;

            if ($targetZone->sub_municipality_id !== $currentSubMunicipalityId) {
                throw new Exception('لا يمكن إضافة هذه المنطقة؛ لأنها تتبع بلدية فرعية مختلفة.');
            }
        }

        // إضافة المنطقة بدون التأثير على المناطق الموجودة سابقاً
        $driver->zones()->syncWithoutDetaching([$zoneId]);
        
        return $driver->load('zones.subMunicipality.municipality');
    }

    public function removeZoneFromDriver(Driver $driver, int $zoneId): Driver
    {
        $driver->zones()->detach($zoneId);
        return $driver->load('zones.subMunicipality.municipality');
    }

    /**
     * جلب هيكل البيانات الجغرافي لبناء القوائم المنسدلة
     */
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