<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('schools', function (Blueprint $table) {
        // نستخدم تغيير النوع فقط، بدون لمس zone_id الذي أصبح موجوداً
        $table->string('status', 20)->change();
    });
}
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            // حذف القيد أولاً ثم العمود
            $table->dropForeign(['zone_id']);
            $table->dropColumn('zone_id');
        });
    }
};