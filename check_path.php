<?php
// تحميل نظام لارافيل
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

// الحصول على الـ ClassLoader الخاص بـ Composer
$loader = require __DIR__.'/vendor/autoload.php';

// البحث عن المسار الذي يسبب المشكلة
$map = $loader->getClassMap();
foreach ($map as $class => $path) {
    if (strpos($class, 'Parents') !== false) {
        echo "وجدنا كلاس باسم قديم في المسار التالي:\n";
        echo "الكلاس: " . $class . "\n";
        echo "الملف المسبب: " . $path . "\n";
        exit;
    }
}

echo "لم يتم العثور على أي كلاس يحتوي على 'Parents' في ذاكرة Composer.";