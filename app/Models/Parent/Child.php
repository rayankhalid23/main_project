<?php

namespace App\Models\Parent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Models\ParentModel; // تأكد من المسار الصحيح

class Child extends Model
{
    protected $table = 'children';

    // يفضل تفعيل timestamps إذا كانت موجودة في الـ Migration، 
    // وإلا اتركها false كما هي في كودك القديم
    public $timestamps = true;

    protected $fillable = [
        'parent_id',
        'school_id',
        'address_id',
        'full_name',
        'birth_date',
        'gender',
        'grade',
        'photo_url',
        'medical_notes',
        'notification_radius',
        'qr_code_token',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    /**
     * توليد توكن فريد للـ QR Code تلقائياً عند إنشاء الطفل
     */
    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($child) {
            $child->qr_code_token = 'CHLD-' . Str::upper(Str::random(6)) . '-' . time();
        });
    }

    /**
     * =========================================
     * العلاقات (Relationships)
     * =========================================
     */

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentModel::class, 'parent_id');
    }
    public function logistics()
    {
        // الطفل لديه سجل لوجستي واحد في جدول child_logistics
        return $this->hasOne(\App\Models\Parent\ChildLogistics::class, 'child_id', 'id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Parent\School::class, 'school_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Parent\Address::class, 'address_id');
    }

    /**
     * الملحقات (Attributes)
     */
    public function getAgeAttribute(): int
    {
        return $this->birth_date ? $this->birth_date->age : 0;
    }
}