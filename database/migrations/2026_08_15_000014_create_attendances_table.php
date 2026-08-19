<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Presensi per sesi (16 minggu x 2 sesi). status absent dihitung sbg hari tidak hadir.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->unsignedTinyInteger('week_number')->comment('1-16');
            $table->unsignedTinyInteger('session_number')->comment('1-2');
            $table->enum('status', ['present', 'permit', 'sick', 'absent'])->default('present');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id', 'week_number', 'session_number'], 'attendance_session_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
