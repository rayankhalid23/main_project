<?php

namespace App\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminResource extends JsonResource
{
    /**
     * تحويل المورد إلى مصفوفة (Array)
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // بيانات جدول المشرفين (Admins)
            'id'           => $this->id,
            'user_id'      => $this->user_id,
            
            // البيانات المدمجة من جدول المستخدمين (Users) لتسهيل القراءة للفرونت إند
            'full_name'    => $this->user->full_name ?? null,
            'phone_number' => $this->user->phone_number ?? null,
            
            // تجهيز رابط الصورة ليعمل فوراً (أو إرجاع null إذا لم توجد صورة)
            'avatar_url'   => $this->user->avatar_url ? asset($this->user->avatar_url) : null,
            
            // إجبار نوع البيانات ليكون Boolean
            'is_active'    => (bool) ($this->user->is_active ?? false),
            
            // اسم المشرف أو النظام الذي قام بإنشاء هذا الحساب (عبر علاقة creator)
            'creator_name' => $this->creator->full_name ?? 'النظام',
            
            // تنسيق التاريخ ليكون مقروءاً وموحداً
            'created_at'   => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}