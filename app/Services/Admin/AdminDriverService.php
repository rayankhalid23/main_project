<?php

namespace App\Services\Admin;

use App\Models\Driver\Driver;
use App\Models\Driver\DriverApproval;
use App\Services\Shared\EmailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Exception;

class AdminDriverService
{
    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * جلب طلبات اشتراك السائقين المعلقة فقط حصرياً للآدمن
     */
    public function getDriversList(array $filters): LengthAwarePaginator
    {
        // 1. بدء الاستعلام مع كسر حجب جدول المستخدمين (Users) المرتبطين بالسائقين
        $query = Driver::with(['user' => function($q) {
            $q->withTrashed()->withoutGlobalScopes(); 
        }]);

        // 2. 🚀 الإجبار الصارم: جلب الحالات المعلقة فقط وإلغاء أي فلاتر أخرى قادمة من الـ Controller
        $query->whereIn('status', ['Pending', 'pending']);

        // 3. فلترة البحث النصي (تُفعّل فقط إذا كتب الآدمن نصاً في خانة البحث)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->withTrashed()->withoutGlobalScopes()
                  ->where(function($sub) use ($search) {
                      $sub->where('full_name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('phone_number', 'like', "%{$search}%");
                  });
            });
        }

        // 4. ترتيب تنازلي (الأحدث أولاً) مع الـ Pagination
        return $query->orderBy('id', 'desc')->paginate(15);
    }
   

    /**
     * 2. جلب التفاصيل العميقة لسائق معين لمراجعته من قبل الإدارة
     */
   /**
     * جلب تفاصيل سائق محدد بالكامل دون أي حجب صامت أو قيود معالجة الوثائق
     */
    public function getDriverDetails(int $id): Driver
    {
        // 1. كسر حجب السائق بالكامل أولاً بشكل منفصل
        $driver = Driver::withoutGlobalScopes()->where('id', $id)->first();

        if (!$driver) {
            throw new \Exception("عذراً، السائق المطلوب غير موجود في النظام نهائياً.");
        }

        // 2. تحميل الحساب الشخصي بشكل آمن ومحمي من الحجب الصامت
        $driver->load([
            'user' => function($q) {
                $q->withTrashed()->withoutGlobalScopes(); 
            }
        ]);

        // 3. تحميل المركبات بشكل آمن وتخطي القيود
        if (method_exists($driver, 'vehicles')) {
            $driver->load(['vehicles' => function($q) {
                $q->withoutGlobalScopes();
            }]);
        }

        // 4. تحميل الوثائق بشكل آمن مع فك حجب القيود الصامتة
        if (method_exists($driver, 'documents')) {
            $driver->load(['documents' => function($q) {
                $q->withoutGlobalScopes();
            }]);

            // 🚀 هندسة ذكية: إصلاح مشكلة الـ null في الحقول والروابط مباشرة هنا لضمان قيم حقيقية وروابط كاملة
            $driver->documents->transform(function ($doc) {
                $doc->document_type = $doc->doc_type ?? $doc->document_type;
                $doc->document_url = $doc->file_url ? url($doc->file_url) : ($doc->document_url ?? null);
                return $doc;
            });
        }

        return $driver;
    }

    /**
     * 3. معالجة طلب السائق (تفعيل وقبول أو رفض مسبب) للإنشاء أول مرة
     */
    public function reviewDriverRequest(int $driverId, array $data, int $adminId): Driver
    {
        return DB::transaction(function () use ($driverId, $data, $adminId) {
            
            $driver = Driver::with('user')->lockForUpdate()->findOrFail($driverId);
            
            $status = $data['status']; // Approved أو Rejected
            $rejectionReason = $data['rejection_reason'] ?? null;

            $driver->update([
                'status' => $status
            ]);

            if ($status === 'Approved') {
                $driver->user->update([
                    'is_active' => true
                ]);
            }

            DriverApproval::create([
                'driver_id'        => $driver->id,
                'admin_id'         => $adminId,
                'status'           => $status,
                'rejection_reason' => $rejectionReason,
                'created_at'       => now()
            ]);

            $this->emailService->sendDriverReviewResult(
                $driver->user->email,
                $driver->user->full_name,
                $status,
                $rejectionReason,
                $driver->gender
            );

            return $driver;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | 🚀 الدوال المضافة الخاصة بإدارة التعديلات اللاحقة
    |--------------------------------------------------------------------------
    |*/

    /**
     * 4. عرض كافة طلبات التعديلات المعلقة بانتظار موافقة الآدمن
     */
    public function getPendingChangesList(): LengthAwarePaginator
    {
        return DB::table('driver_profile_changes')
            ->join('drivers', 'driver_profile_changes.driver_id', '=', 'drivers.id')
            ->join('users', 'drivers.user_id', '=', 'users.id')
            ->select(
                'driver_profile_changes.*', 
                'users.full_name as driver_name', 
                'users.phone_number as driver_phone'
            )
            ->where('driver_profile_changes.status', 'Pending')
            ->orderBy('driver_profile_changes.created_at', 'asc')
            ->paginate(15);
    }

    /**
     * 5. عرض تفصيلي لطلب تعديل محدد
     */
    public function getPendingChangeDetails(int $changeId): object
    {
        $change = DB::table('driver_profile_changes')->where('id', $changeId)->first();
        
        if (!$change) {
            throw new Exception('طلب التعديل هذا غير موجود أو تم معالجته مسبقاً.');
        }

        // جلب السائق الحالي للمقارنة
        $change->driver = Driver::with(['user', 'vehicles'])->find($change->driver_id);
        $change->new_values_decoded = json_decode($change->new_values, true);

        return $change;
    }

    /**
     * 6. الموافقة أو الرفض على طلب التعديل المعلق مع تطبيق التغييرات وإرسال إشعار داخلي فوراً
     */
    public function reviewProfileChangeRequest(int $changeId, string $decision, ?string $rejectionReason, int $adminId): bool
    {
        return DB::transaction(function () use ($changeId, $decision, $rejectionReason, $adminId) {
            
            // جلب سجل التعديل وقفله
            $change = DB::table('driver_profile_changes')->lockForUpdate()->where('id', $changeId)->first();
            
            if (!$change || $change->status !== 'Pending') {
                throw new Exception('لا يمكن معالجة هذا الطلب، قد يكون مقبولاً أو مرفوضاً مسبقاً.');
            }

            $driver = Driver::with('user')->findOrFail($change->driver_id);

            if ($decision === 'Approved') {
                $newValues = json_decode($change->new_values, true);

                // أ) فرز وتحديث حقول جدول الـ users إن وجدت
                $userFields = ['full_name', 'phone_number', 'alternative_phone', 'avatar_url'];
                $userDataToUpdate = array_intersect_key($newValues, array_flip($userFields));
                if (!empty($userDataToUpdate)) {
                    $driver->user->update($userDataToUpdate);
                }

                // ب) فرز وتحديث حقول جدول الـ drivers الحساسة
                $driverFields = ['national_id', 'license_number', 'license_expiry'];
                $driverDataToUpdate = array_intersect_key($newValues, array_flip($driverFields));
                if (!empty($driverDataToUpdate)) {
                    $driver->update($driverDataToUpdate);
                }

                // ج) فرز وتحديث بيانات المركبة المرتبطة إن وجدت
                $vehicleFields = ['plate_number', 'brand', 'model', 'year', 'color', 'type', 'capacity_manual', 'vehicle_image_path', 'has_ac'];
                $vehicleDataToUpdate = array_intersect_key($newValues, array_flip($vehicleFields));
                if (!empty($vehicleDataToUpdate)) {
                    // تحديث السيارة الحالية النشطة للسائق
                    $driver->vehicles()->where('is_verified', true)->update($vehicleDataToUpdate);
                }

                // د) تحديث حالة الطلب إلى Approved
                DB::table('driver_profile_changes')->where('id', $changeId)->update([
                    'status'    => 'Approved',
                    'action_at' => now()
                ]);

                // 🚀 هـ) إدخال إشعار القبول مباشرة في جدول الإشعارات الخاص بك
                DB::table('notifications')->insert([
                    'user_id'    => $driver->user_id,
                    'type'       => 'SYSTEM', // متوافق تماماً مع Enum جدولك الخاص
                    'title'      => '🎉 تم قبول تحديث بياناتك',
                    'body'       => 'مرحباً بك كابتن، تمت الموافقة على تعديل بيانات ملفك الشخصي وتطبيقها بنجاح.',
                    'metadata'   => json_encode([
                        'status' => 'Approved',
                        'type'   => 'profile_update_review'
                    ]),
                    'priority'   => 'High',
                    'is_read'    => 0,
                    'created_at' => now(),
                ]);

            } else {
                // أ) في حال الرفض: تحديث حالة الطلب وإثبات سبب الرفض
                DB::table('driver_profile_changes')->where('id', $changeId)->update([
                    'status'           => 'Rejected',
                    'rejection_reason' => $rejectionReason,
                    'action_at'        => now()
                ]);

                // 🚀 ب) إدخال إشعار الرفض مباشرة في جدول الإشعارات الخاص بك مع توضيح السبب
                DB::table('notifications')->insert([
                    'user_id'    => $driver->user_id,
                    'type'       => 'SYSTEM', // متوافق تماماً مع Enum جدولك الخاص
                    'title'      => '📋 مراجعة تحديث البيانات',
                    'body'       => "نأسف لإبلاغك برفض طلب تعديل البيانات المرفق بملفك الشخصي بسبب: {$rejectionReason}",
                    'metadata'   => json_encode([
                        'status'           => 'Rejected',
                        'rejection_reason' => $rejectionReason,
                        'type'             => 'profile_update_review'
                    ]),
                    'priority'   => 'High',
                    'is_read'    => 0,
                    'created_at' => now(),
                ]);
            }

            return true;
        });
    }
}