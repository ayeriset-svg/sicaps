<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Assessment TANPA field kustom = milestone (materi saja) → requires_submission false.
     * Assessment yang sudah diberi field oleh koordinator dibiarkan (butuh pengerjaan).
     */
    public function up(): void
    {
        DB::table('modules')
            ->where('type', 'assessment')
            ->where(function ($q) {
                $q->whereNull('fields_json')->orWhere('fields_json', '[]')->orWhere('fields_json', '');
            })
            ->update(['requires_submission' => false]);
    }

    public function down(): void
    {
        // Tidak dikembalikan (perubahan data intensional).
    }
};
