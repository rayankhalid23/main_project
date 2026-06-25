<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Driver\ProfileUpdateRequest;
use App\Http\Requests\Api\Driver\UpdateLegalDocumentsRequest;
use App\Http\Requests\Api\Driver\UpdateVehicleDetailsRequest;
use App\Http\Resources\Api\Driver\DriverProfileResource;
use App\Services\Driver\DriverProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class DriverProfileController extends Controller
{
    protected DriverProfileService $profileService;

    // حقن الخدمة لضمان عزل منطق العمل عن الـ Controller
    public function __construct(DriverProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * 1. تحديث البيانات الشخصية والأساسية (الاسم، الهاتف، كلمة المرور، الصورة الشخصية)
     */
    public function updateProfile(ProfileUpdateRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $data = $request->validated();

            // معالجة ورفع الصورة الشخصية (Avatar) إن وجدت
            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                $filename = 'avatar_' . time() . '.' . $file->getClientOriginalExtension();
                // تخزين في مجلد public/uploads/avatars
                $file->move(public_path('uploads/avatars'), $filename);
                $data['avatar_url'] = 'uploads/avatars/' . $filename;
            }

            $result = $this->profileService->updateDriverProfile($user->id, $data);

            return response()->json([
                'status'  => true,
                'message' => $result['message'],
                'data'    => new DriverProfileResource($result['driver'])
            ], 200);

        } catch (Exception $e) {
            Log::error("Controller Error - updateProfile: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage() ?: 'حدث خطأ داخلي أثناء تحديث البيانات الشخصية.'
            ], 500);
        }
    }

    /**
     * 2. تحديث وتجديد البيانات القانونية والوثائق الرسمية (الرخصة، الكتيب، التأمين)
     */
    public function updateLegalData(UpdateLegalDocumentsRequest $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $data = $request->validated();

            // مصفوفة لتحديد المستندات المرفوعة ومعالجتها ديناميكياً
            $documentFields = [
                'doc_license'         => 'doc_license_path',
                'doc_logbook'         => 'doc_logbook_path',
                'doc_insurance'       => 'doc_insurance_path',
                'doc_criminal_record' => 'doc_criminal_record_path'
            ];

            foreach ($documentFields as $fileKey => $dataKey) {
                if ($request->hasFile($fileKey)) {
                    $file = $request->file($fileKey);
                    $filename = $fileKey . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('uploads/documents'), $filename);
                    $data[$dataKey] = 'uploads/documents/' . $filename;
                } else {
                    // إذا لم يرفع مستند معين، نمرر مساره القديم أو null حسب منطق النظام
                    $data[$dataKey] = null; 
                }
            }

            $result = $this->profileService->updateLegalDocuments($userId, $data);

            return response()->json([
                'status'  => true,
                'message' => $result['message']
            ], 200);

        } catch (Exception $e) {
            Log::error("Controller Error - updateLegalData: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage() ?: 'حدث خطأ داخلي أثناء رفع المستندات.'
            ], 500);
        }
    }

    /**
     * 3. تحديث بيانات المركبة الحالية للسائق
     */
    public function updateVehicle(UpdateVehicleDetailsRequest $request, int $vehicleId): JsonResponse
    {
        try {
            $userId = auth()->id();
            $data = $request->validated();

            // معالجة صورة المركبة إن وجدت
            if ($request->hasFile('vehicle_image')) {
                $file = $request->file('vehicle_image');
                $filename = 'vehicle_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/vehicles'), $filename);
                $data['vehicle_image_path'] = 'uploads/vehicles/' . $filename;
            }

            $vehicle = $this->profileService->updateVehicleDetails($userId, $vehicleId, $data);

            return response()->json([
                'status'  => true,
                'message' => 'تم تحديث بيانات المركبة بنجاح، وهي الآن تحت المراجعة لإعادة اعتمادها.',
                'vehicle' => $vehicle
            ], 200);

        } catch (Exception $e) {
            Log::error("Controller Error - updateVehicle: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage() ?: 'تعذر تحديث بيانات المركبة، تأكد من البيانات المرسلة.'
            ], 500);
        }
    }
}