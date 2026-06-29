<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Shared\Zone; // تأكد من مسار الموديل لديك
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ZoneController extends Controller
{
    /**
     * عرض جميع المناطق المتاحة للسائقين
     * مع تحميل العلاقات الهرمية للبلديات
     */
    public function index(): JsonResponse
    {
        $zones = Zone::with('subMunicipality')->get();
        
        return response()->json([
            'status' => true,
            'data'   => \App\Http\Resources\Api\Driver\ZoneResource::collection($zones)
        ], 200);
    }
}