<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_logbooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            // Pengisi individu (tugas/assignment). NULL = logbook tim (diwakilkan ketua).
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->json('payload_json')->nullable()->comment('Nilai per field: {field_key: html/text}');
            $table->enum('status_approval', ['Not Started', 'Pending', 'Revision Needed', 'Approved'])->default('Not Started');
            $table->text('feedback')->nullable()->comment('Feedback superadmin');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable()->comment('Tanggal review/revisi terakhir');
            $table->unsignedInteger('revision_count')->default(0);
            // Hasil pemeriksaan indikasi AI (estimasi indikatif).
            $table->decimal('ai_percentage', 5, 2)->nullable()->comment('Estimasi AI keseluruhan %');
            $table->decimal('ai_text_percentage', 5, 2)->nullable();
            $table->decimal('ai_image_percentage', 5, 2)->nullable();
            $table->json('ai_detail_json')->nullable();
            $table->timestamp('ai_checked_at')->nullable();
            // Hasil pemeriksaan tata tulis (proofreader/technical editor).
            $table->unsignedTinyInteger('proofread_score')->nullable()->comment('Skor kualitas tata tulis 0-100');
            $table->json('proofread_json')->nullable()->comment('{summary,total_issues,issues[],corrected_text}');
            $table->timestamp('proofread_checked_at')->nullable();
            $table->timestamps();

            // Logbook tim: satu per (team, module) [user_id NULL].
            // Tugas individu: satu per (module, user).
            $table->unique(['team_id', 'module_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_logbooks');
    }
};
