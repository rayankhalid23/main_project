<?php

namespace App\Models\Driver;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverApproval extends Model
{
    // تعيين الجدول بشكل صريح ليتطابق مع قاعدة البيانات
    protected $table = 'driver_approvals';

    protected $fillable = [
        'driver_id',
        'admin_id',
        'status',
        'rejection_reason'
    ];

    // إيقاف الـ timestamps الافتراضية لأننا نعتمد على created_at فقط
    public $timestamps = false;

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}