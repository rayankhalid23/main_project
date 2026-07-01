<?php

namespace App\Models\Parent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChildLogistics extends Model
{
    // تحديد اسم الجدول إذا كان يختلف عن الجمع (اختياري هنا لأنه يطابق التسمية)
    protected $table = 'child_logistics';

    // الحقول المسموح بالتعديل عليها
    protected $fillable = [
        'child_id',
        'preferred_time_slot',
        'pickup_time',
        'dropoff_time',
        'trip_direction',
        'is_active',
        'start_date',      // الجديد
        'end_date',        // الجديد
        'subscription_type' // الجديد
    ];

    // تحويل البيانات (Casting) لضمان التعامل معها بأنواعها الصحيحة في PHP
    protected $casts = [
        'is_active' => 'boolean',
        'child_id'  => 'integer',
        // ملاحظة: القيم 'morning', 'go' ستعامل كنصوص في لارافيل
    ];

    /**
     * علاقة الموديل بجدول الأطفال
     */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }
}