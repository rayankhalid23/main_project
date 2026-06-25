<?php

namespace App\Services\Shared;

use App\Models\Shared\Zone;
use Illuminate\Support\Facades\DB;
use Exception;

class ZoneService
{
    /**
     * جلب كافة المناطق
     */
    public function getAllZones()
    {
        return Zone::orderBy('name', 'asc')->get();
    }

    /**
     * إضافة منطقة جديدة مع منع التكرار
     */
    public function createZone(array $data): Zone
    {
        $exists = Zone::where('name', $data['name'])->exists();
        if ($exists) {
            throw new Exception("هذه المنطقة مسجلة مسبقاً في النظام.");
        }

        return Zone::create(['name' => $data['name']]);
    }

    /**
     * تعديل اسم منطقة موجودة
     */
    public function updateZone(Zone $zone, array $data): Zone
    {
        $exists = Zone::where('name', $data['name'])->where('id', '!=', $zone->id)->exists();
        if ($exists) {
            throw new Exception("تعذر التعديل: هناك منطقة أخرى تحمل نفس الاسم.");
        }

        $zone->update(['name' => $data['name']]);
        return $zone;
    }

    /**
     * حذف منطقة نهائياً مع حماية العلاقات
     */
    public function deleteZone(Zone $zone): void
    {
        // شبكة أمان: فحص ما إذا كان هناك سائقون مسجلون في هذه المنطقة حالياً
        $hasDrivers = DB::table('driver_zone')->where('zone_id', $zone->id)->exists();
        
        if ($hasDrivers) {
            throw new Exception("لا يمكن حذف هذه المنطقة لأنها تحتوي على سائقين نشطين حالياً، قم بنقل السائقين أولاً.");
        }

        $zone->delete();
    }
}