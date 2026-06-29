<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TripoliGeographySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // مصفوفة البيانات الجغرافية المنظمة بدقة لمدينة طرابلس (3 مستويات)
        $geographyData = [
            'طرابلس المركز' => [
                'طرابلس المدينة' => ['بن عاشور', 'الظهرة', 'زاوية الدهماني', 'فشلوم', 'ميزران', 'شارع عمر المختار'],
                'النوفليين وراس حسن' => ['النوفليين', 'راس حسن', 'الهاني', 'ساحة الشهداء']
            ],
            'حي الأندلس' => [
                'حي الأندلس المركز' => ['حي الأندلس', 'قرقارش', 'قرجي', 'المدينة السياحية'],
                'غوط الشعال والسراج' => ['غوط الشعال', 'السراج', 'حي الكويت', 'طريق قرقارش الرئيسي']
            ],
            'سوق الجمعة' => [
                'سوق الجمعة المركز' => ['عرادة', 'شرفة الملاحة', 'الحارات', 'سيدي المصري', 'الحامية'],
                'امتداد تاجوراء' => ['البيفي', 'طريق الشط تاجوراء', 'وسط تاجوراء', 'الكلوة']
            ],
            'أبوسليم' => [
                'أبوسليم المدينة' => ['محلة أبوسليم', 'الدريبي', 'باب بن غشير', 'حي المجاهدين'],
                'الهضبة' => ['حي دمشق', 'الهضبة الخضراء', 'مشروع الهضبة', 'صلاح الدين']
            ],
            'عين زارة' => [
                'عين زارة الشمالية' => ['السبعة', 'طريق المشتل', 'زوطة', 'خمس شوارع'],
                'عين زارة الجنوبية' => ['طريق عايد', 'منطقة الاستراحة', 'كوشة المشيرعي', 'الأبيار']
            ]
        ];

        // استخدام Transaction لضمان سلامة البيانات أثناء الإدخال
        DB::transaction(function () use ($geographyData) {
            
            foreach ($geographyData as $municipalityName => $subMunicipalities) {
                
                // 1. إدخال البلدية الرئيسية وجلب الـ ID الخاص بها
                $municipalityId = DB::table('municipalities')->insertGetId([
                    'name' => $municipalityName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($subMunicipalities as $subMunicipalityName => $zones) {
                    
                    // 2. إدخال البلدية الأصغر وجلب الـ ID الخاص بها
                    $subMunicipalityId = DB::table('sub_municipalities')->insertGetId([
                        'municipality_id' => $municipalityId,
                        'name' => $subMunicipalityName,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    foreach ($zones as $zoneName) {
                        
                        // 3. إدخال المناطق الدقيقة داخل جدول zones
                        DB::table('zones')->insert([
                            'sub_municipality_id' => $subMunicipalityId,
                            'name' => $zoneName,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        });
    }
}