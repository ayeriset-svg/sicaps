<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aturan penalti kehadiran berbasis jumlah HARI tidak hadir.
     * type: none | points_deduction (kurangi poin dari NA) | fail (Grade E).
     */
    public function up(): void
    {
        Schema::create('attendance_penalty_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('label', 120)->nullable();
            $table->unsignedInteger('min_days')->comment('Batas bawah hari tidak hadir (inklusif)');
            $table->unsignedInteger('max_days')->nullable()->comment('Batas atas (inklusif), null = tak terhingga');
            $table->enum('penalty_type', ['none', 'points_deduction', 'fail']);
            $table->decimal('deduction_points', 6, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_penalty_rules');
    }
};
