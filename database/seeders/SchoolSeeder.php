<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        $schools = [
            // لاحظ استخدام 'address' بدلاً من 'address_text'
            ['name' => 'مدرسة طرابلس المركزية للتعليم الأساسي (بنين)', 'lat' => 32.8872, 'lng' => 13.1913, 'address' => 'طرابلس المركز - محلة بلخير', 'zone_id' => 1, 'status' => 'active'],
            ['name' => 'مدرسة النصر للتعليم الثانوي (بنات)', 'lat' => 32.8815, 'lng' => 13.1820, 'address' => 'طرابلس المركز - شارع الصريم', 'zone_id' => 1, 'status' => 'active'],
            ['name' => 'مدرسة حطين للتعليم الأساسي', 'lat' => 32.8910, 'lng' => 13.2450, 'address' => 'سوق الجمعة - شرفة الملاحة', 'zone_id' => 2, 'status' => 'active'],
            ['name' => 'مدرسة شهداء النوفليين للتعليم الأساسي', 'lat' => 32.8760, 'lng' => 13.2120, 'address' => 'سوق الجمعة - محلة النوفليين', 'zone_id' => 2, 'status' => 'active'],
            ['name' => 'مدرسة الفجر الجديد للتعليم الأساسي', 'lat' => 32.8790, 'lng' => 13.1340, 'address' => 'حي الأندلس - الشارع الرئيسي', 'zone_id' => 3, 'status' => 'active'],
            ['name' => 'مدرسة غوط الشعال الثانوية (بنين)', 'lat' => 32.8640, 'lng' => 13.1250, 'address' => 'حي الأندلس - منطقة غوط الشعال', 'zone_id' => 3, 'status' => 'active'],
        ];

        foreach ($schools as $school) {
            DB::table('schools')->updateOrInsert(
                ['name' => $school['name']], 
                $school
            );
        }
    }
}