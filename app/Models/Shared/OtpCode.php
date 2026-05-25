<?php

namespace App\Models\Shared;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    // بما أن جدولك يحتوي على created_at، لا تقم بتعطيل الـ timestamps
    // أو إذا كنت تريد إدارة التوقيت يدوياً، احتفظ بها false ولكن أضف العمود للـ fillable
    public $timestamps = false; 
    
    protected $fillable = [
        'phone_number', 
        'code_hash', 
        'purpose', 
        'expires_at', 
        'is_used',
        'attempts', // تم إضافة هذا العمود
        'created_at' // تم إضافة هذا العمود لأنك تستخدمه في الجدول
    ];
}