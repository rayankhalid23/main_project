<?php

namespace App\Models\Shared;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Driver\Driver; // تأكد من المسار الصحيح للموديل لديك
use App\Models\Shared\SubMunicipality; // تأكد من مسار الـ SubMunicipality

class Zone extends Model
{
    protected $fillable = ['name', 'sub_municipality_id'];

    /**
     * المنطقة الدقيقة تتبع بلدية فرعية واحدة
     */
    public function subMunicipality(): BelongsTo
    {
        return $this->belongsTo(SubMunicipality::class, 'sub_municipality_id');
    }

    /**
     * المنطقة الدقيقة يمكن أن يختارها العديد من السائقين
     * ملاحظة: تم التأكد من اسم الجدول الوسيط (driver_zones) كما اتفقنا سابقاً
     */
    public function drivers(): BelongsToMany
{
    // تأكد من استخدام 'driver_zone' كما هو في قاعدة بياناتك
    return $this->belongsToMany(\App\Models\Driver\Driver::class, 'driver_zone', 'zone_id', 'driver_id')
                ->withTimestamps();
}
}