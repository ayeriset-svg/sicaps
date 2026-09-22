<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            // Penanda presensi tugas sudah difinalisasi otomatis setelah deadline.
            $table->timestamp('attendance_finalized_at')->nullable()->after('counts_as_attendance');
        });
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('attendance_finalized_at');
        });
    }
};
