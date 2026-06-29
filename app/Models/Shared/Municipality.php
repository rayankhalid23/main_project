<?php

namespace App\Models\Shared;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipality extends Model
{
    protected $fillable = ['name'];

    // البلدية الكبرى تحتوي على العديد من البلديات الأصغر
    public function subMunicipalities(): HasMany
    {
        return $this->hasMany(SubMunicipality::class);
    }
}