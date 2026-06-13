<?php

namespace App\Http\Controllers\API\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Driver\UpdateSubscriptionStatusRequest;
use App\Services\Shared\SubscriptionRequestService;
use App\Models\Shared\SubscriptionRequest;
use Illuminate\Http\JsonResponse;
use Exception;

class DriverSubscriptionController extends Controller
{
    protected SubscriptionRequestService $subscriptionService;

    public function __construct(SubscriptionRequestService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * عرض جميع الطلبات الواردة للسائق الحالي
     */
    public function index(): JsonResponse
    {
        $driver = auth()->user()->driver;

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'عذراً، هذا الحساب غير معرف كسائق في النظام.'
            ], 403);
        }

        $requests = SubscriptionRequest::where('driver_id', $driver->id)
            ->with(['parent.user', 'school', 'children']) 
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $requests
        ], 200);
    }

    /**
     * تحديث حالة الطلب من قبل السائق
     */
    public function updateStatus(UpdateSubscriptionStatusRequest $request, $id): JsonResponse
    {
        try {
            $driver = auth()->user()->driver;

            if (!$driver) {
                return response()->json([
                    'success' => false,
                    'message' => 'عذراً، لا تمتلك صلاحية سائق لتنفيذ هذا الإجراء.'
                ], 403);
            }

            // التحقق الصارم من ملكية الطلب للسائق الحالي
            $subscriptionRequest = SubscriptionRequest::where('id', $id)
                ->where('driver_id', $driver->id)
                ->firstOrFail();

            // منع التعديل التكراري لحماية منطق العمل (Business Logic)
            if ($subscriptionRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'عذراً، هذا الطلب تم اتخاذ قرار فيه مسبقاً ولا يمكن تعديله.'
                ], 422);
            }

            // معالجة تحديث الحالة عبر الـ Service
            $this->subscriptionService->updateStatus(
                $subscriptionRequest,
                $request->status,
                $request->rejection_reason
            );

            $message = $request->status === 'accepted' 
                ? 'تم قبول الطلب بنجاح وتوليد عقود الاشتراكات للأطفال تلقائياً.' 
                : 'تم رفض الطلب وإرسال الملاحظة لولي الأمر.';

            return response()->json([
                'success' => true,
                'message' => $message
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة الطلب.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}