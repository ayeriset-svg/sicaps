<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nilai rubrik kelompok per kriteria (nilai sama untuk seluruh anggota tim).
     */
    public function up(): void
    {
        Schema::create('assessment_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criterion_id')->constrained('assessment_criteria')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->decimal('score', 6, 2)->comment('Skala 0-100');
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['criterion_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_scores');
    }
};
