<?php

namespace App\Models\Shared;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_request_id',
        'parent_id',
        'driver_id',
        'price',
        'pickup_time',
        'dropoff_time',
        'max_waiting_time',
        'selected_clauses',
        'status',
    ];

    /**
     * تحويل حقل الـ JSON إلى مصفوفة PHP تلقائياً عند التعامل مع الموديل
     */
    protected $casts = [
        'selected_clauses' => 'array',
        'price' => 'decimal:2',
    ];

    /**
     * علاقة العقد مع الطلب الأساسي للاشتراك
     */
    public function subscriptionRequest(): BelongsTo
    {
        return $this->belongsTo(SubscriptionRequest::class, 'subscription_request_id');
    }

    /**
     * علاقة العقد مع ولي الأمر (مستلم الخدمة)
     */
    /**
     * علاقة العقد مع ولي الأمر
     */
    public function parent(): BelongsTo
    {
        // استخدام المسار الكامل \App\Models\User ينهي مشكلة Namespace تماماً
        return $this->belongsTo(\App\Models\User::class, 'parent_id');
    }

    /**
     * علاقة العقد مع السائق
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'driver_id');
    }
    
}