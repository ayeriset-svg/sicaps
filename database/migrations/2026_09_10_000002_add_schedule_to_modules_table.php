<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jadwal buka/tutup modul, tugas, & assessment.
     * opens_at  = mulai dapat dikerjakan/berlangsung (opsional).
     * closes_at = batas akhir / deadline (opsional).
     */
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->timestamp('opens_at')->nullable()->after('is_open');
            $table->timestamp('closes_at')->nullable()->after('opens_at');
        });
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn(['opens_at', 'closes_at']);
        });
    }
};
