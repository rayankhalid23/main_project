<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تشغيل الهجرة لإنشاء الجداول
     */
    public function up(): void
    {
        // 1. جدول المناطق الأساسي في طرابلس
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // اسم المنطقة مثل (حي الأندلس، عين زارة)
            $table->timestamps();
        });

        // 2. الجدول الوسيط لربط السائقين بالمناطق (علاقة Many-to-Many)
        Schema::create('driver_zone', function (Blueprint $table) {
            $table->id();
            
            // ربط السائق مع تفعيل الحذف المتتالي (إذا حُذف السائق نهائياً تُحذف خياراته تلقائياً)
            $table->foreignId('driver_id')
                ->constrained('drivers')
                ->onDelete('cascade');

            // ربط المنطقة مع تفعيل الحذف المتتالي
            $table->foreignId('zone_id')
                ->constrained('zones')
                ->onDelete('cascade');

            $table->timestamps();

            // شبكة أمان: منع تكرار نفس السائق لنفس المنطقة في الجدول الوسيط
            $table->unique(['driver_id', 'zone_id']);
        });
    }

    /**
     * التراجع عن الهجرة وحذف الجداول بأمان
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_zone');
        Schema::dropIfExists('zones');
    }
};