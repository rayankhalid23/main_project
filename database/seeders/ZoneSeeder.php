<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shared\Zone;

class ZoneSeeder extends Seeder
{
    /**
     * تشغيل التغذية الآلية لمناطق طرابلس الكبرى
     */
    public function run(): void
    {
        $zones = [
            ['name' => 'حي الأندلس'],
            ['name' => 'سوق الجمعة'],
            ['name' => 'عين زارة'],
            ['name' => 'تاجوراء'],
            ['name' => 'بوسليم'],
            ['name' => 'قرجي'],
            ['name' => 'السياحية'],
            ['name' => 'النوفليين'],
            ['name' => 'الهضبة الخضراء'],
            ['name' => 'السبعة'],
            ['name' => 'غوط الشعال'],
            ['name' => 'الدريبي'],
            ['name' => 'طريق المطار'],
            ['name' => 'صلاح الدين'],
            ['name' => 'طريق السور'],
            ['name' => 'بن غشير'],
            ['name' => 'الظهرة'],
            ['name' => 'فشلوم'],
            ['name' => 'زاوية الدهماني'],
            ['name' => 'المنصورة'],
            ['name' => 'أبو نواس'],
            ['name' => 'السراج'],
            ['name' => 'جنزور'],
            ['name' => 'تاجوراء - الوسط'],
            ['name' => 'الـ 4 شوارع زناتة'],
        ];

        foreach ($zones as $zone) {
            // لمنع التكرار في حال تشغيل الـ Seeder أكثر من مرة
            Zone::firstOrCreate(['name' => $zone['name']]);
        }
    }
}