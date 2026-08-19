<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tahapan Assessment (A1/A2/A3) beserta bobot & porsi peer 180.
     */
    public function up(): void
    {
        Schema::create('assessment_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('code', 10)->comment('A1, A2, A3');
            $table->string('name', 100);
            $table->decimal('weight_percentage', 5, 2)->comment('Bobot terhadap NA (30/30/40)');
            $table->decimal('peer_weight_percentage', 5, 2)->default(10)->comment('Porsi peer 180 di dalam stage');
            $table->boolean('peer_open')->default(false)->comment('Peer 180 dibuka untuk stage ini');
            $table->unsignedInteger('order_index')->default(0);
            $table->timestamps();

            $table->unique(['academic_year_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_stages');
    }
};
