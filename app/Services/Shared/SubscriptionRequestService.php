<?php

namespace App\Services\Shared;

use App\Models\Shared\SubscriptionRequest; // استدعاء الموديل من المسار المشترك الصحيح
use Illuminate\Support\Facades\DB;
use Exception;

class SubscriptionRequestService
{
    /**
     * إنشاء طلب اشتراك جديد والتخزين في الجدول الرئيسي والجدول الوسيط للأطفال
     */
    public function createRequest(array $data, $userId)
    {
        // 1. جلب سجل ولي الأمر الحقيقي المرتبط بالمستخدم الحالي لضمان العلاقات الصحيحة
        $parent = DB::table('parents')->where('user_id', $userId)->first();
        
        if (!$parent) {
            throw new Exception("عذراً، هذا الحساب غير مسجل كولي أمر في النظام.");
        }

        // 2. بدء معاملة قاعدة البيانات (Database Transaction) لضمان الأمان والذرية
        DB::beginTransaction();

        try {
            // 3. التخزين في الجدول الرئيسي (requests)
            $requestId = DB::table('requests')->insertGetId([
                'parent_id'      => $parent->id, 
                'driver_id'      => $data['driver_id'],
                'school_id'      => $data['school_id'],
                'timing'         => $data['timing'],
                'status'         => 'pending',
                'notes'          => $data['notes'] ?? null,
                'children_count' => isset($data['children']) ? count($data['children']) : 1,
                'created_at'     => now(), 
            ]);

            // 4. التخزين في الجدول الجديد (request_children) لربط الأطفال والمسارات بالطلب
            if (!empty($data['children']) && is_array($data['children'])) {
                foreach ($data['children'] as $child) {
                    DB::table('request_children')->insert([
                        'request_id'          => $requestId,
                        'child_id'            => $child['child_id'],
                        
                        // حزام أمان: إذا لم يرسل الفرونت-إند قيم المواقع، يتم وضع القيمة الافتراضية 1 منعاً لانهيار الـ SQL
                        'pickup_location_id'  => isset($child['pickup_location_id']) ? $child['pickup_location_id'] : 1, 
                        'dropoff_location_id' => isset($child['dropoff_location_id']) ? $child['dropoff_location_id'] : 1,
                        
                        'notes'               => $child['notes'] ?? null,
                    ]);
                }
            }

            // تأكيد حفظ كافة البيانات في الجدولين بنجاح
            DB::commit();

            return [
                'success'    => true,
                'message'    => 'تم إرسال طلب الاشتراك بنجاح وهو قيد الانتظار لموافقة السائق.',
                'request_id' => $requestId
            ];

        } catch (Exception $e) {
            // التراجع عن أي إدخال في حال حدوث خطأ لحماية سلامة قاعدة البيانات
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * تحديث حالة الطلب من قبل السائق (قبول أو رفض)
     */
    public function updateStatus($subscriptionRequest, $status, $rejectionReason = null)
{
    return DB::table('requests')->where('id', $subscriptionRequest->id)->update([
        'status' => $status,
        'notes'  => $status === 'rejected' ? $rejectionReason : (is_object($subscriptionRequest) ? $subscriptionRequest->notes : null),
    ]);
}
}