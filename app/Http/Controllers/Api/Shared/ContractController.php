<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Shared\StoreContractRequest;
use App\Http\Resources\Api\Shared\ContractResource;
use App\Services\Shared\ContractService;
use App\Models\Shared\Clause; // 🔥 لاحظ إضافة \Shared\
use Exception;
use Illuminate\Http\JsonResponse;

class ContractController extends Controller
{
    protected ContractService $contractService;

    // حقن السيرفس داخل الكنترولر عبر الـ Constructor
    public function __construct(ContractService $contractService)
    {
        $this->contractService = $contractService;
    }

    /**
     * استقبال طلب إنشاء العقد من السائق
     */
    public function store(StoreContractRequest $request): JsonResponse
    {
        try {
            // استدعاء البيزنس لوجيك من السيرفس وتمرير البيانات الموثقة فقط
            $contract = $this->contractService->createContractForDriver($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء مسودة العقد بنجاح وبانتظار موافقة ولي الأمر.',
                'data'    => new ContractResource($contract)
            ], 201);

        } catch (Exception $e) {
            // التعامل مع الأخطاء المخصصة التي أطلقناها في السيرفس (مثل 403 أو 400)
            $statusCode = in_array($e->getCode(), [400, 403]) ? $e->getCode() : 500;
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    /**
     * موافقة ولي الأمر على العقد
     */
    public function accept($id): JsonResponse
    {
        try {
            $contract = $this->contractService->acceptContract($id);
            return response()->json([
                'success' => true,
                'message' => 'تم توقيع العقد بنجاح وبدأ سريان الاشتراك.',
                'data'    => new ContractResource($contract)
            ], 200);
        } catch (Exception $e) {
            $statusCode = in_array($e->getCode(), [400, 403]) ? $e->getCode() : 500;
            return response()->json(['success' => false, 'message' => $e->getMessage()], $statusCode);
        }
    }

    /**
     * رفض ولي الأمر للعقد
     */
    public function reject($id): JsonResponse
    {
        try {
            $contract = $this->contractService->rejectContract($id);
            return response()->json([
                'success' => true,
                'message' => 'تم رفض مسودة العقد وإلغاء الطلب.',
                'data'    => new ContractResource($contract)
            ], 200);
        } catch (Exception $e) {
            $statusCode = in_array($e->getCode(), [400, 403]) ? $e->getCode() : 500;
            return response()->json(['success' => false, 'message' => $e->getMessage()], $statusCode);
        }
    }
    public function clauses()
{
    // جلب كل الشروط من قاعدة البيانات وإرجاعها مجزأة حسب الأقسام أو مرتبة
    $clauses = Clause::all();
    return response()->json([
        'success' => true,
        'data' => $clauses
    ], 200);
}
}