<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
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
    }
}