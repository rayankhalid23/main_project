<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            /**
             * إضافة عمود shift بنوع tinyInteger موجه عددياً:
             * 1 => صباحي فقط (Morning)
             * 2 => مسائي فقط (Evening)
             * 3 => الفترتين معاً (Both)
             * نضعه افتراضياً 1 أو يترك nullable حسب رغبتك، وضعناه بعد حقل الـ status لترتيب الجدول
             */
            $table->tinyInteger('shift')->default(1)->comment('1: Morning, 2: Evening, 3: Both')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            // حذف العمود في حال التراجع عن الهجرة
            $table->dropColumn('shift');
        });
    }
};
