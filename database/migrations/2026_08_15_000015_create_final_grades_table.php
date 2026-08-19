<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('final_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->json('breakdown_json')->nullable()->comment('Rincian nilai per stage');
            $table->decimal('raw_score', 6, 2)->default(0)->comment('NA sebelum penalti');
            $table->unsignedInteger('absent_days')->default(0);
            $table->decimal('penalty_points', 6, 2)->default(0);
            $table->string('penalty_level', 30)->nullable();
            $table->decimal('final_score', 6, 2)->default(0)->comment('NA setelah penalti');
            $table->string('grade_letter', 2)->nullable();
            $table->decimal('override_score', 6, 2)->nullable();
            $table->text('override_note')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_grades');
    }
};
