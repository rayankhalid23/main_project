<?php

namespace App\Http\Resources\Api\Parent;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DriverMatchResource extends JsonResource
{
    public function toArray($request): array
    {
        // نصل للسائق ثم للمستخدم المرتبط به
        $user = $this->user;
        
        return [
            'id'                => $this->id,
            // التعديل هنا: استخدام full_name و phone_number
            'name'              => $user->full_name ?? 'غير متوفر',
            'phone'             => $user->phone_number ?? 'غير متوفر',
            
            'photo_url'         => ($user && $user->avatar_url) 
                                    ? asset(Storage::url($user->avatar_url)) 
                                    : asset('assets/images/default-driver.png'),
            
            'gender'            => $this->gender,
            'rating'            => number_format((float)($this->rating_avg ?? 5.0), 1),
            'completed_trips'   => $this->completed_trips_count ?? 0,
            
            // نستخدم العلاقة 'vehicles' (جمع) كما في الموديل
            'has_ac' => (bool) ($this->vehicles && $this->vehicles->isNotEmpty() ? $this->vehicles->first()->has_ac : false),
            'subscription_type' => $this->subscription_type,
            'working_zones'     => $this->zones->pluck('name'), // جلب أسماء المناطق
            
            'estimated_total_price' => isset($this->estimated_total_price) 
                                        ? number_format($this->estimated_total_price, 2) . ' د.ل' 
                                        : '0.00 د.ل',
            
            'children_included' => ChildResource::collection($this->when(isset($this->children), $this->children)),               
            'debug_info' => $this->debug_trace ?? 'لا يوجد سجل',             
        ];
    }
}