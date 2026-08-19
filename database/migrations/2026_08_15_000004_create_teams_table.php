<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('team_name', 100);
            $table->foreignId('leader_id')->constrained('users')->cascadeOnDelete();
            $table->enum('case_type', ['jasa', 'dagang', 'manufaktur'])->nullable();
            // Topik yang dipilih/diajukan tim (katalog atau mandiri).
            $table->foreignId('topic_id')->nullable()->constrained('topics')->nullOnDelete();
            $table->enum('topic_status', ['none', 'pending', 'approved', 'rejected'])->default('none');
            $table->text('topic_review_note')->nullable();
            // Fitur yang ditawarkan tim (disalin dari katalog, dapat diubah tim tanpa mengubah master topik).
            $table->text('custom_general_features')->nullable();
            $table->text('custom_ai_features')->nullable();
            // Kelayakan pengajuan HKI (ditandai superadmin).
            $table->boolean('hki_eligible')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
