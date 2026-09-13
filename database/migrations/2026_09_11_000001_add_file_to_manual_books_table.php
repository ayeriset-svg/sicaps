<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lampiran berkas opsional pada manual book (PDF/Word/PPT/gambar/zip).
     */
    public function up(): void
    {
        Schema::table('manual_books', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('content')->comment('Path berkas di private disk');
            $table->string('file_name')->nullable()->after('file_path')->comment('Nama asli berkas');
        });
    }

    public function down(): void
    {
        Schema::table('manual_books', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'file_name']);
        });
    }
};
