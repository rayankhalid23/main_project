<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SchoolSeeder::class,      // 1. المدارس
            ClauseSeeder::class,      // 2. الشروط
            SystemInitialSeeder::class, // 3. الأدوار والمستخدمين والبيانات الأخرى
        ]);
    }
}