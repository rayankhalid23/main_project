<?php

namespace App\Models\Parent;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ParentModel extends Model
{
    use HasFactory;

    protected $table = 'parents'; // تحديد اسم الجدول يدوياً
    
    public $timestamps = false; // الجدول لا يحتوي على created_at/updated_at

    protected $fillable = [
        'user_id',
        'is_trusted'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Child::class, 'parent_id');
    }
}