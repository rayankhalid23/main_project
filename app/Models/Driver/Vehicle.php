<?php

namespace App\Models\Driver;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    use SoftDeletes;

    protected $table = 'vehicles';
    const DELETED_AT = 'deleted_at';

    // تفعيل الطوابع الزمنية الافتراضية والحذف الناعم القياسي
    public $timestamps = true;

    protected $fillable = [
        'driver_id', 'plate_number', 'brand', 'model', 'year', 'color', 
        'type', 'capacity_manual', 'capacity_ai', 'is_verified', 
        'vehicle_image_url', 'has_ac', 'status'
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}