<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Driver\UpdateDriverPreferencesRequest;
use App\Services\Driver\DriverPreferenceService;
use App\Http\Resources\Api\Driver\DriverPreferenceResource;
use App\Models\Shared\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class DriverPreferenceController extends Controller
{
    protected DriverPreferenceService $preferenceService;

    public function __construct(DriverPreferenceService $preferenceService)
    {
        $this->preferenceService = $preferenceService;
    }

    /**
     * 🔍 1. دالة العرض: استعراض تفضيلات ومناطق السائق الحالية
     */
    public function show(Request $request): JsonResponse
    {
        $driver = $request->user()->driver;
        if (!$driver) {
            return response()->json(['status' => false, 'message' => 'ملف التعريف غير موجود.'], 404);
        }

        $preferences = $this->preferenceService->getPreferences($driver);

        return response()->json([
            'status' => true,
            'data'   => new DriverPreferenceResource($preferences)
        ], Response::HTTP_OK);
    }

    /**
     * 🔄 2. دالة التحديث الشامل (الفترة + المناطق معاً)
     */
    public function update(UpdateDriverPreferencesRequest $request): JsonResponse
    {
        try {
            $driver = $request->user()->driver;
            if (!$driver) {
                return response()->json(['status' => false, 'message' => 'ملف التعريف غير موجود.'], 404);
            }

            $updatedDriver = $this->preferenceService->updatePreferences($driver, $request->validated());

            return response()->json([
                'status'  => true,
                'message' => 'تم تحديث منطقة العمل والفترات الزمنية بنجاح وتطبيقها في النظام.',
                'data'    => new DriverPreferenceResource($updatedDriver)
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * ➕ 3. دالة إضافة منطقة منفردة لتفضيلات السائق
     */
    public function addZone(Request $request): JsonResponse
    {
        $request->validate([
            'zone_id' => ['required', 'integer', 'exists:zones,id']
        ], ['zone_id.exists' => 'المنطقة المحددة غير مسجلة بالنظام.']);

        $driver = $request->user()->driver;
        $updatedDriver = $this->preferenceService->addZoneToDriver($driver, $request->zone_id);

        return response()->json([
            'status'  => true,
            'message' => 'تم إضافة المنطقة الجديدة لتفضيلاتك بنجاح.',
            'data'    => new DriverPreferenceResource($updatedDriver)
        ], Response::HTTP_OK);
    }

    /**
     * ➖ 4. دالة إزالة منطقة منفردة من تفضيلات السائق
     */
    public function removeZone(Request $request): JsonResponse
    {
        $request->validate([
            'zone_id' => ['required', 'integer']
        ]);

        $driver = $request->user()->driver;
        $updatedDriver = $this->preferenceService->removeZoneFromDriver($driver, $request->zone_id);

        return response()->json([
            'status'  => true,
            'message' => 'تم إزالة المنطقة من تفضيلاتك بنجاح.',
            'data'    => new DriverPreferenceResource($updatedDriver)
        ], Response::HTTP_OK);
    }

    /**
     * ⚙️ 5. دالة الخيارات الافتراضية للنظام (مهمة جداً للـ Front-end لبناء واجهة الاختيارات)
     */
    public function defaults(): JsonResponse
    {
        $defaults = $this->preferenceService->getSystemDefaults();

        return response()->json([
            'status' => true,
            'data'   => $defaults
        ], Response::HTTP_OK);
    }
}