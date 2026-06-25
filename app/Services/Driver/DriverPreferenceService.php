<?php

namespace App\Services\Driver;

use App\Models\Driver\Driver;
use App\Enums\driver\DriverShift;
use App\Models\Shared\Zone;
use Illuminate\Support\Facades\DB;

class DriverPreferenceService
{
    /**
     * 1. جلب التفضيلات الحالية للسائق
     */
    public function getPreferences(Driver $driver): Driver
    {
        return $driver->load('zones');
    }

    /**
     * 2. تحديث التفضيلات الشاملة (الكود السابق والمستقر عندك)
     */
    public function updatePreferences(Driver $driver, array $data): Driver
    {
        return DB::transaction(function () use ($driver, $data) {
            $driver->update(['shift' => $data['shift']]);
            $driver->zones()->sync($data['zones']);
            return $driver->load('zones');
        });
    }

    /**
     * 3. إضافة منطقة واحدة فقط دون المساس بالمناطق القديمة
     */
    public function addZoneToDriver(Driver $driver, int $zoneId): Driver
    {
        // syncWithoutDetaching تمنع تكرار الـ ID وتضيفه لو لم يكن موجوداً
        $driver->zones()->syncWithoutDetaching([$zoneId]);
        return $driver->load('zones');
    }

    /**
     * 4. إزالة منطقة واحدة فقط من تفضيلات السائق
     */
    public function removeZoneFromDriver(Driver $driver, int $zoneId): Driver
    {
        $driver->zones()->detach($zoneId);
        return $driver->load('zones');
    }

    /**
     * 5. جلب الخيارات والبيانات الافتراضية للنظام لبناء قائمة الاختيارات
     */
    public function getSystemDefaults(): array
    {
        return [
            // جلب كافة الفترات الزمنية المتاحة من الـ Enum كـ Key و Value ومسمى عربي
            'available_shifts' => collect(DriverShift::cases())->map(function ($shift) {
                return [
                    'value' => $shift->value,
                    'label' => $shift->label()
                ];
            }),
            // جلب المناطق الجغرافية المتاحة بالنظام حالياً عبر الـ Model
            'available_zones' => Zone::all(['id', 'name'])->map(function ($zone) {
                return [
                    'id'   => $zone->id,
                    'name' => $zone->name
                ];
            })
        ];
    }
}