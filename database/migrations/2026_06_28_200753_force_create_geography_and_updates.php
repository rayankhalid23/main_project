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
        // 1. إنشاء جدول البلديات الرئيسية
        Schema::create('municipalities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->timestamps();
        });

        // 2. إنشاء جدول البلديات الأصغر
        Schema::create('sub_municipalities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained('municipalities')->onDelete('cascade');
            $table->string('name', 100);
            $table->timestamps();
        });

        // 3. تعديل جدول المناطق الحالي (zones) لربطه بالبلدية الأصغر
        Schema::table('zones', function (Blueprint $table) {
            $table->foreignId('sub_municipality_id')->nullable()->after('name')->constrained('sub_municipalities')->onDelete('set null');
        });

        // 4. تعديل جدول الأطفال (children) لإضافة الجنس وأوقات الدوام
        Schema::table('children', function (Blueprint $table) {
            $table->enum('gender', ['male', 'female'])->after('birth_date');
            $table->time('pickup_time')->nullable()->after('preferred_time_slot'); 
            $table->time('dropoff_time')->nullable()->after('pickup_time');       
        });

        // 5. تعديل جدول السائقين (drivers) لإضافة نوع الاشتراك المقبول
        Schema::table('drivers', function (Blueprint $table) {
            $table->enum('subscription_type', ['daily', 'monthly', 'both'])->default('both')->after('shift');
        });

        // 6. إنشاء جدول الربط المتعدد بين السائق والمناطق الدقيقة (driver_zones)
        Schema::create('driver_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->foreignId('zone_id')->constrained('zones')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['driver_id', 'zone_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_zones');
        
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn('subscription_type');
        });

        Schema::table('children', function (Blueprint $table) {
            $table->dropColumn(['gender', 'pickup_time', 'dropoff_time']);
        });

        Schema::table('zones', function (Blueprint $table) {
            $table->dropForeign(['sub_municipality_id']);
            $table->dropColumn('sub_municipality_id');
        });

        Schema::dropIfExists('sub_municipalities');
        Schema::dropIfExists('municipalities');
    }
};