<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_addresses', function (Blueprint $table) {
            $table->id();
            // ربط إجباري بجدول السائقين (وليس Nullable) لضمان سلامة البيانات
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->string('label', 100); // مثل: الموقف الرئيسي
            $table->decimal('lat', 10, 8); // تخزين دقيق للإحداثيات جغرافياً
            $table->decimal('lng', 11, 8);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes(); // دعم الحذف الناعم للأمان
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_addresses');
    }
};