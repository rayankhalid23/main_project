<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreSchoolRequest;
use App\Http\Requests\Api\Admin\UpdateSchoolRequest;
use App\Http\Resources\Api\Admin\SchoolResource;
use App\Models\Parent\School;
use App\Services\Admin\SchoolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SchoolController extends Controller
{
    protected SchoolService $schoolService;

    public function __construct(SchoolService $schoolService)
    {
        $this->schoolService = $schoolService;
    }

    public function index(): JsonResponse
    {
        // الأدمن يرى كل المدارس ليراجع المعلق منها ويوافق عليه
        $schools = $this->schoolService->getSchools();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب كافة المدارس بنجاح.',
            'data'    => SchoolResource::collection($schools)
        ], Response::HTTP_OK);
    }

    public function store(StoreSchoolRequest $request): JsonResponse
    {
        // الأدمن يضيف مدرسة معتمدة فوراً
        $data = array_merge($request->validated(), ['status' => 'approved']);
        $school = $this->schoolService->createSchool($data);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة المدرسة كعنوان معتمد بنجاح.',
            'data'    => new SchoolResource($school)
        ], Response::HTTP_CREATED);
    }

    public function show(School $school): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new SchoolResource($school)
        ], Response::HTTP_OK);
    }

    public function update(UpdateSchoolRequest $request, School $school): JsonResponse
    {
        $updatedSchool = $this->schoolService->updateSchool($school, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات وموقع المدرسة بنجاح.',
            'data'    => new SchoolResource($updatedSchool)
        ], Response::HTTP_OK);
    }

    public function destroy(School $school): JsonResponse
    {
        if ($school->children()->exists()) {
            return response()->json([
                'status'     => false,
                'error_code' => 'SCHOOL_IN_USE',
                'message'    => 'لا يمكن حذف المدرسة، هناك أطفال مسجلين بها حالياً.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->schoolService->deleteSchool($school);

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المدرسة من النظام بنجاح.'
        ], Response::HTTP_OK);
    }
}