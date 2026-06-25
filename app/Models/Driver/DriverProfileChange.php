<?php

namespace App\Models\Driver;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Enums\driver\DriverShift; 
use App\Models\Shared\Zone;       

class Driver extends Model
{
    public $timestamps = false;
    protected $table = 'drivers';

    protected $fillable = [
        'user_id', 
        'gender',
        'shift',
        'national_id', 
        'license_number', 
        'license_expiry', 
        'status',          
        'current_lat', 
        'current_lng', 
        'last_ping_at'
    ];

    protected function casts(): array
    {
        return [
            'current_lat'    => 'float',
            'current_lng'    => 'float',
            'last_ping_at'   => 'datetime',
            'license_expiry' => 'date',
            'shift'          => DriverShift::class, 
        ];
    }

    // --- العلاقات المباشرة ---

    public function zones(): BelongsToMany
    {
        return $this->belongsToMany(Zone::class, 'driver_zone', 'driver_id', 'zone_id')
                    ->withTimestamps(); 
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'driver_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DriverDocument::class, 'driver_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(DriverApproval::class, 'driver_id');
    }

    // --- 🔍 Scopes الفلترة والبحث المتقدم ---

    // 1. فلترة الاسم أو الهاتف
    public function scopeSearchKeyword(Builder $query, ?string $keyword): Builder
    {
        if (!$keyword) return $query;
        return $query->whereHas('user', function ($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
              ->orWhere('phone', 'like', "%{$keyword}%");
        });
    }

    // 2. فلترة جنس السائق
    public function scopeGender(Builder $query, ?string $gender): Builder
    {
        return $gender ? $query->where('gender', $gender) : $query;
    }

    // 3. فلترة الفترة الزمنية
    public function scopeShift(Builder $query, ?int $shift): Builder
    {
        return $shift ? $query->where('shift', $shift) : $query;
    }

    // 4. فلترة المناطق المتعددة (قائمة الـ IDs)
    public function scopeZones(Builder $query, ?array $zoneIds): Builder
    {
        if (empty($zoneIds)) return $query;
        return $query->whereHas('zones', function ($q) use ($zoneIds) {
            $q->whereIn('zones.id', $zoneIds);
        });
    }

    // 5. فلترة السعة الركابية المتبقية (تعتمد على اسم الجدول مباشرة لمنع خطأ كلاس الطلاب)
    public function scopeAvailableCapacity(Builder $query, ?int $requiredSeats): Builder
    {
        if (!$requiredSeats) return $query;

        return $query->whereHas('vehicles', function ($q) use ($requiredSeats) {
            $q->whereRaw('(capacity - (select count(*) from students where students.driver_id = drivers.id)) >= ?', [$requiredSeats]);
        });
    }
}