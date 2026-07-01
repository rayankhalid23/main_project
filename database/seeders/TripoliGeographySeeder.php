<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TripoliGeographySeeder extends Seeder
{
    public function run(): void
    {
        $geographyData = [
            'طرابلس المركز' => [
                'طرابلس المدينة' => ['بن عاشور', 'الظهرة', 'زاوية الدهماني', 'فشلوم'],
                'النوفليين' => ['النوفليين', 'راس حسن']
            ],
            'حي الأندلس' => [
                'حي الأندلس المركز' => ['حي الأندلس', 'قرقارش', 'غوط الشعال']
            ],
            'سوق الجمعة' => [
                'سوق الجمعة المركز' => ['عرادة', 'شرفة الملاحة']
            ]
        ];

        foreach ($geographyData as $muniName => $subMunis) {
            $muniId = DB::table('municipalities')->insertGetId(['name' => $muniName, 'created_at' => now(), 'updated_at' => now()]);

            foreach ($subMunis as $subName => $zones) {
                $subMuniId = DB::table('sub_municipalities')->insertGetId(['municipality_id' => $muniId, 'name' => $subName, 'created_at' => now(), 'updated_at' => now()]);

                foreach ($zones as $zoneName) {
                    DB::table('zones')->insert(['sub_municipality_id' => $subMuniId, 'name' => $zoneName, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }
    }
}