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
            $table->tinyInteger('detect_aplication_id')->default(1)->after('fastapi_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ocr_process_logs', function (Blueprint $table) {
            $table->dropColumn('detect_aplication_id');
        });
    }
};
