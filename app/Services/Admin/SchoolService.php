<?php

namespace App\Services\Admin;

use App\Models\Parent\School;
use Illuminate\Database\Eloquent\Collection;

class SchoolService
{
    /**
     * جلب المدارس بناءً على الحالة (للأدمن يجلب الكل، وللأب يجلب المعتمد فقط)
     */
    public function getSchools(?string $status = null): Collection
    {
        if ($status) {
            return School::where('status', $status)->get();
        }
        return School::all();
    }

    /**
     * إضافة مدرسة في النظام
     */
    public function createSchool(array $data): School
    {
        return School::create($data);
    }

    /**
     * تحديث بيانات مدرسة أو تغيير حالتها (الاعتماد)
     */
    public function updateSchool(School $school, array $data): School
    {
        $school->update($data);
        return $school;
    }

    /**
     * حذف مدرسة
     */
    public function deleteSchool(School $school): void
    {
        $school->delete();
    }
}