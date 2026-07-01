<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Parent\StoreChildRequest;
use App\Http\Requests\Api\Parent\UpdateChildRequest;
use App\Models\Parent\Child;
use App\Services\Parent\ChildService;
use App\Http\Resources\Api\Parent\ChildResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;

class ChildrenController extends Controller
{
    protected ChildService $childService;

    public function __construct(ChildService $childService)
    {
        $this->childService = $childService;
    }

    public function index(): JsonResponse
    {
        $parentId = auth()->user()->parent->id; 

        $children = Child::where('parent_id', $parentId)
            ->with(['school', 'address', 'logistics']) 
            ->get();

        return response()->json([
            'success' => true,
            'data'    => ChildResource::collection($children)
        ], 200);
    }

    public function store(StoreChildRequest $request): JsonResponse
    {
        $child = $this->childService->createChild($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة بيانات الطفل بنجاح.',
            'data'    => new ChildResource($child->load('logistics'))
        ], 201);
    }

    public function show(Child $child): JsonResponse
{
    // التحقق أن الطفل يخص ولي الأمر الحالي
    if ($child->parent_id !== auth()->user()->parent->id) {
        return response()->json(['success' => false, 'message' => 'غير مصرح لك بالوصول لهذا السجل'], 403);
    }

    $child->load(['school', 'address', 'logistics']);
    return response()->json(['success' => true, 'data' => new ChildResource($child)], 200);
}
}