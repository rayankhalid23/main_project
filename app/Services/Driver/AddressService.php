<?php

namespace App\Services\Driver;

use App\Models\Driver\Address; // 🚀 الاستيراد من مجلد السائق الجديد
use Illuminate\Support\Facades\DB;
use Exception;

class AddressService
{
    public function getDriverAddresses(int $driverId)
    {
        return Address::where('driver_id', $driverId)->get();
    }

    public function createAddress(int $driverId, array $data): Address
    {
        return DB::transaction(function () use ($driverId, $data) {
            $isDefault = isset($data['is_default']) && $data['is_default'];

            $hasAddresses = Address::where('driver_id', $driverId)->exists();
            if (!$hasAddresses) {
                $isDefault = true;
            }

            if ($isDefault) {
                Address::where('driver_id', $driverId)->update(['is_default' => false]);
            }

            return Address::create([
                'driver_id'  => $driverId,
                'label'      => $data['label'],
                'lat'        => $data['lat'],
                'lng'        => $data['lng'],
                'is_default' => $isDefault,
            ]);
        });
    }

    public function updateAddress(Address $address, int $driverId, array $data): Address
    {
        if (array_key_exists('label', $data) && $data['label'] !== $address->label) {
            $labelExists = Address::where('driver_id', $driverId)
                ->where('label', $data['label'])
                ->where('id', '!=', $address->id)
                ->exists();

            if ($labelExists) {
                throw new \Exception("تعذر التعديل: لديك عنوان آخر مسجل مسبقاً باسم '" . $data['label'] . "'.");
            }
        }

        $newLat = $data['lat'] ?? $address->lat;
        $newLng = $data['lng'] ?? $address->lng;

        if ((array_key_exists('lat', $data) || array_key_exists('lng', $data)) && 
            ($newLat != $address->lat || $newLng != $address->lng)) {
            
            $locationExists = Address::where('driver_id', $driverId)
                ->where('lat', $newLat)
                ->where('lng', $newLng)
                ->where('id', '!=', $address->id)
                ->exists();

            if ($locationExists) {
                throw new \Exception("تعذر التعديل: هذا الموقع الجغرافي يتطابق مع موقع عنوان آخر مضاف لديك بالفعل.");
            }
        }

        $addressId = $address->id;

        return DB::transaction(function () use ($address, $driverId, $data, $addressId) {
            if (isset($data['is_default']) && $data['is_default']) {
                Address::where('driver_id', $driverId)->update(['is_default' => false]);
            }

            $address->update($data);
            
            return Address::withTrashed()->findOrFail($addressId);
        });
    }

    public function deleteAddress(Address $address): void
    {
        if ($address->is_default) {
            $hasOtherAddresses = Address::where('driver_id', $address->driver_id)
                ->where('id', '!=', $address->id)
                ->exists();
                
            if ($hasOtherAddresses) {
                throw new Exception("لا يمكنك حذف العنوان الافتراضي، يرجى تعيين عنوان آخر كافتراضي أولاً.");
            }
        }

        $address->delete();
    }
}