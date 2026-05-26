<?php

namespace App\Services\Driver;

use App\Models\User;
use App\Models\Driver\Driver;
use App\Models\Driver\Vehicle;
use App\Models\Driver\DriverDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;

class DriverRegisterService
{
    /**
     * تسجيل سائق جديد ببياناته الكاملة ومركبته ووثائقه داخل Transaction آمنة
     * * @param array $data البيانات القادمة من الـ Request بعد التحقق
     * @return Driver
     * @throws Exception
     */
    public function register(array $data): Driver
    {
        // بدء المعاملة الآمنة (Database Transaction)
        DB::beginTransaction();

        try {
            // 1. إنشاء الحساب الأساسي في جدول users
            $user = User::create([
                'full_name'    => $data['full_name'],
                'phone_number' => $data['phone_number'],
                'password_hash' => Hash::make($data['password']), // استخدام الحقل الفعلي لقاعدة بياناتك
                'role_id'      => 2, // دور السائق كما هو محدد في السيستم
                'is_active'    => 0, // الحساب معطل وموقوف تلقائياً حتى يراجعه المشرف
                'avatar_url'   => $data['avatar_path'] ?? null,
            ]);

            // 2. إنشاء ملف السائق المهني في جدول drivers
            $driver = Driver::create([
                'user_id'        => $user->id,
                'national_id'    => $data['national_id'],
                'license_number' => $data['license_number'],
                'license_expiry' => $data['license_expiry'],
                'status'         => 'Pending', // حالة السائق بانتظار المراجعة
            ]);

            // 3. إنشاء بيانات المركبة في جدول vehicles
            $vehicle = Vehicle::create([
                'driver_id'         => $driver->id,
                'plate_number'      => $data['plate_number'],
                'brand'             => $data['brand'],
                'model'             => $data['model'],
                'year'              => $data['year'],
                'color'             => $data['color'],
                'type'              => $data['type'],
                'capacity_manual'   => $data['capacity_manual'],
                'vehicle_image_url' => $data['vehicle_image_path'],
                'has_ac'            => $data['has_ac'],
                'status'            => 'Pending', // المركبة تحتاج تدقيق أيضاً
                'is_verified'       => 0
            ]);

            // 4. إدخال المستندات والوثائق المرفوعة
$documents = [
    'LICENSE'         => $data['doc_license_path'],
    'VEHICLE_LOGBOOK' => $data['doc_logbook_path'], // تأكد أن المفتاح هنا هو نفسه في الـ ENUM
    'INSURANCE'       => $data['doc_insurance_path'],
    'CRIMINAL_RECORD' => $data['doc_criminal_record_path'],
];

foreach ($documents as $type => $path) {
    DriverDocument::create([
        'driver_id'   => $driver->id,
        'vehicle_id'  => $vehicle->id,
        'doc_type'    => $type, // هنا سيتم إرسال 'LICENSE' أو 'VEHICLE_LOGBOOK'.. الخ
        'file_url'    => $path,
        'status'      => 'Pending',
        'uploaded_at' => now(), // أضفنا هذا لتجنب خطأ الحقل الفارغ
    ]);
}

            // تأكيد حفظ كل البيانات في الجداول الأربعة معاً بنجاح
            DB::commit();

            // إرجاع كائن السائق محمل بالعلاقات الأساسية له
            return $driver->load(['user', 'vehicles', 'documents']);

        } catch (Exception $e) {
            // حدوث أي خطأ غير متوقع -> إلغاء كل العمليات السابقة فوراً كأنها لم تكن
            DB::rollBack();

            // تسجيل تفاصيل الخطأ الفني في الـ Log للمطور لمراجعته بشكل دقيق
            Log::critical("Driver Registration Service Failure: " . $e->getMessage(), [
                'phone_number' => $data['phone_number'] ?? 'N/A',
                'trace'        => $e->getTraceAsString()
            ]);

            // رفع استثناء يحمل الرسالة الفنية ليتم التقاطها في الـ Controller
            throw new Exception("فشلت عملية تسجيل السائق في النظام بسبب عطل داخلي: " . $e->getMessage());
        }
    }
}