<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            // إضافة عمود deleted_at تلقائياً بعد عمود is_default
            $table->softDeletes()->after('is_default');
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            // حذف العمود في حال التراجع عن الهجرة
            $table->dropSoftDeletes();
        });
    }
};
