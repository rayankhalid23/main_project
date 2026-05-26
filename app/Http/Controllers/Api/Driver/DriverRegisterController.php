<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Driver\RegisterDriverRequest;
use App\Services\Driver\DriverRegisterService;
use App\Http\Resources\Api\Driver\DriverResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class DriverRegisterController extends Controller
{
    protected DriverRegisterService $registerService;

    /**
     * حقن الـ Service داخل الـ Controller عبر الـ Constructor Injection
     */
    public function __construct(DriverRegisterService $registerService)
    {
        $this->registerService = $registerService;
    }

    /**
     * استقبال طلب تسجيل السائق، معالجة الملفات، واستدعاء الخدمة
     *
     * @param RegisterDriverRequest $request
     * @return JsonResponse
     */
    public function register(RegisterDriverRequest $request): JsonResponse
    {
        try {
            // جلب البيانات التي تم التحقق منها بالكامل من الـ Request
            $validatedData = $request->validated();

            // مصفوفة لتخزين المسارات النهائية للملفات المرفوعة (لتسهيل مسحها في حال فشل النظام لاحقاً)
            $uploadedPaths = [];

            // 1. معالجة وتخزين الصورة الشخصية (إن وُجدت)
            if ($request->hasFile('avatar_url')) {
                $avatarPath = $request->file('avatar_url')->store('uploads/drivers/avatars', 'public');
                $validatedData['avatar_path'] = 'storage/' . $avatarPath;
                $uploadedPaths[] = $avatarPath;
            }

            // 2. معالجة وتخزين صورة المركبة (إجبارية)
            $vehiclePath = $request->file('vehicle_image_url')->store('uploads/drivers/vehicles', 'public');
            $validatedData['vehicle_image_path'] = 'storage/' . $vehiclePath;
            $uploadedPaths[] = $vehiclePath;

            // 3. معالجة وتخزين الوثائق والمستندات الرسمية الأربعة (إجبارية)
            $documentFields = [
                'doc_license'         => 'doc_license_path',
                'doc_logbook'         => 'doc_logbook_path',
                'doc_insurance'       => 'doc_insurance_path',
                'doc_criminal_record' => 'doc_criminal_record_path'
            ];

            foreach ($documentFields as $fileInput => $arrayKey) {
                $storedPath = $request->file($fileInput)->store('uploads/drivers/documents', 'public');
                $validatedData[$arrayKey] = 'storage/' . $storedPath;
                $uploadedPaths[] = $storedPath;
            }

            // 4. تمرير البيانات المجهزة بالمسارات إلى الـ Service لإدخالها في قاعدة البيانات
            $driver = $this->registerService->register($validatedData);

            // 5. إرجاع استجابة نجاح موحدة واحترافية للموبايل
            return response()->json([
                'status'  => true,
                'message' => 'تم استلام طلب التسجيل بنجاح! حسابك بانتظار مراجعة وتدقيق المشرف حالياً لتفعيله.',
                'data'    => new DriverResource($driver)
            ], 201); // 201 Created

        } catch (Exception $e) {
            // تكتيك تنظيف متقدم: في حال فشل إدخال البيانات بعد رفع الملفات، نقوم بمسح الصور المرفوعة فوراً لمنع تراكم الملفات المهملة بالسيرفر
            if (!empty($uploadedPaths)) {
                foreach ($uploadedPaths as $path) {
                    Storage::disk('public')->delete($path);
                }
            }

            // تسجيل الخطأ الفني في الـ Log
            Log::error("Driver Registration Controller Error: " . $e->getMessage(), [
                'phone_number' => $request->input('phone_number') ?? 'N/A'
            ]);

            // إرجاع رد خطأ موحد ومحمي للمستخدم
            return response()->json([
                'status'  => false,
                'message' => 'تعذر إتمام عملية التسجيل بسبب مشكلة تقنية داخلية بالنظام.',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}