<?php

namespace App\Models\Shared;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubMunicipality extends Model
{
    protected $fillable = ['municipality_id', 'name'];

    // البلدية الأصغر تتبع بلدية كبرى واحدة
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    // البلدية الأصغر تحتوي على العديد من المناطق الدقيقة
    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class);
    }
}