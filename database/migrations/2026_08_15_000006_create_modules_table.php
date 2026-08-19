<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modul / Pertemuan dinamis (dikelola superadmin per tahun ajaran).
     * type: module (logbook praktikum) | assessment (milestone nilai) | other (logbook lain).
     * fields_json: definisi field logbook [{key,label,type,required}].
     */
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->unsignedInteger('order_index')->default(0);
            $table->string('week_label', 20)->comment('W1, W3.1, W3.2, dst');
            $table->string('code', 20)->nullable();
            $table->enum('type', ['module', 'assessment', 'other'])->default('module');
            $table->enum('assessment_stage', ['A1', 'A2', 'A3'])->nullable();
            // Tugas individu (dikerjakan per mahasiswa, bukan diwakilkan tim).
            $table->boolean('is_individual')->default(false)->comment('true = tugas/assignment individu');
            // Gate: modul/tugas hanya dapat dikerjakan mahasiswa bila dibuka superadmin.
            $table->boolean('is_open')->default(false)->comment('Dibuka superadmin utk dikerjakan');
            // Slot presensi yang otomatis "hadir" saat tugas individu di-PASS (opsional).
            $table->unsignedTinyInteger('attendance_week')->nullable()->comment('1-16');
            $table->unsignedTinyInteger('attendance_session')->nullable()->comment('1-2');
            $table->unsignedTinyInteger('ai_policy_level')->default(1)->comment('Level batasan AI 1-5 (Tabel V.5)');
            $table->string('title');
            // Materi modul (rich HTML) — mengikuti template dokumen.
            $table->longText('objectives')->nullable()->comment('Tujuan');
            $table->longText('tools_materials')->nullable()->comment('Alat dan Bahan');
            $table->longText('ai_rules')->nullable()->comment('Aturan Penggunaan AI');
            $table->longText('references')->nullable()->comment('Referensi');
            $table->longText('description')->nullable()->comment('Deskripsi / Materi Teori');
            $table->longText('tasks')->nullable()->comment('Tugas / Pertanyaan');
            $table->json('fields_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
