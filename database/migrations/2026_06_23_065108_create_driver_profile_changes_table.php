<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('driver_profile_changes', function (Blueprint $table) {
            $table->id();
            
            // ربط خارجي مع جدول السائقين مع التحديث والحذف التلقائي
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade')->onUpdate('cascade');
            
            // القيم القديمة والجديدة بصيغة JSON لمرونة الحقول
            $table->json('old_values')->nullable(); // nullable لتفادي الأخطاء إذا كانت البيانات السابقة فارغة
            $table->json('new_values'); 
            
            // حالة الطلب وسبب الرفض إن وجد
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->string('rejection_reason', 500)->nullable(); 
            
            // الأدمن المسؤول عن اتخاذ القرار (مرتبط بجدول admins)
            $table->foreignId('action_by')->nullable()->constrained('admins')->onDelete('restrict'); 
            
            // التوقيتات القياسية وتوقيت اتخاذ القرار
            $table->timestamps(); 
            $table->timestamp('action_at')->nullable(); 
        
            // الفهارس (Indexes) لضمان سرعة الاستعلام والفلترة داخل لوحة التحكم
            $table->index('driver_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_profile_changes');
    }
};