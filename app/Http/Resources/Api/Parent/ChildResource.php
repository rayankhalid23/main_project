<?php

namespace App\Http\Resources\Api\Parent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ChildResource extends JsonResource
{
    /**
     * تحويل كائن الطفل إلى مصفوفة JSON منسقة واحترافية.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'parent_id'        => $this->parent_id,
            'school_id'        => $this->school_id,
            'home_address_id'  => $this->home_address_id,
            
            'full_name'        => $this->full_name,
            
            // تنسيق تاريخ الميلاد ليظهر بشكل نصي واضح YYYY-MM-DD
            'birth_date'       => $this->birth_date ? $this->birth_date->format('Y-m-d') : null,
            
            // جلب العمر المحسوب ديناميكياً من الـ Accessor الذي وضعناه في الموديل
            'age'              => $this->age,
            
            'grade'            => $this->grade,
            
            // معالجة رابط الصورة: إذا كانت الصورة موجودة، يرجع النظام الرابط الكامل لها على السيرفر
            // وإذا لم تكن موجودة، يرجع رابطاً افتراضياً لصورة طفل (Placeholder)
            'photo_url'        => $this->photo 
                                    ? asset(Storage::url($this->photo)) 
                                    : asset('assets/images/default-child.png'),
            
            'medical_notes'    => $this->medical_notes ?? 'لا توجد ملاحظات طبية',
            'qr_code_token'          => $this->qr_code_token,
            'preferred_time_slot' => $this->preferred_time_slot ?? 'BOTH',
            
        

            // =======================================================
            // تضمين العلاقات بشكل ذكي وآمن (Conditional Loading)
            // =======================================================
            
            // لن تظهر بيانات المدرسة إلا إذا قام الـ Controller بطلبها عبر الـ Eager Loading
            'school'           => $this->whenLoaded('school', function() {
                return [
                    'id'           => $this->school->id,
                    'name'         => $this->school->name,
                    'address_text' => $this->school->address_text,
                ];
            }),

            // لن تظهر بيانات عنوان الانطلاق إلا إذا كانت محملة مسبقاً
            'address'          => $this->whenLoaded('address', function() {
                return [
                    'id'         => $this->address->id,
                    'label'      => $this->address->label,
                    'lat'        => $this->address->lat,
                    'lng'        => $this->address->lng,
                ];
            }),
        ];
    }
}