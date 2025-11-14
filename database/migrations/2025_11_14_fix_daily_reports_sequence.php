<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix sequence untuk daily_reports
        DB::statement("SELECT setval('daily_reports_id_seq', (SELECT COALESCE(MAX(id), 1) FROM daily_reports));");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
