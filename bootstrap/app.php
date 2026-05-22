<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Illuminate\Database\QueryException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // هنا يمكنك تسجيل الـ Middlewares الخاصة بك لاحقاً
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        
        // معالجة واصطياد أخطاء الـ API لمنع الانهيار وتقديم رسائل تجارية احترافية
        $exceptions->render(function (Throwable $e, Request $request) {
            
            // نطبق الفحص الموحد فقط إذا كان الطلب موجهاً لمسارات الـ API
            if ($request->is('api/*')) {

                // [الحالة 1]: ضغط وحمل زائد على السيرفر (تخطي الـ Rate Limit)
                if ($e instanceof TooManyRequestsHttpException) {
                    return response()->json([
                        'status' => false,
                        'error_code' => 'TOO_MANY_REQUESTS',
                        'message' => 'السيرفر تحت ضغط حالياً! يرجى إعادة المحاولة بعد قليل .'
                    ], 429);
                }

                // [الحالة 2]: خطأ في الاتصال بقاعدة البيانات أو سقوط سيرفر الـ DB مؤقتاً
                if ($e instanceof QueryException) {
                    return response()->json([
                        'status' => false,
                        'error_code' => 'DATABASE_ERROR',
                        'message' => 'نواجه مشكلة مؤقتة في الاتصال بخادم البيانات، تأكد من استقرار الإنترنت و حاول مره اخري.'
                    ], 500);
                }

                // [الحالة 3]: السيرفر قيد الصيانة أو متوقف مؤقتاً لمعالجة البيانات
                if ($e instanceof ServiceUnavailableHttpException) {
                    return response()->json([
                        'status' => false,
                        'error_code' => 'SERVER_MAINTENANCE',
                        'message' => 'المنصة خاضعة للتحديثات الدورية ، يرجى المحاولة بعد قليل.'
                    ], 503);
                }

                // [الحالة 4]: طلب عنصر غير موجود (موظف محذوف، مخزن ملغي، رابط خطأ)
                if ($e instanceof NotFoundHttpException) {
                    return response()->json([
                        'status' => false,
                        'error_code' => 'NOT_FOUND',
                        'message' => 'العنصر أو الرابط الذي تحاول الوصول إليه غير موجود أو تم حذفه مؤقتاً.'
                    ], 404);
                }

                // [الحالة 5]: شبكة الأمان المطلقة لأي خطأ برمجى مفاجئ غير متوقع في النظام
                return response()->json([
                    'status' => false,
                    'error_code' => 'SERVER_ERROR',
                    'message' => 'حدث خطأ غير متوقع في النظام.'
                ], 500);
            }
        });
        
    })->create();