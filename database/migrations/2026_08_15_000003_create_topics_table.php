<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Katalog Topik + Topik Mandiri.
     * origin = katalog (disediakan superadmin, bisa dipilih tim) | mandiri (diajukan tim).
     */
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->string('partner_name')->nullable()->comment('Nama mitra teks bebas (topik mandiri)');
            $table->string('title');
            $table->text('general_features')->nullable()->comment('Fitur umum yang ditawarkan');
            $table->text('ai_features')->nullable()->comment('Fitur AI yang ditawarkan');
            $table->text('description')->nullable();
            $table->enum('origin', ['katalog', 'mandiri'])->default('katalog');
            $table->boolean('is_available')->default(true)->comment('Katalog: masih bisa dipilih tim');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
