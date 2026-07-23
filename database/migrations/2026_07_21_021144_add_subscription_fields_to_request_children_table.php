<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_children', function (Blueprint $table) {
            $table->string('subscription_type', 50)->nullable();
            $table->string('direction', 50)->nullable();
            $table->string('timing', 50)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
        });

        // Migrate existing data from requests table
        $requests = Illuminate\Support\Facades\DB::table('requests')->get();
        foreach ($requests as $request) {
            Illuminate\Support\Facades\DB::table('request_children')
                ->where('request_id', $request->id)
                ->update([
                    'subscription_type' => $request->subscription_type,
                    'direction'         => $request->direction,
                    'timing'            => $request->timing,
                    'start_date'        => $request->start_date,
                    'end_date'          => $request->end_date,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_children', function (Blueprint $table) {
            $table->dropColumn(['subscription_type', 'direction', 'timing', 'start_date', 'end_date']);
        });
    }
};
