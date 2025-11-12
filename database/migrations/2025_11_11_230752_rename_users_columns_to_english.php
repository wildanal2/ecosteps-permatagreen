<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'cabang')) {
                $table->renameColumn('cabang', 'branch');
            }
            if (Schema::hasColumn('users', 'direktorat')) {
                $table->renameColumn('direktorat', 'directorate');
            }
            if (Schema::hasColumn('users', 'kendaraan_kantor')) {
                $table->renameColumn('kendaraan_kantor', 'transport');
            }
            if (Schema::hasColumn('users', 'jarak_rumah')) {
                $table->renameColumn('jarak_rumah', 'distance');
            }
            if (Schema::hasColumn('users', 'mode_kerja')) {
                $table->renameColumn('mode_kerja', 'work_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'branch')) {
                $table->renameColumn('branch', 'cabang');
            }
            if (Schema::hasColumn('users', 'directorate')) {
                $table->renameColumn('directorate', 'direktorat');
            }
            if (Schema::hasColumn('users', 'transport')) {
                $table->renameColumn('transport', 'kendaraan_kantor');
            }
            if (Schema::hasColumn('users', 'distance')) {
                $table->renameColumn('distance', 'jarak_rumah');
            }
            if (Schema::hasColumn('users', 'work_mode')) {
                $table->renameColumn('work_mode', 'mode_kerja');
            }
        });
    }
};
