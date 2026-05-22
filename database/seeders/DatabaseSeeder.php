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
        // 1. إدخال الأدوار الأساسية في النظام (Roles)
        DB::table('roles')->insertOrIgnore([
            [
                'id' => 1,
                'name' => 'admin',
                'display_name' => 'مدير النظام',
                'permissions' => null,
                'description' => 'صلاحيات كاملة على النظام'
            ],
            [
                'id' => 2,
                'name' => 'driver',
                'display_name' => 'سائق',
                'permissions' => null,
                'description' => 'إدارة المسارات والرحلات'
            ],
            [
                'id' => 3,
                'name' => 'parent',
                'display_name' => 'ولي أمر',
                'permissions' => null,
                'description' => 'متابعة الأطفال والعقود'
            ],
        ]);

        // 2. إنشاء مستخدمين حقيقيين يطابقون شروط الـ Validation الصارمة (09 وباسورد معقد)
        // نستخدم insertOrIgnore لتجنب تكرار البيانات عند إعادة تشغيل الـ Seed
        
        // أ. حساب مدير النظام (Admin)
        DB::table('users')->insertOrIgnore([
            'id' => 1,
            'full_name' => 'أحمد المدير',
            'phone_number' => '0912345678', // مطابق لشرط 10 خانات ويبدأ بـ 09
            'password_hash' => Hash::make('A123456'), // يحتوي على حروف وأرقام وأكثر من 6 خانات
            'role_id' => 1,
            'is_active' => 1,
            'phone_verified' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // ب. حساب السائق (Driver)
        DB::table('users')->insertOrIgnore([
            'id' => 2,
            'full_name' => 'محمود السائق',
            'phone_number' => '0912345679',
            'password_hash' => Hash::make('D123456'),
            'role_id' => 2,
            'is_active' => 1,
            'phone_verified' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // ج. حساب ولي الأمر (Parent)
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


        // 3. ربط المستخدمين في جداول التخصيص الفرعية كما توضح بنية قاعدة البيانات تماماً
        
        // تخصيص جدول الـ admins
        DB::table('admins')->insertOrIgnore([
            [
                'id' => 1,
                'user_id' => 1, // مرتبط بـ أحمد المدير
                'created_by' => 1
            ]
        ]);

        // تخصيص جدول الـ drivers وضخ بيانات تجريبية له
        DB::table('drivers')->insertOrIgnore([
            'id' => 1,
            'user_id' => 2, // مرتبط بـ محمود السائق
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
        ]);

        // تخصيص جدول الـ parents
        DB::table('parents')->insertOrIgnore([
            'id' => 1,
            'user_id' => 3, // مرتبط بـ خالد ولي الأمر
            'is_trusted' => 1,
        ]);
    }
}