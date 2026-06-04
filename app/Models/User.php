<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens; 
use App\Models\Driver\Driver; 
use App\Models\Admin\Admin;
use App\Models\Parent\ParentModel; // تأكد من استدعاء المسار الصحيح

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes; 

    protected $table = 'users';

    // الطريقة التقليدية والمستقرة لتعريف الحقول
    protected $fillable = [
        'full_name',
        'phone_number',
        'password_hash',
        'avatar_url',
        'role_id',
        'is_active',
        'phone_verified',
        'alternative_phone',
        'last_login_at'
    ];

    protected $hidden = [
        'password_hash',
        'remember_token'
    ];

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

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // علاقة الربط مع جدول أولياء الأمور
    public function parentProfile()
    {
        return $this->hasOne(ParentModel::class, 'user_id');
    }

    public function driver()
    {
        return $this->hasOne(Driver::class, 'user_id');
    }

    public function admin()
    {
        return $this->hasOne(Admin::class, 'user_id');
    }
}