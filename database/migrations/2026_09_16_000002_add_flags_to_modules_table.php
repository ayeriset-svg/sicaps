<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            // false = modul hanya materi (mahasiswa tidak mengisi logbook).
            $table->boolean('requires_submission')->default(true)->after('is_individual');
            // Tugas individu yang dihitung sebagai presensi kelas (PASS = hadir, tolak/telat = alpa).
            $table->boolean('counts_as_attendance')->default(false)->after('attendance_session');
        });
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn(['requires_submission', 'counts_as_attendance']);
        });
    }
};
