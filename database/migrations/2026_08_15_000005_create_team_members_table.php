<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            // Peran bebas (boleh lebih dari satu, dipisah koma). Contoh: "Project Manager, Backend Developer".
            $table->string('assigned_role', 255);
            $table->timestamps();

            $table->unique(['team_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
