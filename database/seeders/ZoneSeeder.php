<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shared\Zone;
use App\Models\Shared\SubMunicipality;

class ZoneSeeder extends Seeder
{
    public function run(): void
    {
        // تعريف الهيكل: بلدية فرعية -> مناطقها
        $data = [
            'حي الأندلس' => ['حي الأندلس', 'قرجي', 'السياحية', 'غوط الشعال', 'السراج'],
            'بوسليم' => ['بوسليم', 'الهضبة الخضراء', 'السبعة', 'الدريبي', 'طريق المطار', 'صلاح الدين'],
            'سوق الجمعة' => ['سوق الجمعة', 'تاجوراء', 'تاجوراء - الوسط', 'النوفليين'],
            'طرابلس المركز' => ['الظهرة', 'فشلوم', 'زاوية الدهماني', 'المنصورة', 'أبو نواس', 'بن غشير', 'طريق السور', 'الـ 4 شوارع زناتة'],
            'جنزور' => ['جنزور'],
        ];

        foreach ($data as $subName => $zones) {
            // جلب أو إنشاء البلدية الفرعية (يجب أن تكون موجودة في جدول sub_municipalities)
            $subMuni = SubMunicipality::firstOrCreate(['name' => $subName], [
                'municipality_id' => 1 // استبدل بـ ID البلدية الكبرى المناسب
            ]);

            foreach ($zones as $zoneName) {
                Zone::updateOrCreate(
                    ['name' => $zoneName],
                    ['sub_municipality_id' => $subMuni->id]
                );
            }
        }
    }
}