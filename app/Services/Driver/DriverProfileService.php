<?php

namespace App\Services\Driver;

use App\Models\User;
use App\Models\Driver\Driver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class DriverProfileService
{
    /**
     * تحديث بيانات السائق بشكل جزئي واحترافي
     * * @param User $user المستخدم الحالي (الذي يمثل السائق)
     * @param array $data البيانات المفلترة والمراد تحديثها
     * @return Driver
     * @throws Exception
     */
    public function updateProfile(User $user, array $data): Driver
    {
        DB::beginTransaction();

        try {
            // 1. تحديث بيانات جدول Users
            // استخدام array_intersect_key يضمن أخذ الحقول المسموحة فقط وتجاهل الباقي
            $userData = array_intersect_key($data, array_flip(['full_name', 'phone_number', 'avatar_path']));
            
            if (isset($data['password'])) {
                $userData['password_hash'] = Hash::make($data['password']);
            }

            // تحديث ذكي: تحديث فقط الحقول التي تم إرسالها (التعديل الجزئي)
            if (!empty($userData)) {
                $user->update($userData);
            }

            // 2. تحديث بيانات جدول Drivers
            $driver = $user->driver; // جلب علاقة السائق
            if (!$driver) {
                throw new Exception("لا يوجد ملف سائق مرتبط بهذا الحساب.");
            }

            $driverData = array_intersect_key($data, array_flip(['national_id', 'license_number', 'license_expiry']));
            
            if (!empty($driverData)) {
                $driver->update($driverData);
            }

            DB::commit();

            // إرجاع كائن السائق محدثاً ومحملاً بالعلاقات للاستجابة
            return $driver->fresh(['user', 'vehicles', 'documents']);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Driver Profile Update Service Failure: " . $e->getMessage(), [
                'user_id' => $user->id,
            ]);
            throw new Exception("فشلت عملية تحديث البيانات بسبب عطل داخلي.");
        }
    }
}