<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreSchoolRequest;
use App\Http\Resources\Api\Admin\SchoolResource;
use App\Services\Admin\SchoolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ParentSchoolController extends Controller
{
    protected SchoolService $schoolService;

    public function __construct(SchoolService $schoolService)
    {
        $this->schoolService = $schoolService;
    }

    public function store(StoreSchoolRequest $request): JsonResponse
    {
        // إجبار النظام على جعل الحالة pending لأن المدخل ولي أمر
        $data = array_merge($request->validated(), ['status' => 'pending']);
        
        $school = $this->schoolService->createSchool($data);

        return response()->json([
            'success' => true,
            'message' => 'تم اقتراح المدرسة بنجاح وربطها بحسابك، وهي قيد المراجعة السريعة من الإدارة.',
            'data'    => new SchoolResource($school)
        ], Response::HTTP_CREATED);
    }
}