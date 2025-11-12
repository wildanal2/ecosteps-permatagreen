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
        Schema::create('ocr_process_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('daily_reports')->onDelete('cascade');
            $table->string('request_id', 100)->nullable();
            $table->tinyInteger('fastapi_status')->default(1); // 1=queued, 2=processing, 3=done
            $table->longText('ocr_raw')->nullable();
            $table->longText('ocr_text_result')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ocr_process_logs');
    }
};
