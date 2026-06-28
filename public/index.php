<?php

set_time_limit(420); // رفع مهلة التنفيذ إلى دقيقتين (120 ثانية) بدلاً من 30 ثانية

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// --- إضافة لحل مشكلة رفع الملفات في ويندوز ---
// هذا السطر يوجه PHP لاستخدام المجلد داخل مشروعك بدلاً من مجلدات النظام
putenv('TMPDIR=' . __DIR__ . '/../storage/temp');
if (!is_dir(__DIR__ . '/../storage/temp')) {
    mkdir(__DIR__ . '/../storage/temp', 0777, true);
}
// ------------------------------------------

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());