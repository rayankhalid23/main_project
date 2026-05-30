<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Driver\ProfileUpdateRequest;
use App\Services\Driver\DriverProfileService;
use App\Http\Resources\Api\Driver\DriverResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProfileController extends Controller
{
    protected DriverProfileService $profileService;

    public function __construct(DriverProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * تحديث بيانات الملف الشخصي للسائق
     *
     * @param ProfileUpdateRequest $request
     * @return JsonResponse
     */
    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
$driver = $user->driver; // جلب العلاقة

if (!$driver) {
    return response()->json([
        'status' => false,
        'message' => 'عذراً، هذا الحساب غير مرتبط بملف سائق.'
    ], 404);
}
            $validatedData = $request->validated();
            
            $uploadedPaths = [];

            // معالجة وتحديث الصورة الشخصية إذا تم إرسالها في الطلب
            if ($request->hasFile('avatar_url')) {
                // مسح الصورة القديمة لتوفير المساحة على السيرفر (Best Practice)
                if ($user->avatar_url && Storage::disk('public')->exists(str_replace('storage/', '', $user->avatar_url))) {
                    Storage::disk('public')->delete(str_replace('storage/', '', $user->avatar_url));
                }

                $avatarPath = $request->file('avatar_url')->store('uploads/drivers/avatars', 'public');
                $validatedData['avatar_path'] = 'storage/' . $avatarPath;
                $uploadedPaths[] = $avatarPath;
            }

            // تمرير البيانات للـ Service للقيام بعملية التحديث
            $updatedDriver = $this->profileService->updateProfile($user, $validatedData);

            return response()->json([
                'status'  => true,
                'message' => 'تم تحديث بيانات الملف الشخصي بنجاح.',
                'data'    => new DriverResource($updatedDriver)
            ], 200); // 200 OK

        } catch (Exception $e) {
            // في حال الفشل نقوم بمسح الصورة الجديدة التي تم رفعها للتو
            if (!empty($uploadedPaths)) {
                foreach ($uploadedPaths as $path) {
                    Storage::disk('public')->delete($path);
                }
            }

            Log::error("Driver Profile Update Controller Error: " . $e->getMessage(), [
                'user_id' => auth()->id() ?? 'N/A'
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'تعذر تحديث البيانات بسبب مشكلة تقنية.',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }


    /**
     * عرض بيانات الملف الشخصي للسائق
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function show(\Illuminate\Http\Request $request): JsonResponse
    {
        try {
            // [احترافي]: استخدام Eager Loading لتحميل علاقة السائق دفعة واحدة من قاعدة البيانات
            // هذا يمنع لارافيل من تنفيذ استعلام إضافي عند استدعاء $user->driver لاحقاً
            $user = $request->user();
            $driver = $user->driver;

            // حارس الحماية (Guard Clause) للتأكد من وجود السائق
            if (!$driver) {
                return response()->json([
                    'status' => false,
                    'error_code' => 'DRIVER_NOT_FOUND',
                    'message' => 'عذراً، هذا الحساب غير مرتبط بملف سائق.'
                ], 404);
            }
            $driver->load(['vehicles', 'documents']);
            return response()->json([
                'status'  => true,
                'message' => 'تم جلب بيانات الملف الشخصي بنجاح.',
                // نمرر البيانات للـ Resource ليتولى هو عملية التنسيق (Formatting)
                'data'    => new DriverResource($driver)
            ], 200);

        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error("Driver Profile Fetch Error: " . $e->getMessage(), [
                'user_id' => auth()->id() ?? 'N/A'
            ]);

            return response()->json([
                'status'  => false,
                'error_code' => 'SERVER_ERROR',
                'message' => 'تعذر جلب البيانات بسبب مشكلة تقنية.',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    
}