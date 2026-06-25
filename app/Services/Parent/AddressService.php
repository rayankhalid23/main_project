<?php

namespace App\Services\Parent;

use App\Models\Parent\Address;
use Illuminate\Support\Facades\DB;
use Exception;

class AddressService
{
    /**
     * جلب كافة عناوين ولي الأمر الحالي
     */
    public function getParentAddresses(int $parentId)
    {
        return Address::where('parent_id', $parentId)->get();
    }

    /**
     * إنشاء عنوان جديد لولي الأمر
     */
    public function createAddress(int $parentId, array $data): Address
    {
        return DB::transaction(function () use ($parentId, $data) {
            $isDefault = isset($data['is_default']) && $data['is_default'];

            // إذا كان هذا أول عنوان لولي الأمر، نجعله ديفلت تلقائياً لحمايته
            $hasAddresses = Address::where('parent_id', $parentId)->exists();
            if (!$hasAddresses) {
                $isDefault = true;
            }

            if ($isDefault) {
                Address::where('parent_id', $parentId)->update(['is_default' => false]);
            }

            return Address::create([
                'parent_id'  => $parentId,
                'label'      => $data['label'],
                'lat'        => $data['lat'],
                'lng'        => $data['lng'],
                'is_default' => $isDefault,
            ]);
        });
    }

   /**
     * تحديث عنوان موجود (يدعم التعديل الجزئي الصارم مع الحماية الكاملة)
     */
    public function updateAddress(Address $address, int $parentId, array $data): Address
    {
        // 1. فحص تكرار الاسم (Label) فقط إذا تم إرساله وكان مختلفاً عن الاسم الحالي
        if (array_key_exists('label', $data) && $data['label'] !== $address->label) {
            $labelExists = Address::where('parent_id', $parentId)
                ->where('label', $data['label'])
                ->where('id', '!=', $address->id)
                ->exists();

            if ($labelExists) {
                throw new \Exception("تعذر التعديل: لديك عنوان آخر مسجل مسبقاً باسم '" . $data['label'] . "'.");
            }
        }

        // 2. فحص تكرار الموقع الجغرافي فقط إذا أُرسلت إحداثيات جديدة وكانت مختلفة عن الحالية
        $newLat = $data['lat'] ?? $address->lat;
        $newLng = $data['lng'] ?? $address->lng;

        if ((array_key_exists('lat', $data) || array_key_exists('lng', $data)) && 
            ($newLat != $address->lat || $newLng != $address->lng)) {
            
            $locationExists = Address::where('parent_id', $parentId)
                ->where('lat', $newLat)
                ->where('lng', $newLng)
                ->where('id', '!=', $address->id)
                ->exists();

            if ($locationExists) {
                throw new \Exception("تعذر التعديل: هذا الموقع الجغرافي يتطابق مع موقع عنوان آخر مضاف لديك بالفعل.");
            }
        }

        // 🌟 [تثبيت الـ ID]: حفظ معرف السجل في متغير ثابت قبل بدء المعاملة لضمان عدم فقده في الذاكرة
        $addressId = $address->id;

        // 3. تنفيذ التعديل الجزئي الفعلي داخل الـ Transaction
        return DB::transaction(function () use ($address, $parentId, $data, $addressId) {
            // إذا تم طلب جعل هذا العنوان افتراضياً
            if (isset($data['is_default']) && $data['is_default']) {
                Address::where('parent_id', $parentId)->update(['is_default' => false]);
            }

            // تحديث الحقول المرسلة فقط دون المساس بالبقية
            $address->update($data);
            
            // 🌟 [الاستعلام الآمن النهائي]: إعادة جلب السجل بالاعتماد على الـ ID الثابت لملء البيانات كاملة للمستخدم
            return Address::withTrashed()->findOrFail($addressId);
        });
    }

    /**
     * حذف العنوان ناعماً مع حماية العنوان الافتراضي
     */
    public function deleteAddress(Address $address): void
    {
        // شبكة أمان: منع حذف العنوان إذا كان هو الافتراضي الحالي ولديه عناوين أخرى
        if ($address->is_default) {
            $hasOtherAddresses = Address::where('parent_id', $address->parent_id)
                ->where('id', '!=', $address->id)
                ->exists();
                
            if ($hasOtherAddresses) {
                throw new Exception("لا يمكنك حذف العنوان الافتراضي، يرجى تعيين عنوان آخر كافتراضي أولاً.");
            }
        }

        // سيقوم لارافل هنا بملء عمود deleted_at بوقت الحذف الحالي بدلاً من مسح السجل
        $address->delete();
    }
}