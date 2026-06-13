<?php

namespace App\Http\Resources\Api\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Shared\Clause;

class ContractResource extends JsonResource
{
    /**
     * تحويل الموديل إلى مصفوفة قابلة للإرسال كـ JSON
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'subscription_request_id' => $this->subscription_request_id,
            'price'                   => (float) $this->price,
            'pickup_time'             => $this->pickup_time,
            'dropoff_time'            => $this->dropoff_time,
            'max_waiting_time'        => $this->max_waiting_time,
            
            // جلب نصوص الشروط بدلاً من أرقامها فقط
            'clauses'                 => Clause::whereIn('id', $this->selected_clauses ?? [])
                                                ->get(['id', 'clause_text', 'category']),
            
            // جلب بيانات الأطفال ومدارسهم المرتبطة بالطلب
            'children'                => $this->relationLoaded('subscriptionRequest') ? 
                                         $this->subscriptionRequest->children->map(function ($child) {
                                             return [
                                                 'name'   => $child->full_name,
                                                 'school' => $child->school->name ?? 'غير محددة',
                                                 'grade'  => $child->grade,
                                             ];
                                         }) : null,
            
            'status'                  => $this->status,
            'status_text'             => $this->translateStatus($this->status),
            
            'created_at'              => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'              => $this->updated_at?->format('Y-m-d H:i:s'),

            'parent' => $this->relationLoaded('parent') ? [
                'id'    => $this->parent->id,
                'name'  => $this->parent->full_name,    // تم التعديل إلى full_name
                'phone' => $this->parent->phone_number,
                
            ] : null,

            'driver' => $this->relationLoaded('driver') ? [
                'id'    => $this->driver->id,
                'name'  => $this->driver->full_name,    // تم التعديل إلى full_name
                'phone' => $this->driver->phone_number,
            ] : null,
        ];
    }

    /**
     * دالة مساعدة لترجمة حالة العقد
     */
    private function translateStatus(string $status): string
    {
        return match ($status) {
            'pending_parent_approval' => 'بانتظار موافقة وتوقيع ولي الأمر',
            'activated'               => 'مفعّل وساري العمل به',
            'rejected'                => 'تم رفض العقد من قِبل ولي الأمر',
            default                   => 'حالة غير معروفة',
        };
    }
}