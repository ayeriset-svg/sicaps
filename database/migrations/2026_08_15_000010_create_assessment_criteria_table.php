<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kriteria rubrik per stage (mis. A1: Ruang lingkup, Fitur, Fitur AI, ...).
     */
    public function up(): void
    {
        Schema::create('assessment_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_id')->constrained('assessment_stages')->cascadeOnDelete();
            $table->string('name', 150);
            $table->unsignedInteger('order_index')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_criteria');
    }
};
