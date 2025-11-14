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
        Schema::create('manual_verification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('daily_reports')->onDelete('cascade');
            $table->text('image_url');
            $table->integer('valid_step');
            $table->string('app_name')->nullable();
            $table->tinyInteger('status_verifikasi'); // 2=approved, 3=rejected
            $table->foreignId('validated_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_verification_logs');
    }
};
