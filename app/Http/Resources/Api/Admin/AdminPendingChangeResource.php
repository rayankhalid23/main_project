<?php

namespace App\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPendingChangeResource extends JsonResource
{
    /**
     * تحويل الكائن إلى مصفوفة JSON منسقة ومطابقة لطلب الأدمن
     */
    public function toArray(Request $request): array
    {
        // التحقق مما إذا كانت هذه البيانات قادمة من استعلام Query (كـ stdClass) أو موديل موديل حقيقي
        $oldValues = is_string($this->old_values) ? json_decode($this->old_values, true) : $this->old_values;
        $newValues = is_string($this->new_values) ? json_decode($this->new_values, true) : $this->new_values;

        return [
            'request_id'        => $this->id ?? $this->request_id,
            'driver_id'         => $this->driver_id,
            'driver_name'       => $this->driver_name ?? $this->current_driver_name ?? null,
            'driver_phone'      => $this->driver_phone ?? $this->current_driver_phone ?? null,
            'status'            => $this->status,
            'old_values'        => $oldValues ?? [],
            'new_values'        => $newValues ?? [],
            'rejection_reason'  => $this->rejection_reason ?? null,
            'created_at'        => isset($this->created_at) ? date('Y-m-d H:i:s', strtotime($this->created_at)) : null,
        ];
    }
}