<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Capaian pembelajaran: PLO (Program Learning Outcome) → CLO (Course Learning
     * Outcome) → Sub-CLO. Per tahun ajaran (bisa berbeda tiap tahun). Modul
     * praktikum memetakan Sub-CLO untuk pengukuran capaian.
     */
    public function up(): void
    {
        Schema::create('plos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('code', 30);
            $table->text('description')->nullable();
            $table->unsignedInteger('order_index')->default(0);
            $table->timestamps();
        });

        Schema::create('clos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('plo_id')->nullable()->constrained('plos')->nullOnDelete();
            $table->string('code', 30);
            $table->text('description')->nullable();
            $table->unsignedInteger('order_index')->default(0);
            $table->timestamps();
        });

        Schema::create('sub_clos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('clo_id')->constrained('clos')->cascadeOnDelete();
            $table->string('code', 30);
            $table->text('description')->nullable();
            $table->unsignedInteger('order_index')->default(0);
            $table->timestamps();
        });

        // Pemetaan modul ↔ Sub-CLO (many-to-many).
        Schema::create('module_sub_clo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->foreignId('sub_clo_id')->constrained('sub_clos')->cascadeOnDelete();
            $table->unique(['module_id', 'sub_clo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_sub_clo');
        Schema::dropIfExists('sub_clos');
        Schema::dropIfExists('clos');
        Schema::dropIfExists('plos');
    }
};
