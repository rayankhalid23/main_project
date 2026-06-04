<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Parent\StoreChildRequest;
use App\Http\Requests\Api\Parent\UpdateChildRequest;
use App\Models\Parent\Child;
use App\Services\Parent\ChildService;
use App\Http\Resources\Api\Parent\ChildResource; // سنقوم بإنشائه في الخطوة القادمة
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ChildrenController extends Controller
{
    protected ChildService $childService;

    /**
     * حقن طبقة الخدمات (ChildService) داخل الـ Controller عبر الـ Constructor
     */
    public function __construct(ChildService $childService)
    {
        $this->childService = $childService;
    }

    /**
     * عرض قائمة الأطفال التابعين لولي أمر محدد.
     * ملاحظة: قمنا بتجهيزها لجلب أطفال ولي الأمر الحالي لمزيد من الأمان.
     */
    public function index(): JsonResponse
    {
        // هنا نقوم بجلب الأطفال (كمثال ممررنا parent_id = 1 الفرضي الذي حقناه بالـ Seeder)
        // مستقبلاً عند تفعيل الـ Auth يمكنك استبدالها بـ auth()->user()->parent->id
        $parentId = 1; 

        $children = Child::where('parent_id', $parentId)
            ->with(['school', 'address']) // استخدام Eager Loading لتفادي مشكلة N+1 في قاعدة البيانات
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب قائمة الأطفال بنجاح.',
            'data'    => ChildResource::collection($children) // سننشئ الـ Resource لترتيب البيانات بعد قليل
        ], Response::HTTP_OK);
    }

    /**
     * إضافة طفل جديد لولي الأمر.
     */
    public function store(StoreChildRequest $request): JsonResponse
    {
        // تمرير البيانات الموثقة بالكامل إلى الـ Service لمعالجتها وحفظها
        $child = $this->childService->createChild($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة بيانات الطفل وتوليد الـ QR بنجاح.',
            'data'    => new ChildResource($child)
        ], Response::HTTP_CREATED);
    }

    /**
     * عرض بيانات طفل محدد بالتفصيل.
     */
    public function show(Child $child): JsonResponse
    {
        // تحميل العلاقات لضمان ظهور اسم المدرسة والعنوان في الواجهة
        $child->load(['school', 'address']);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب بيانات الطفل بنجاح.',
            'data'    => new ChildResource($child)
        ], Response::HTTP_OK);
    }

    /**
     * تحديث بيانات طفل موجود.
     */
    public function update(UpdateChildRequest $request, Child $child): JsonResponse
    {
        // إرسال الطفل والبيانات الجديدة الموثقة إلى الـ Service لتحديثها بأمان
        $updatedChild = $this->childService->updateChild($child, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات الطفل بنجاح.',
            'data'    => new ChildResource($updatedChild)
        ], Response::HTTP_OK);
    }

    /**
     * حذف طفل من النظام.
     */
    public function destroy(Child $child): JsonResponse
    {
        $this->childService->deleteChild($child);

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الطفل وكافة ملفاته الملحقة بنجاح.'
        ], Response::HTTP_OK);
    }
}