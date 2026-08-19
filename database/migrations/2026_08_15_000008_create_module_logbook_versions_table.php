<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_logbook_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_logbook_id')->constrained('module_logbooks')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->json('payload_json')->nullable();
            $table->string('status_snapshot', 30)->nullable();
            $table->text('feedback_snapshot')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_logbook_versions');
    }
};
