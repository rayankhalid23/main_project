<?php

namespace App\Models\Shared;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'email',       // قمنا بتغيير phone_number إلى email
        'code_hash', 
        'purpose', 
        'expires_at', 
        'is_used',
        'attempts', 
        'created_at' 
    ];
}