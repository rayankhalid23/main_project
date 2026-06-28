<?php

namespace App\Models\Driver;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\Student;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Enums\driver\DriverShift; // 👈 استدعاء الـ Enum الخاص بالفترات
use App\Models\Shared\Zone;       // 👈 استدعاء موديل المناطق

class Driver extends Model
{
    // بما أننا ألغينا الـ timestamps، هذا ممتاز
    public $timestamps = false;
    protected $table = 'drivers';

    protected $fillable = [
        'user_id', 
        'gender',
        'shift',
        'national_id', 
        'license_number', 
        'license_expiry', 
        'status',          // سنعتمد عليه في منطق الموافقة (Pending, Active, Suspended)
        'current_lat', 
        'current_lng', 
        'last_ping_at'
    ];

    /**
     * تحويل أنواع البيانات (Casts)
     * ضروري جداً لتعامل لارافيل الصحيح مع الإحداثيات والتواريخ والـ Enums
     */
    protected function casts(): array
    {
        return [
            'current_lat'    => 'float',
            'current_lng'    => 'float',
            'last_ping_at'   => 'datetime',
            'license_expiry' => 'date',
            'shift'          => DriverShift::class, // 👈 تحويل تلقائي للـ Enum لقراءة الـ value والـ label
        ];
    }

    /**
     * 🗺️ علاقة السائق بالمناطق المخصصة للعمل (Many-to-Many)
     */
    public function zones(): BelongsToMany
    {
        return $this->belongsToMany(Zone::class, 'driver_zone', 'driver_id', 'zone_id')
                    ->withTimestamps(); // إذا كان جدول الوسيط يحتوي على timestamps
    }

    // علاقة مع المستخدم (حساب السائق)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // علاقة مع المركبات
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'driver_id');
    }

    // علاقة مع الوثائق
    public function documents(): HasMany
    {
        return $this->hasMany(DriverDocument::class, 'driver_id');
    }

    // علاقة مع الموافقات (للسجل الإداري)
    public function approvals(): HasMany
    {
        return $this->hasMany(DriverApproval::class, 'driver_id');
    }
    // 5. 🔥 الفلترة الذكية بالسعة المتبقية للمركبة (سعة المركبة النشطة - عدد الطلاب الحاليين >= عدد الأطفال المطلوب)
    public function scopeAvailableCapacity(Builder $query, ?int $requiredSeats): Builder
    {
        if (!$requiredSeats) return $query;

        return $query->whereHas('vehicles', function ($q) use ($requiredSeats) {
            // نأخذ المركبة المفعلة أو الأولى كمثال، ونطرح منها كاونت الطلاب المشتركين
            $q->whereRaw('(capacity - (select count(*) from students where students.driver_id = drivers.id)) >= ?', [$requiredSeats]);
        });
    }
    /**
 * علاقة السائق مع الطلاب المشتركين معه حالياً
 */
public function students(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    // تأكد من أن اسم الموديل 'Student' واسم الحقل 'driver_id' يطابق ما لديك في قاعدة البيانات
    return $this->hasMany(\App\Models\Student::class, 'driver_id');
}
/**
 * جلب عناوين السائق من الجدول المنفصل الجديد
 */
public function addresses()
{
    return $this->hasMany(\App\Models\Driver\Address::class, 'driver_id');
}
}