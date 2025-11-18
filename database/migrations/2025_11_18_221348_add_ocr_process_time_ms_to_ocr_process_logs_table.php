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
        Schema::table('ocr_process_logs', function (Blueprint $table) {
            $table->integer('ocr_process_time_ms')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ocr_process_logs', function (Blueprint $table) {
            $table->dropColumn('ocr_process_time_ms');
        });
    }
};
