<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flag "sembunyikan dari mahasiswa": modul yang di-hide tidak muncul & tidak
     * dapat dibuka mahasiswa sama sekali (beda dengan is_open yang masih
     * menampilkan materi). Default false agar modul lama tetap terlihat.
     */
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(false)->after('is_open')
                ->comment('true = disembunyikan dari mahasiswa (tidak terlihat sama sekali)');
        });
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('is_hidden');
        });
    }
};
