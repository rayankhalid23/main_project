<?php

namespace App\Http\Resources\Api\Parent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParentResource extends JsonResource
{
    /**
     * تحويل المورد إلى مصفوفة (Array)
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'full_name'    => $this->full_name,
            'phone_number' => $this->phone_number,
            'is_active'    => (bool) $this->is_active,
            'role_id'      => $this->role_id,
            // هنا يمكنك إضافة أي علاقة (Relationship) إذا قمت بتحميلها مسبقاً (Eager Loading)
            // 'children'  => ChildResource::collection($this->whenLoaded('children')),
            'created_at'   => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}