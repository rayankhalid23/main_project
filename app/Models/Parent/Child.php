<?php

namespace App\Models\Parent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
// تأكد من مسار ParentModel الصحيح لديك، إذا كان داخل مجلد Parent أو المجلد الرئيسي Models
use App\Models\ParentModel; 

class Child extends Model
{
    // تحديد اسم الجدول في قاعدة البيانات
    protected $table = 'children';
    public $timestamps = false;

    /**
     * الحقول القابلة للتعبئة بشكل جماعي (Mass Assignable)
     */
    protected $fillable = [
        'parent_id',
        'school_id',
        'home_address_id',
        'full_name',
        'birth_date',
        'grade', // تأكد من وجوده هنا
        'notification_radius',
        'daily_status',
        'gender',
'pickup_time',
'dropoff_time',
        'photo_url', // تم التعديل ليتطابق مع الـ DB
        'medical_notes',
        'preferred_time_slot',
        'qr_code_token' // تم التعديل ليتطابق مع الـ DB
    ];

    /**
     * عمل حقل الـ birth_date ككائن Carbon تلقائياً لسهولة التعامل مع التواريخ
     */
    protected $casts = [
        'birth_date' => 'date',
    ];

    /**
     * الدالة المدمجة (Boot Method) للتحكم في الأحداث (Eloquent Events)
     */
    protected static function booted(): void
    {
        parent::booted();
    
        static::creating(function ($child) {
            // توليد الـ Token للحقل الصحيح في قاعدة البيانات
            $child->qr_code_token = 'CHLD-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(6)) . '-' . time();
        });
    }

    /**
     * =========================================================================
     * العلاقات البرمجية (Eloquent Relationships)
     * =========================================================================
     */

    /**
     * علاقة الطفل بولي أمره
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentModel::class, 'parent_id');
    }

    
    /**
     * حساب عمر الطفل الحالي تلقائياً بناءً على تاريخ ميلاده
     */
    public function getAgeAttribute(): int
    {
        return $this->birth_date ? $this->birth_date->age : 0;
    }

    public function school(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(\App\Models\Parent\School::class, 'school_id');
}

public function address(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(\App\Models\Parent\Address::class, 'home_address_id');
}
}