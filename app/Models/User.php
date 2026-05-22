<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // استدعاء حزمة التوكنات لـ Sanctum

#[Fillable([
    'full_name',
    'phone_number',
    'password_hash',
    'avatar_url',
    'role_id',
    'is_active',
    'phone_verified',
    'alternative_phone',
    'last_login_at'
])]
#[Hidden([
    'password_hash',
    'remember_token'
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable; // تفعيل توليد التوكنات هنا لإنهاء خطأ createToken

    /**
     * تحديد اسم الجدول الفعلي في قاعدة البيانات بشكل صريح
     */
    protected $table = 'users';

    /**
     * تحويل أنواع البيانات عند استدعائها (Casting)
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'phone_verified' => 'boolean',
            'last_login_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            
        ];
    }

    /**
     * توجيه لارفيل للاعتماد على حقل password_hash المخصص في قاعدة بياناتك الحقيقية
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }
}