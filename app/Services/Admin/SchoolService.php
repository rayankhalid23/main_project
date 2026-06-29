<?php

namespace App\Services\Admin;

use App\Models\Parent\School;
use Illuminate\Database\Eloquent\Collection;

class SchoolService
{
    /**
     * جلب المدارس بناءً على الحالة مع شحن العلاقات الجغرافية كاملة لتسريع الأداء
     * (للأدمن يجلب الكل، وللأب يجلب المعتمد فقط)
     */
    public function getSchools(?string $status = null): Collection
    {
        // استخدام with لشحن شجرة الجغرافيا بالكامل كلمح البصر في استعلام واحد
        $query = School::with('zone.subMunicipality.municipality');

        if ($status) {
            return $query->where('status', $status)->get();
        }

        return $query->get();
    }

    /**
     * إضافة مدرسة جديدة في النظام مربوطة بالمنطقة الجغرافية (zone_id)
     */
    public function createSchool(array $data): School
    {
        // سيتم تخزين الـ zone_id تلقائياً لأنه مضاف في الـ $fillable للموديل
        $school = School::create($data);

        return $school->load('zone.subMunicipality.municipality');
    }

    /**
     * تحديث بيانات مدرسة، موقعها الجغرافي، أو تغيير حالتها (الاعتماد)
     */
    public function updateSchool(School $school, array $data): School
    {
        $school->update($data);
        
        // شحن البيانات الجغرافية المحدثة لضمان رجوع الـ Resource كامل للفرونت إند
        return $school->load('zone.subMunicipality.municipality');
    }

    /**
     * حذف مدرسة نهائياً من النظام
     */
    public function deleteSchool(School $school): void
    {
        $school->delete();
    }
}