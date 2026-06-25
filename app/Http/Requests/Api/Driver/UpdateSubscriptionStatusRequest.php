<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Admin;
use App\Services\Admin\AdminService;
use App\Http\Requests\Api\Admin\StoreAdminRequest;
use App\Http\Requests\Api\Admin\UpdateAdminRequest;
// تم تعديل الاستدعاء هنا ليتوافق مع الملف المطور الخاص بالموافقة والرفض
use App\Http\Requests\Api\Admin\ProcessDriverApprovalRequest; 
use App\Http\Resources\Api\Admin\AdminResource;
use App\Http\Resources\Api\Driver\DriverResource; // استيراد ريسورس السائق بشكل صحيح
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class AdminController extends Controller
{
    protected AdminService $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * عرض قائمة المشرفين
     */
    public function index(): JsonResponse
    {
        try {
            $admins = $this->adminService->getAllAdmins();
            return response()->json([
                'status'  => true,
                'message' => 'تم جلب قائمة المشرفين بنجاح.',
                'data'    => AdminResource::collection($admins)->response()->getData(true)
            ], 200);
        } catch (Exception $e) {
            Log::error("Fetch Admins Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ في النظام.'], 500);
        }
    }

    /**
     * إضافة مشرف جديد
     */
    public function store(StoreAdminRequest $request): JsonResponse
    {
        try {
            // استخدام حقل الملف النظيف 'avatar' تماشياً مع قواعد التحقق
            $admin = $this->adminService->createAdmin(
                $request->validated(), 
                $request->file('avatar')
            );

            return response()->json([
                'status'  => true,
                'message' => 'تم إضافة المشرف بنجاح.',
                'data'    => new AdminResource($admin)
            ], 201);
        } catch (Exception $e) {
            Log::error("Store Admin Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'تعذر إضافة المشرف الجديد للأنظمة.'], 500);
        }
    }

    /**
     * عرض مشرف واحد بالتحديد
     */
    public function show($id): JsonResponse
    {
        try {
            $admin = Admin::with(['user', 'creator'])->find($id);
            
            if (!$admin) {
                return response()->json(['status' => false, 'message' => 'عذراً، المشرف غير موجود.'], 404);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب بيانات المشرف.',
                'data'    => new AdminResource($admin)
            ], 200);
        } catch (Exception $e) {
            Log::error("Show Admin Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ في النظام أثناء جلب البيانات.'], 500);
        }
    }

    /**
     * تعديل بيانات المشرف جزئياً
     */
    public function update(UpdateAdminRequest $request, $id): JsonResponse
    {
        try {
            $admin = Admin::find($id);

            if (!$admin) {
                return response()->json(['status' => false, 'message' => 'عذراً، المشرف المطلوب تعديله غير موجود.'], 404);
            }

            // تعديل الحقل المرفوع إلى 'avatar' لتطابق المدخلات والمخرجات
            $updatedAdmin = $this->adminService->updateAdmin(
                $admin, 
                $request->validated(), 
                $request->file('avatar')
            );

            return response()->json([
                'status'  => true,
                'message' => 'تم تحديث بيانات المشرف بنجاح.',
                'data'    => new AdminResource($updatedAdmin)
            ], 200);
        } catch (Exception $e) {
            Log::error("Update Admin Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'تعذر تحديث البيانات، يرجى المحاولة لاحقاً.'], 500);
        }
    }

    /**
     * معالجة طلب السائق (قبول أو رفض) من قِبل المشرف الإداري
     */
    public function updateDriverStatus(ProcessDriverApprovalRequest $request, $driverId): JsonResponse
    {
        try {
            // 1. التأكد من هوية المشرف وتأكيد صلاحياته
            $adminId = auth()->id();
            if (!$adminId) {
                return response()->json(['status' => false, 'message' => 'غير مصرح لك بالقيام بهذا الإجراء الإداري.'], 403);
            }
    
            // 2. استدعاء الخدمة لتحديث البيانات عبر الـ Database Transaction
            $driver = $this->adminService->processDriverApproval(
                (int)$driverId, 
                (int)$adminId, 
                $request->validated('status'), 
                $request->validated('rejection_reason')
            );
    
            // 3. التنسيق الاحترافي لبيانات السائق المرجعة عبر الريسورس المخصص له
            return response()->json([
                'status'  => true,
                'message' => 'تم تحديث حالة السائق ومعالجة الطلب بنجاح.',
                'data'    => new DriverResource($driver)
            ], 200);
    
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database Error in updateDriverStatus: ' . $e->getMessage());
            return response()->json([
                'status'  => false, 
                'message' => 'خطأ داخلي في قاعدة البيانات: ' . $e->getMessage()
            ], 500);
            
        } catch (Exception $e) {
            Log::error('General Error in updateDriverStatus: ' . $e->getMessage());
            return response()->json([
                'status'  => false, 
                'message' => 'حدث خطأ غير متوقع أثناء معالجة حالة السائق: ' . $e->getMessage()
            ], 500);
        }
    }
}