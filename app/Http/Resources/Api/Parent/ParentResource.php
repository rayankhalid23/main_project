<?php

namespace App\Http\Resources\Api\Parent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParentResource extends JsonResource
{
    /**
     * تحويل كائن ولي الأمر إلى مصفوفة JSON متوافقة مع التعديل الجزئي والعرض الاحترافي
     */
    public function toArray(Request $request): array
    {
        // التحقق الذكي من أصل الكائن الممرر (سواء كان User ومعه الـ profile أو العكس)
        $user = $this->user ?? ($this->resource instanceof \App\Models\User ? $this->resource : null);
        
        // جلب الـ profile التابع لجدول parents بشكل آمن
        $parentProfile = $this->parentProfile ?? ($this->resource instanceof \App\Models\User ? $this->parentProfile : $this);

        return [
            'id'                => (int) ($parentProfile?->id ?? $this->id),
            'account_id'        => (int) ($user?->id ?? $this->user_id),
            'full_name'         => $user?->full_name ?? $this->full_name ?? '',
            'email'             => $user?->email ?? $this->email ?? '',
            'phone_number'      => $user?->phone_number ?? $this->phone_number ?? '',
            'alternative_phone' => $user?->alternative_phone ?? $this->alternative_phone ?? null, 
            'role'              => 'parent',
            'is_active'         => (bool) ($user?->is_active ?? $this->is_active ?? false),
            
            // 🚀 إضافة الحقل الخاص بجدول الـ parents الذي قمنا بتحديثه في السيرفس
            'is_trusted'        => (bool) ($parentProfile?->is_trusted ?? false),

            // 🚀 التنبيه الذكي للـ Front-end: إذا كان هناك بريد إلكتروني قيد التحقق والتأكيد حالياً
            'email_change_pending' => (bool) ($user?->email_change_pending ?? $this->email_change_pending ?? false),
            'access_token'         => $this->when(isset($this->access_token) || isset($user->access_token), $this->access_token ?? $user?->access_token),
        ];
    }
}