<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;

class SystemInitialSeeder extends Seeder
{
    public function run(): void
    {
        // 1. إدخال الأدوار (ضمان عدم التكرار)
        $roles = [
            ['name' => 'admin', 'display_name' => 'مدير النظام'],
            ['name' => 'supervisor', 'display_name' => 'مشرف'],
            ['name' => 'parent', 'display_name' => 'ولي أمر'],
            ['name' => 'driver', 'display_name' => 'سائق'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(['name' => $role['name']], $role);
        }

        // كلمة مرور موحدة ومحسوبة مسبقاً لكل الحسابات لتسريع عملية الفحص والربط (12345678)
        $defaultPassword = Hash::make('12345678');


        // 2. حساب مدير النظام (Role ID: 1)
        User::updateOrCreate(
            ['email' => 'admin@derbi.ly'],
            [
                'full_name'     => 'أحمد المدير',
                'phone_number'  => '0900000000',
                'password_hash' => $defaultPassword,
                'role_id'       => 1,
                'is_active'     => 1,
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ]
        );


        // 3. حسابات المشرفين الوهمية (Role ID: 2)
        $supervisors = [
            ['email' => 'ali.supervisor@derbi.ly', 'full_name' => 'علي عمر المشرف', 'phone_number' => '0911002200'],
            ['email' => 'fatima.supervisor@derbi.ly', 'full_name' => 'فاطمة محمد الترهوني', 'phone_number' => '0922003300'],
        ];

        foreach ($supervisors as $supervisor) {
            User::updateOrCreate(
                ['email' => $supervisor['email']],
                [
                    'full_name'     => $supervisor['full_name'],
                    'phone_number'  => $supervisor['phone_number'],
                    'password_hash' => $defaultPassword,
                    'role_id'       => 2,
                    'is_active'     => 1,
                    'created_at'    => Carbon::now(),
                    'updated_at'    => Carbon::now(),
                ]
            );
        }


        // 4. حسابات أولياء الأمور الوهمية (Role ID: 3)
        $parents = [
            ['email' => 'kilani.parent@derbi.ly', 'full_name' => 'محمد عبد الله الكيلاني', 'phone_number' => '0913334444'],
            ['email' => 'khaled.parent@derbi.ly', 'full_name' => 'خالد مصطفى الورفلي', 'phone_number' => '0925556666'],
        ];

        foreach ($parents as $parent) {
            $userParent = User::updateOrCreate(
                ['email' => $parent['email']],
                [
                    'full_name'     => $parent['full_name'],
                    'phone_number'  => $parent['phone_number'],
                    'password_hash' => $defaultPassword,
                    'role_id'       => 3,
                    'is_active'     => 1,
                    'created_at'    => Carbon::now(),
                    'updated_at'    => Carbon::now(),
                ]
            );

            // ملاحظة هندسية: إذا كان لديك جدول فرعي باسم parents يحتوي على تفاصيل إضافية،
            // يمكنك فك التعليق عن الأسطر بالأسفل لربطه برقم الـ user_id تلقائياً:
            /*
            DB::table('parents')->updateOrInsert(
                ['user_id' => $userParent->id],
                [
                    'address_text' => 'طرابلس، النوفليين',
                    'created_at'   => Carbon::now(),
                    'updated_at'   => Carbon::now()
                ]
            );
            */
        }


        // 5. حسابات السائقين الوهمية (Role ID: 4)
        $drivers = [
            ['email' => 'abdo.driver@derbi.ly', 'full_name' => 'عبد الرزاق حسن الزنتاني', 'phone_number' => '0917778888'],
            ['email' => 'mahmoud.driver@derbi.ly', 'full_name' => 'محمود علي المصراتي', 'phone_number' => '0942223333'],
        ];

        foreach ($drivers as $driver) {
            $userDriver = User::updateOrCreate(
                ['email' => $driver['email']],
                [
                    'full_name'     => $driver['full_name'],
                    'phone_number'  => $driver['phone_number'],
                    'password_hash' => $defaultPassword,
                    'role_id'       => 4,
                    'is_active'     => 1,
                    'created_at'    => Carbon::now(),
                    'updated_at'    => Carbon::now(),
                ]
            );

            // ملاحظة هندسية: إذا كان لديك جدول فرعي باسم drivers يحتوي على تفاصيل السيارة والرخصة،
            // يمكنك فك التعليق عن الأسطر بالأسفل لربطه برقم الـ user_id تلقائياً:
            /*
            DB::table('drivers')->updateOrInsert(
                ['user_id' => $userDriver->id],
                [
                    'license_number' => 'L-'.rand(10000, 99999),
                    'car_model'      => 'Hyundai H1',
                    'created_at'     => Carbon::now(),
                    'updated_at'     => Carbon::now()
                ]
            );
            */
        }
    }
}