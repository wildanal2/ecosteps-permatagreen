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
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('tanggal_laporan');
            $table->integer('langkah')->default(0);
            $table->decimal('co2e_reduction_kg', 10, 2)->default(0);
            $table->integer('poin')->default(0);
            $table->decimal('pohon', 5, 2)->default(0);
            $table->tinyInteger('status_verifikasi')->default(1); // 1=pending, 2=diverifikasi, 3=ditolak
            $table->text('bukti_screenshot')->nullable(); // URL ke S3
            $table->longText('ocr_result')->nullable();
            $table->integer('count_document')->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tanggal_laporan']); // 1 laporan per hari per user
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
