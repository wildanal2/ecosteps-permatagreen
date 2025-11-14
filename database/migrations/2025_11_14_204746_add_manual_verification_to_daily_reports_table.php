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
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->boolean('manual_verification_requested')->default(false)->after('status_verifikasi');
            $table->timestamp('manual_verification_requested_at')->nullable()->after('manual_verification_requested');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropColumn(['manual_verification_requested', 'manual_verification_requested_at']);
        });
    }
};
