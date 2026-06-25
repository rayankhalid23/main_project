<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shared\Zone;
use App\Services\Shared\ZoneService;
use App\Http\Requests\Api\Admin\StoreZoneRequest; // 👈 تم الدمج
use App\Http\Resources\Api\Shared\ZoneResource;       // 👈 تم الدمج
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ZoneController extends Controller
{
    protected ZoneService $zoneService;

    public function __construct(ZoneService $zoneService)
    {
        $this->zoneService = $zoneService;
    }

    /**
     * 1. عرض كافة المناطق (متاح للسائقين، أولياء الأمور، والآدمن)
     */
    public function index(): JsonResponse
    {
        $zones = $this->zoneService->getAllZones();
        
        return response()->json([
            'status' => true,
            'data'   => ZoneResource::collection($zones) // تحويل القائمة كاملة عبر الـ Resource لتوحيد البيانات
        ], Response::HTTP_OK);
    }

    /**
     * 2. إضافة منطقة جديدة (Admin) مع منع التكرار التلقائي
     */
    public function store(StoreZoneRequest $request): JsonResponse
    {
        try {
            // استخدام البيانات المفحوصة والمضمونة من الـ FormRequest
            $zone = $this->zoneService->createZone($request->validated());
            
            return response()->json([
                'status'  => true,
                'message' => 'تم إضافة المنطقة بنجاح.',
                'data'    => new ZoneResource($zone) // تنسيق المخرجات بالـ Resource
            ], Response::HTTP_CREATED);
            
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false, 
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * 3. تعديل منطقة (Admin) مع تجاهل السجل الحالي أثناء فحص التكرار
     */
    public function update(StoreZoneRequest $request, $id): JsonResponse
    {
        try {
            $zone = Zone::findOrFail($id);
            
            // تمرير البيانات الآمنة للخدمة لتحديث الاسم
            $updatedZone = $this->zoneService->updateZone($zone, $request->validated());
            
            return response()->json([
                'status'  => true,
                'message' => 'تم تحديث اسم المنطقة بنجاح.',
                'data'    => new ZoneResource($updatedZone) // تنسيق المخرجات بالـ Resource
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false, 
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * 4. حذف منطقة (Admin) مع حماية علاقات السائقين النشطين
     */
    public function destroy($id): JsonResponse
    {
        try {
            $zone = Zone::findOrFail($id);
            $this->zoneService->deleteZone($zone);
            
            return response()->json([
                'status'  => true,
                'message' => 'تم حذف المنطقة من النظام بنجاح.'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false, 
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}