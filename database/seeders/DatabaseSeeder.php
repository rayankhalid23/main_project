<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. إدخال الأدوار الأساسية في النظام (Roles) بعد إعادة الترتيب الهندسية
        DB::table('roles')->insertOrIgnore([
            [
                'id' => 1,
                'name' => 'admin',
                'display_name' => 'مدير النظام',
                'permissions' => null,
                'description' => 'صلاحيات كاملة ومطلقة على النظام'
            ],
            [
                'id' => 2,
                'name' => 'supervisor',
                'display_name' => 'مشرف',
                'permissions' => null,
                'description' => 'إشراف ومتابعة العملاء و الرحلات '
            ],
            [
                'id' => 3,
                'name' => 'parent',
                'display_name' => 'ولي أمر',
                'permissions' => null,
                'description' => 'متابعة الأطفال'
            ],
            [
                'id' => 4,
                'name' => 'driver',
                'display_name' => 'سائق',
                'permissions' => null,
                'description' => 'إدارة المسارات والرحلات والتوصيل'
            ],
        ]);

        // 2. إنشاء مستخدمين تجريبيين يطابقون شروط الـ Validation الصارمة (09 وباسورد معقد)
        
        // أ. حساب مدير النظام (Admin) - Role 1
        DB::table('users')->insertOrIgnore([
            'id' => 1,
            'full_name' => 'أحمد المدير',
            'phone_number' => '0912345678', // 10 خانات ويبدأ بـ 09
            'password_hash' => Hash::make('A123456'),
            'role_id' => 1,
            'is_active' => 1,
            'phone_verified' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // ب. حساب المشرف الجديد (Supervisor) - Role 2
        DB::table('users')->insertOrIgnore([
            'id' => 2,
            'full_name' => 'محمد المشرف',
            'phone_number' => '0922345678',
            'password_hash' => Hash::make('S123456'),
            'role_id' => 2,
            'is_active' => 1,
            'phone_verified' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // ج. حساب ولي الأمر (Parent) - Role 3
        DB::table('users')->insertOrIgnore([
            'id' => 3,
            'full_name' => 'خالد ولي الأمر',
            'phone_number' => '0912345670',
            'password_hash' => Hash::make('P123456'),
            'role_id' => 3,
            'is_active' => 1,
            'phone_verified' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // د. حساب السائق (Driver) - تم تعديل معرف الـ User والـ Role ليصبح 4
        DB::table('users')->insertOrIgnore([
            'id' => 4,
            'full_name' => 'محمود السائق',
            'phone_number' => '0912345679',
            'password_hash' => Hash::make('D123456'),
            'role_id' => 4, // يطابق التحديث الجديد لـ السائق
            'is_active' => 1,
            'phone_verified' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);


        // 3. ربط المستخدمين في جداول التخصيص الفرعية بدقة عالية
        
        // تخصيص جدول الـ admins
        DB::table('admins')->insertOrIgnore([
            [
                'id' => 1,
                'user_id' => 1, // مرتبط بـ أحمد المدير
                'created_by' => 1
            ]
        ]);

        // تخصيص جدول الـ parents
        DB::table('parents')->insertOrIgnore([
            [
                'id' => 1,
                'user_id' => 3, // مرتبط بـ خالد ولي الأمر
                'is_trusted' => 1,
            ]
        ]);

        // تخصيص جدول الـ drivers وتعديل الـ user_id ليطابق الرقم الجديد (4)
        DB::table('drivers')->insertOrIgnore([
            [
                'id' => 1,
                'user_id' => 4, // مرتبط بـ محمود السائق بعد التعديل
                'national_id' => '119900000000',
                'license_number' => 'L-554433',
                'license_expiry' => Carbon::now()->addYears(3),
                'status' => 'available',
                'rating_avg' => 5.0,
                'completed_trips_count' => 0,
                'total_subs_count' => 0,
                'active_subs_count' => 0,
                'cancelled_by_driver_count' => 0,
                'cancelled_by_parent_count' => 0,
                'retention_rate' => 100.00
            ]
        ]);
    }
}