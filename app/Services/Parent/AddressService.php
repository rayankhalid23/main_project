<?php

namespace App\Services\Parent;

use App\Models\Parent\Address;
use Illuminate\Support\Facades\DB;

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
     * تحديث عنوان موجود لولي الأمر
     */
    public function updateAddress(Address $address, int $parentId, array $data): Address
    {
        return DB::transaction(function () use ($address, $parentId, $data) {
            if (isset($data['is_default']) && $data['is_default']) {
                Address::where('parent_id', $parentId)->update(['is_default' => false]);
            }

            $address->update($data);
            return $address;
    });
    }

    /**
     * حذف عنوان
     */
    public function deleteAddress(Address $address): void
    {
        $address->delete();
    }
}