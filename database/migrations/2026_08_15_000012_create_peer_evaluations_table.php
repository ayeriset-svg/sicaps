<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Peer 180 per stage assessment (dibuka superadmin per tahap).
     */
    public function up(): void
    {
        Schema::create('peer_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_id')->constrained('assessment_stages')->cascadeOnDelete();
            $table->foreignId('evaluator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('evaluatee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->decimal('score_communication', 5, 2);
            $table->decimal('score_contribution', 5, 2);
            $table->decimal('score_responsibility', 5, 2);
            $table->decimal('score_attendance', 5, 2);
            $table->decimal('final_peer_score', 5, 2);
            $table->text('feedback')->nullable();
            $table->timestamps();

            $table->unique(['stage_id', 'evaluator_id', 'evaluatee_id'], 'peer_eval_stage_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peer_evaluations');
    }
};
