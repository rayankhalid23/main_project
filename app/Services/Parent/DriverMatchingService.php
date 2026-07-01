<?php

namespace App\Services\Parent;

use App\Models\Parent\Child;
use App\Models\Driver\Driver;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class DriverMatchingService
{

    /**
 * محرك الفلترة الرئيسي
 */
public function matchDrivers(array $filters, int $parentId)
{
    // 1. بدء الاستعلام مع العلاقات الصحيحة
    $query = Driver::with(['user', 'zones', 'vehicles']) 
        ->where('status', 'Approved');

    // 2. إذا كان هناك بحث نصي، نتجاهل الفلترة الذكية ونرجع النتائج
    if (!empty($filters['search_query'])) {
        return $this->applyTextSearch($query, $filters['search_query']);
    }

    // 3. تطبيق الفلاتر اليدوية
    if (!empty($filters['driver_gender'])) {
        $query->where('gender', $filters['driver_gender']);
    }

    if (isset($filters['has_ac']) && $filters['has_ac'] == true) {
        $query->whereHas('vehicles', function ($q) {
            $q->where('has_ac', true);
        });
    }

    // 4. بناء سياق الأطفال للفلترة الذكية
    $childrenContext = $this->buildChildrenContext($filters['child_ids'] ?? [], $parentId);

    if ($childrenContext) {
        if (!empty($childrenContext['zones'])) {
            $query->whereHas('zones', function ($q) use ($childrenContext) {
                $q->whereIn('zone_id', $childrenContext['zones']);
            });
        }

        $query->where(function($q) use ($childrenContext) {
            $q->where('accepted_gender', 'both')
              ->orWhere('accepted_gender', $childrenContext['required_gender']);
        });

        if (!empty($childrenContext['subscription_types'])) {
            $query->where(function($q) use ($childrenContext) {
                $q->where('subscription_type', 'both');
                foreach ($childrenContext['subscription_types'] as $subType) {
                    $q->orWhere('subscription_type', $subType);
                }
            });
        }

        if (!empty($childrenContext['grades'])) {
            $query->where(function ($q) use ($childrenContext) {
                foreach ($childrenContext['grades'] as $grade) {
                    $q->whereJsonContains('preferred_grades', (string) $grade);
                }
            });
        }
    }

    // 5. جلب النتائج مع الترتيب
    $drivers = $query->orderByDesc('rating_avg')
                     ->orderByDesc('completed_trips_count')
                     ->paginate(15);

    // 6. جلب الأطفال (المنطق الجديد: جلب الكل إذا لم يحدد المستخدم أطفالاً)
    $queryChildren = Child::with(['address', 'school', 'logistics'])
        ->where('parent_id', $parentId);

    if (!empty($filters['child_ids']) && is_array($filters['child_ids'])) {
        $queryChildren->whereIn('id', $filters['child_ids']);
    }

    $children = $queryChildren->get();

    // 7. تحويل النتائج وحساب السعر (فقط إذا وجدنا أطفالاً)
    if ($children->isNotEmpty()) {
        $drivers->getCollection()->transform(function ($driver) use ($children) {
            $driver->estimated_total_price = $this->calculateTotalPriceForDriver($driver, $children);
            $driver->children = $children; 
            return $driver;
        });
    }

    return $drivers;
}



    /**
     * دالة استخراج القواسم المشتركة للأطفال
     */
    private function buildChildrenContext(array $childIds, int $parentId): ?array
    {
        

        $query = Child::with(['school', 'logistics'])->where('parent_id', $parentId);
        
        if (!empty($childIds)) {
            $query->whereIn('id', $childIds);
        }

        $children = $query->get();
        if ($children->isEmpty()) return null;

        // تحديد متطلبات الجنس (إذا تم اختيار ذكر وأنثى، يجب أن يقبل السائق 'both')
        $genders = $children->pluck('gender')->unique()->values()->toArray();
        $requiredGender = count($genders) > 1 ? 'both' : $genders[0];

        return [
            'zones'              => $children->pluck('school.zone_id')->filter()->unique()->toArray(),
            'subscription_types' => $children->pluck('logistics.subscription_type')->filter()->unique()->toArray(),
            'grades'             => $children->pluck('grade')->unique()->toArray(),
            'required_gender'    => $requiredGender,
        ];
    }

    /**
     * دالة البحث النصي
     */
    private function applyTextSearch($query, $searchQuery)
    {
        return $query->whereHas('user', function ($q) use ($searchQuery) {
            $q->where('name', 'LIKE', "%{$searchQuery}%")
              ->orWhere('phone', 'LIKE', "%{$searchQuery}%");
        })->paginate(15);
    }
    
    private function calculateTotalPriceForDriver($driver, $children)
    {
        $totalPrice = 0;
        $trace = []; // مصفوفة لتخزين كل خطوة
        
        foreach ($children as $child) {
            $trace[] = "--- فحص الطفل ID: {$child->id} ---";
            
            if (!$child->address || !$child->school) {
                $trace[] = "خطأ: الطفل مفقود العنوان أو المدرسة";
                continue;
            }
    
            $distance = $this->calculateHaversineDistance(
                $child->address->lat, $child->address->lng,
                $child->school->lat, $child->school->lng
            );
            $trace[] = "المسافة المحسوبة: " . $distance . " كم";
    
            $startDate = \Carbon\Carbon::parse($child->logistics->start_date);
            $endDate = \Carbon\Carbon::parse($child->logistics->end_date);
            $workingDays = $startDate->diffInDaysFiltered(fn($d) => !$d->isFriday() && !$d->isSaturday(), $endDate) ?: 1;
            
            $trace[] = "عدد أيام العمل: " . $workingDays;
            
            $childCost = ($distance * 1.00) * $workingDays;
            $totalPrice += $childCost;
            $trace[] = "تكلفة الطفل: " . $childCost;
        }
    
        // هنا السر: سنضع الـ trace داخل السائق ليظهر في الـ API
        $driver->debug_trace = $trace; 
        
        return round($totalPrice, 2);
    }
   



    private function calculateHaversineDistance(?float $lat1, ?float $lon1, ?float $lat2, ?float $lon2): float
    {
        if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) {
            return 0.0;
        }
    
        $earthRadius = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
    
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
}