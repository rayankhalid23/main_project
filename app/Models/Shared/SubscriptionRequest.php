<?php

namespace App\Models\Shared;

use App\Models\Parent\ParentModel;
use App\Models\Driver\Driver;
use App\Models\Parent\School;
use App\Models\Parent\Child;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SubscriptionRequest extends Model
{
    // ربط الموديل بجدول الطلبات
    protected $table = 'requests'; 

    public $timestamps = false; // لأن الجدول يحتوي على created_at فقط

    protected $fillable = [
        'parent_id',
        'driver_id',
        'school_id',
        'timing',
        'status',
        'notes',
        'children_count'
    ];

    /**
     * علاقة جلب الأطفال المرتبطين بالطلب من خلال الجدول الوسيط
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Child::class, 'request_children', 'request_id', 'child_id')
                    ->withPivot('pickup_location_id', 'dropoff_location_id', 'notes');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentModel::class, 'parent_id');
    }
}