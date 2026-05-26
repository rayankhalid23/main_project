<?php

namespace App\Http\Resources\Api\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Exception;

class DriverResource extends JsonResource
{
    /**
     * تحويل كائن السائق إلى مصفوفة JSON احترافية آمنة
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        try {
            // التحقق من وجود البيانات الأساسية لتفادي أخطاء محاولة القراءة من كائن فارغ (Null-Safe)
            if (!$this->resource) {
                return [];
            }

            return [
                // 1. البيانات الشخصية والحساب (دمج من جدول users إذا كانت العلاقة محملة)
                'id'            => (int) $this->id,
                'account_id'    => (int) $this->user_id,
                'full_name'     => $this->user?->full_name ?? '',
                'phone_number'  => $this->user?->phone_number ?? '',
                'avatar_url'    => $this->user?->avatar_url ? url($this->user->avatar_url) : null,
                'is_active'     => (bool) ($this->user?->is_active ?? false),

                // 2. البيانات المهنية للسائق (من جدول drivers)
                'national_id'    => $this->national_id,
                'license_number' => $this->license_number,
                'license_expiry' => $this->license_expiry,
                'driver_status'  => $this->status ?? 'Pending',
                
                // بيانات تتبع الموقع الفورية (تظهر إذا وجدت قيم في قاعدة البيانات)
                'location' => [
                    'lat' => $this->current_lat ? (float) $this->current_lat : null,
                    'lng' => $this->current_lng ? (float) $this->current_lng : null,
                    'last_ping' => $this->last_ping_at,
                ],

                // 3. بيانات المركبة (تظهر فقط إذا كانت العلاقة محملة مع السائق من خلال with)
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
                        'vehicle_image_url' => $vehicle->vehicle_image_url ? url($vehicle->vehicle_image_url) : null,
                        'status'            => $vehicle->status,
                    ];
                }) : [],

                // 4. بيانات الوثائق والمستندات المرفوعة (تظهر فقط إذا كانت العلاقة محملة)
                'documents' => $this->relationLoaded('documents') ? $this->documents->map(function ($doc) {
                    return [
                        'id'         => (int) $doc->id,
                        'doc_type'   => $doc->doc_type,
                        'file_url'   => $doc->file_url ? url($doc->file_url) : null,
                        'status'     => $doc->status,
                        'feedback'   => $doc->feedback, // يظهر سبب الرفض في حال رفض المشرف للورقة
                    ];
                }) : [],

                // التواريخ الزمنية للسجل
                'created_at' => $this->created_at?->toDateTimeString(),
                'updated_at' => $this->updated_at?->toDateTimeString(),
            ];

        } catch (Exception $e) {
            // معالجة استباقية وحماية في حال حدوث عطل غير متوقع أثناء معالجة البيانات وتحويل الأنواع (Type Casting)
            Log::error("DriverResource Data Formatting Error: " . $e->getMessage(), [
                'driver_id' => $this->id ?? 'N/A'
            ]);

            return [
                'error'   => true,
                'message' => 'حدث خطأ تقني أثناء تنسيق بيانات السائق للاستجابة.',
                'debug'   => config('app.debug') ? $e->getMessage() : null
            ];
        }
    }
}