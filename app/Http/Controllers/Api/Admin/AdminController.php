<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Admin;
use App\Services\Admin\AdminService;
use App\Http\Requests\Api\Admin\StoreAdminRequest;
use App\Http\Requests\Api\Admin\UpdateAdminRequest;
use App\Http\Resources\Api\Admin\AdminResource;
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
            $admin = $this->adminService->createAdmin(
                $request->validated(), 
                $request->file('avatar_url')
            );

            return response()->json([
                'status'  => true,
                'message' => 'تم إضافة المشرف بنجاح.',
                'data'    => new AdminResource($admin)
            ], 201);
        } catch (Exception $e) {
            Log::error("Store Admin Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'تعذر إضافة المشرف.'], 500);
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
            return response()->json(['status' => false, 'message' => 'حدث خطأ في النظام.'], 500);
        }
    }

    /**
     * تعديل بيانات المشرف
     */
    public function update(UpdateAdminRequest $request, $id): JsonResponse
    {
        try {
            $admin = Admin::find($id);

            if (!$admin) {
                return response()->json(['status' => false, 'message' => 'عذراً، المشرف غير موجود.'], 404);
            }

            $updatedAdmin = $this->adminService->updateAdmin(
                $admin, 
                $request->validated(), 
                $request->file('avatar_url')
            );

            return response()->json([
                'status'  => true,
                'message' => 'تم تحديث بيانات المشرف بنجاح.',
                'data'    => new AdminResource($updatedAdmin)
            ], 200);
        } catch (Exception $e) {
            Log::error("Update Admin Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'تعذر تحديث البيانات.'], 500);
        }
    }
}