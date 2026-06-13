<?php

namespace App\Http\Controllers\API\Parent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Shared\StoreSubscriptionRequest;
use App\Services\Shared\SubscriptionRequestService;
use App\Models\Shared\SubscriptionRequest;
use Illuminate\Http\JsonResponse;
use Exception;

class ParentSubscriptionController extends Controller
{
    protected SubscriptionRequestService $subscriptionService;

    public function __construct(SubscriptionRequestService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * إنشاء طلب اشتراك جديد
     */
    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        try {
            $userId = auth()->id(); 

            // 1. تنفيذ الخدمة وحفظ البيانات في الجداول (بما فيها الجدول الوسيط)
            $result = $this->subscriptionService->createRequest($request->validated(), $userId);

            // 2. جلب الموديل من قاعدة البيانات
            $subscriptionRequest = SubscriptionRequest::findOrFail($result['request_id']);

            // 3. 💡 الحل هنا: عمل refresh لإجبار كاش الموديل على قراءة السجلات الوسيطة الجديدة
            $subscriptionRequest->refresh(); 

            // 4. الآن نقوم بتحميل الأطفال وبيانات الـ pivot بأمان
            $subscriptionRequest->load('children');

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال طلب الاشتراك بنجاح وفي انتظار رد السائق.',
                'data'    => $subscriptionRequest
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'عذراً، حدث خطأ أثناء معالجة الطلب البرمجي.',
                'error'   => $e->getMessage() 
            ], 500);
        }
    }

    /**
     * جلب كافة طلبات الاشتراكات الخاصة بولي الأمر الحالي
     */
    public function index(): JsonResponse
    {
        // جلب سجل ولي الأمر المرتبط بالمستخدم، وإذا لم يوجد نرجع مصفوفة فارغة لحماية التطبيق من الانهيار
        $parent = auth()->user()->parent;
        
        if (!$parent) {
            return response()->json([
                'success' => true,
                'data'    => []
            ], 200);
        }

        $requests = SubscriptionRequest::where('parent_id', $parent->id)
            ->with(['driver.user', 'school', 'children']) 
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $requests
        ], 200);
    }
}