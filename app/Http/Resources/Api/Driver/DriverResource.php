<?php

namespace App\Http\Resources\Api\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Exception;

class DriverResource extends JsonResource
{
    /**
     * تحويل كائن السائق إلى مصفوفة JSON احترافية متوافقة مع التعديل والعرض
     */
    public function toArray(Request $request): array
    {
        try {
            if (!$this->resource) {
                return [];
            }

            return [
                // 1. البيانات الشخصية والحساب
                'id'            => (int) $this->id,
                'account_id'    => (int) $this->user_id,
                'full_name'     => $this->user?->full_name ?? '',
                'gender'        => $this->gender ?? '',
                'phone_number'  => $this->user?->phone_number ?? '',
                'alternative_phone' => $this->user?->alternative_phone ?? null,
                'email'         => $this->user?->email ?? '', 
                'new_email_temporary'  => $this->user?->new_email_temporary ?? null,
                'email_change_pending' => (bool) ($this->user?->email_change_pending ?? false),
                'avatar_url'    => $this->user?->avatar_url ? asset($this->user->avatar_url) : null,
                'is_active'     => (bool) ($this->user?->is_active ?? false),

                'access_token'      => $this->when(isset($this->access_token) || isset($this->user->access_token), $this->access_token ?? $this->user?->access_token),

                // 2. البيانات المهنية
                'national_id'    => $this->national_id,
                'license_number' => $this->license_number,
                'license_expiry' => $this->license_expiry,
                'driver_status'  => $this->status ?? 'Pending',
                
                'location' => [
                    'lat'       => $this->current_lat ? (float) $this->current_lat : null,
                    'lng'       => $this->current_lng ? (float) $this->current_lng : null,
                    'last_ping' => $this->last_ping_at,
                ],

                // 3. المركبات
                'vehicles' => $this->relationLoaded('vehicles') ? $this->vehicles->map(function ($vehicle) {
                    return [
                        'id'                => (int) $vehicle->id,
                        'plate_number'      => $vehicle->plate_number,
                        'brand'             => $vehicle->brand,
                        'model'             => $vehicle->model,
                        'year'              => (int) $vehicle->year,
                        'color'             => $vehicle->color,
                        'type'              => $vehicle->type,
                        'capacity'          => (int) $vehicle->capacity_manual,
                        'has_ac'            => (bool) $vehicle->has_ac,
                        'is_verified'       => (bool) $vehicle->is_verified,
                        'vehicle_image_url' => $vehicle->vehicle_image_url ? asset($vehicle->vehicle_image_url) : null,
                        'status'            => $vehicle->status,
                    ];
                }) : [],

                // 4. الوثائق
                'documents' => $this->relationLoaded('documents') ? $this->documents->map(function ($doc) {
                    return [
                        'id'          => (int) $doc->id,
                        'doc_type'    => $doc->doc_type,
                        'file_url'    => $doc->file_url ? asset($doc->file_url) : null,
                        'status'      => $doc->status,
                        'feedback'    => $doc->feedback,
                        'uploaded_at' => $doc->uploaded_at,
                    ];
                }) : [],
            ];

        } catch (Exception $e) {
            Log::error("DriverResource Error: " . $e->getMessage());
            return ['error' => true, 'message' => 'تعذر تنسيق بيانات السائق الشخصية.'];
        }
    }
}