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
            $table->text('img_url')->nullable()->after('report_id');
            $table->tinyInteger('verified_id')->default(1)->after('detect_aplication_id');
            $table->unsignedBigInteger('verified_by')->nullable()->after('verified_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ocr_process_logs', function (Blueprint $table) {
            $table->dropColumn(['img_url', 'verified_id', 'verified_by']);
        });
    }
};
