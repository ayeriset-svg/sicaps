<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('year', 10)->comment('Contoh: 2025/2026');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->boolean('is_active')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            $table->unique(['year', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
