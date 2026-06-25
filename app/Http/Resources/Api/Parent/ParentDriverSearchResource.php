<?php

namespace App\Http\Resources\Api\Parent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class ParentDriverSearchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // جلب أول مركبة للسائق لحساب سعتها
        $activeVehicle = $this->vehicles?->first(); 
        $totalCapacity = $activeVehicle ? $activeVehicle->capacity_manual : 0;
        
        // حساب عدد الطلاب المشتركين من جدول الطلاب إن وجد
        $currentStudentsCount = \Illuminate\Support\Facades\Schema::hasTable('students')
            ? DB::table('students')->where('driver_id', $this->id)->count()
            : 0;

        $availableSeats = $totalCapacity - $currentStudentsCount;

        return [
            'id_driver'       => $this->id,
            'full_name'       => $this->user?->full_name,
            'grand'           => $this->shift, // أو التسمية المتوافقة مع نوع الحقل لديك
            'الجنس'           => $this->gender == 'male' ? 'ذكر' : 'أنثى',
            'التقييم'         => round($this->rating_avg ?? 4.5, 1),
            'available_seats' => $availableSeats < 0 ? 0 : $availableSeats, 
        ];
    }
}