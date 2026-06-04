<?php

namespace App\Models\Parent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    // تحديد اسم الجدول الفعلي كما في الصورة
    protected $table = 'schools'; 

    // إيقاف الطوابع الزمنية تماماً بناءً على طلبك
    public $timestamps = false;

    /**
     * الحقول القابلة للتعبئة (Mass Assignable) مطابقة للصورة
     */
    protected $fillable = [
        'name',
        'lat',
        'lng',
        'address_text',
        'status',
    ];

    /**
     * علاقة المدرسة بالأطفال (المدرسة الواحدة بها العديد من الأطفال)
     */
    public function children(): HasMany
    {
        return $this->hasMany(\App\Models\Parent\Child::class, 'school_id');
    }
}