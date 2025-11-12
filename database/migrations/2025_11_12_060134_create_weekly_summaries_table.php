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
        Schema::create('weekly_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('week_start_date');
            $table->date('week_end_date');
            $table->bigInteger('total_langkah')->default(0);
            $table->decimal('total_co2e_kg', 10, 2)->default(0);
            $table->decimal('total_pohon', 10, 2)->default(0);
            $table->integer('poin_mingguan')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_summaries');
    }
};
