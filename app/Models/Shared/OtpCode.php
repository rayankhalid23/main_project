<?php

namespace App\Models\Shared;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{

    public $timestamps = false;
    protected $fillable = [
        'phone_number', 
        'code_hash', 
        'purpose', 
        'expires_at', 
        'is_used'
    ];
}