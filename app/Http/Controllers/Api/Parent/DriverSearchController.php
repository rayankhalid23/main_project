<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Parent\SearchDriversRequest;
use App\Services\Parent\DriverMatchingService;
use App\Http\Resources\Api\Parent\ChildMatchResource;
use App\Http\Resources\Api\Parent\DriverMatchResource;
use App\Models\Parent\Child;

class DriverSearchController extends Controller
{
    protected $driverMatchingService;

    public function __construct(DriverMatchingService $driverMatchingService)
    {
        $this->driverMatchingService = $driverMatchingService;
    }

    public function search(SearchDriversRequest $request)
    {
        $parentId = auth()->id();
        $filters = $request->validated();

        // 1. جلب السائقين المطابقين
        $drivers = $this->driverMatchingService->matchDrivers($filters, $parentId);

        // 2. جلب وتجهيز بيانات أطفال ولي الأمر (لحساب المسافات)
        $childrenQuery = Child::with(['school', 'address'])->where('parent_id', $parentId);
        if (!empty($filters['child_ids'])) {
            $childrenQuery->whereIn('id', $filters['child_ids']);
        }
        $children = $childrenQuery->get();

        return response()->json([
            'status'  => 'success',
            'data'    => [
                'children_context' => ChildMatchResource::collection($children),
                'matched_drivers'  => DriverMatchResource::collection($drivers),
            ]
        ]);
    }
}