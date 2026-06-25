<?php

namespace App\Models\Shared; // اضبط الـ namespace حسب مسار الملف لديك

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Driver; // تأكد من مسار موديل السائق لديك

class Zone extends Model
{
    protected $fillable = ['name'];

    /**
     * علاقة المنطقة بالسائقين (المنطقة الواحدة يغطيها أكثر من سائق)
     */
    public function drivers(): BelongsToMany
    {
        return $this->belongsToMany(Driver::class, 'driver_zone', 'zone_id', 'driver_id')
                    ->withTimestamps();
    }
}