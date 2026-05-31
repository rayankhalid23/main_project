<?php

namespace App\Models\Driver;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Driver\DriverApproval;

class Driver extends Model
{
    public $timestamps = false;
    protected $table = 'drivers';
    

    protected $fillable = [
        'user_id', 'national_id', 'license_number', 'license_expiry', 
        'status', 'current_lat', 'current_lng', 'last_ping_at'
    ];

    // علاقة مع المستخدم (حساب السائق)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // علاقة مع المركبات (سائق واحد قد يملك مركبات متعددة)
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    // علاقة مع الوثائق
    public function documents(): HasMany
    {
        return $this->hasMany(DriverDocument::class);
    }
    public function approvals(): HasMany
    {
        return $this->hasMany(DriverApproval::class, 'driver_id');
    }
}