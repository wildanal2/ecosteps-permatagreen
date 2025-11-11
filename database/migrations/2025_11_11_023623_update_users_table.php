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
        Schema::table('users', function (Blueprint $table) {
            // Tambahkan kolom baru hanya jika belum ada
            if (!Schema::hasColumn('users', 'cabang')) {
                $table->string('cabang', 100)->nullable()->after('password');
            }

            if (!Schema::hasColumn('users', 'direktorat')) {
                $table->string('direktorat', 100)->nullable()->after('cabang');
            }

            if (!Schema::hasColumn('users', 'kendaraan_kantor')) {
                $table->string('kendaraan_kantor', 50)->nullable()->after('direktorat');
            }

            if (!Schema::hasColumn('users', 'jarak_rumah')) {
                $table->integer('jarak_rumah')->default(0)->after('direktorat');
            }

            if (!Schema::hasColumn('users', 'mode_kerja')) {
                $table->enum('mode_kerja', ['WFH', 'WFO'])->default('WFO')->after('kendaraan_kantor');
            }

            if (!Schema::hasColumn('users', 'user_level')) {
                $table->integer('user_level')->default(1)->after('direktorat');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'cabang')) {
                $table->dropColumn('cabang');
            }

            if (Schema::hasColumn('users', 'direktorat')) {
                $table->dropColumn('direktorat');
            }

            if (Schema::hasColumn('users', 'kendaraan_kantor')) {
                $table->dropColumn('kendaraan_kantor');
            }

            if (Schema::hasColumn('users', 'jarak_rumah')) {
                $table->dropColumn('jarak_rumah');
            }

            if (Schema::hasColumn('users', 'mode_kerja')) {
                $table->dropColumn('mode_kerja');
            }

            if (Schema::hasColumn('users', 'user_level')) {
                $table->dropColumn('user_level');
            }
        });
    }
};
