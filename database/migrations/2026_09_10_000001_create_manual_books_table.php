<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Manual Book / Panduan penggunaan sistem — dikelola superadmin, ditampilkan
     * ke seluruh pengguna (mahasiswa & superadmin).
     */
    public function up(): void
    {
        Schema::create('manual_books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('content')->nullable()->comment('Isi panduan (rich HTML + gambar base64)');
            $table->unsignedInteger('order_index')->default(0);
            $table->boolean('is_published')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_books');
    }
};
