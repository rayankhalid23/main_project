<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        $schools = [
            ['name' => 'مدرسة طرابلس المركزية', 'zone_name' => 'زاوية الدهماني', 'lat' => 32.88, 'lng' => 13.19, 'address' => 'طرابلس', 'status' => 'active'],
            ['name' => 'مدرسة حطين', 'zone_name' => 'شرفة الملاحة', 'lat' => 32.89, 'lng' => 13.24, 'address' => 'سوق الجمعة', 'status' => 'active'],
        ];

        foreach ($schools as $school) {
            // جلب الـ ID الخاص بالمنطقة بناءً على اسمها
            $zone = DB::table('zones')->where('name', $school['zone_name'])->first();

            DB::table('schools')->updateOrInsert(
                ['name' => $school['name']], 
                [
                    'zone_id' => $zone ? $zone->id : null, 
                    'lat' => $school['lat'],
                    'lng' => $school['lng'],
                    'address' => $school['address'],
                    'status' => $school['status']
                ]
            );
        }
    }
}