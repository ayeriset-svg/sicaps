<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah status 'Rejected' (tugas ditolak → alpa).
        DB::statement("ALTER TABLE module_logbooks MODIFY status_approval ENUM('Not Started','Pending','Revision Needed','Approved','Rejected') NOT NULL DEFAULT 'Not Started'");

        Schema::table('module_logbooks', function (Blueprint $table) {
            // Hasil pengecekan kemiripan jawaban antar mahasiswa (tugas individu).
            $table->decimal('similarity_max', 5, 2)->nullable()->after('proofread_checked_at')->comment('Kemiripan tertinggi dgn mhs lain (%)');
            $table->json('similarity_json')->nullable()->after('similarity_max')->comment('[{user_id,name,percent}]');
            $table->timestamp('similarity_checked_at')->nullable()->after('similarity_json');
        });
    }

    public function down(): void
    {
        Schema::table('module_logbooks', function (Blueprint $table) {
            $table->dropColumn(['similarity_max', 'similarity_json', 'similarity_checked_at']);
        });
        DB::statement("ALTER TABLE module_logbooks MODIFY status_approval ENUM('Not Started','Pending','Revision Needed','Approved') NOT NULL DEFAULT 'Not Started'");
    }
};
