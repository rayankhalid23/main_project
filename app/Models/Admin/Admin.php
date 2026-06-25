<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Admin extends Model
{
    // ملاحظة: إذا كان الجدول يحتوي على أعمدة created_at، 
    // فمن الأفضل حذف 'public $timestamps = false;' لكي يقوم لارافيل بإدارة التواريخ تلقائياً.
    protected $table = 'admins';

    protected $fillable = [
        'user_id',
        'created_by'
    ];

    /**
     * علاقة المشرف بحسابه الأساسي في جدول المستخدمين
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * علاقة المشرف بالشخص الذي قام بإنشائه (المشرف الآخر)
     * هنا نربط بـ Admin وليس بـ User لأن created_by هو معرف المشرف
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}