<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // تنفيذ التعديل فقط إذا كانت قاعدة البيانات ليست SQLite
        if (DB::getDriverName() !== 'sqlite') {
            // استخدام استعلام SQL مباشر لتعديل الـ ENUM لضمان التوافق التام بدون تغيير البيانات الحالية
            DB::statement("ALTER TABLE requests MODIFY COLUMN status ENUM('pending', 'accepted', 'rejected', 'contract_offered', 'cancelled') NOT NULL DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // تنفيذ التراجع فقط إذا كانت قاعدة البيانات ليست SQLite
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE requests MODIFY COLUMN status ENUM('pending', 'accepted', 'rejected', 'contract_offered') NOT NULL DEFAULT 'pending'");
        }
    }
};