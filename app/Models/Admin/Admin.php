<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Admin extends Model
{
    public $timestamps = false;
    // تحديد اسم الجدول في قاعدة البيانات
    protected $table = 'admins';
   

    // الحقول المسموح بتعبئتها جماعياً لحماية النظام من ثغرات الإدخال (Mass Assignment)
    protected $fillable = [
        'user_id',
        'created_by'
    ];

    /**
     * علاقة المشرف بحسابه الأساسي في جدول المستخدمين
     * كل مشرف هو في الأصل مستخدم (User) له اسم بريد وهاتف.
     * * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * علاقة المشرف بالشخص الذي قام بإنشائه (المدير الأعلى)
     * لمعرفة من الأدمن أو السوبر أدمن الذي أضاف هذا الحساب.
     * * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}