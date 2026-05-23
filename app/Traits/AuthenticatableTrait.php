<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait AuthenticatableTrait // تم تغيير الاسم هنا
{
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم تسجيل الخروج بنجاح.'
        ], 200);
    }
}