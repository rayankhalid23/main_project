<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\Admin\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use App\Models\Driver\DriverApproval;
use App\Models\Driver\Driver;

class AdminService
{
    /**
     * جلب قائمة المشرفين مع علاقاتهم و Pagination
     */
    public function getAllAdmins($perPage = 10)
    {
        return Admin::with(['user', 'creator'])->latest()->paginate($perPage);
    }

    /**
     * إضافة مشرف جديد
     */
    public function createAdmin(array $data, ?UploadedFile $avatar = null): Admin
    {
        return DB::transaction(function () use ($data, $avatar) {
            // 1. رفع الصورة إن وجدت
            $avatarUrl = null;
            if ($avatar) {
                $path = $avatar->store('uploads/admins/avatars', 'public');
                $avatarUrl = 'storage/' . $path;
            }

            // 2. إنشاء حساب المستخدم (User)
            $user = User::create([
                'full_name'    => $data['full_name'],
                'phone_number' => $data['phone_number'],
                'password_hash'     => Hash::make($data['password']),
                'role_id'      => $data['role_id'],
                'is_active'    => $data['is_active'],
                'avatar_url'   => $avatarUrl,
            ]);

            // 3. ربط الحساب بجدول المشرفين (Admin)
            $admin = Admin::create([
                'user_id'    => $user->id,
                'created_by' => $data['created_by'],
            ]);

            return $admin->load(['user', 'creator']);
        });
    }

    /**
     * تعديل بيانات مشرف
     */
    public function updateAdmin(Admin $admin, array $data, ?UploadedFile $avatar = null): Admin
    {
        return DB::transaction(function () use ($admin, $data, $avatar) {
            $user = $admin->user;
            $updateData = [];

            // تحديث الحقول الأساسية إذا تم إرسالها
            if (isset($data['full_name'])) $updateData['full_name'] = $data['full_name'];
            if (isset($data['phone_number'])) $updateData['phone_number'] = $data['phone_number'];
            if (isset($data['is_active'])) $updateData['is_active'] = $data['is_active'];

            // تشفير كلمة المرور إذا تم إرسال واحدة جديدة
            if (!empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }

            // معالجة استبدال الصورة (مسح القديمة لتوفير المساحة + رفع الجديدة)
            if ($avatar) {
                if ($user->avatar_url && Storage::disk('public')->exists(str_replace('storage/', '', $user->avatar_url))) {
                    Storage::disk('public')->delete(str_replace('storage/', '', $user->avatar_url));
                }
                $path = $avatar->store('uploads/admins/avatars', 'public');
                $updateData['avatar_url'] = 'storage/' . $path;
            }

            // تنفيذ التحديث
            if (!empty($updateData)) {
                $user->update($updateData);
            }

            return $admin->refresh()->load(['user', 'creator']);
        });
    }
    public function processDriverApproval(int $driverId, int $adminId, string $status, ?string $reason = null)
{
    // تحويل الحالة إلى صيغة تتوافق مع قاعدة البيانات إذا لزم الأمر
    $status = ucfirst(strtolower($status)); // تحويل 'approved' إلى 'Approved'

    return DB::transaction(function () use ($driverId, $adminId, $status, $reason) {
        
        // 1. تسجيل السجل التاريخي
        DriverApproval::create([
            'driver_id' => $driverId,
            'admin_id'  => $adminId,
            'status'    => $status,
            'rejection_reason' => $reason,
        ]);

        // 2. جلب السائق
        $driver = Driver::findOrFail($driverId);
        
        // 3. تحديث الـ User (تفعيل/إيقاف)
        $driver->user->update([
            'is_active' => ($status === 'Approved' ? 1 : 0)
        ]);
        
        // 4. تحديث حالة السائق
        $driver->update([
            'status' => $status
        ]);

        return $driver;
    });
}
}