<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('identity_number', 30)->unique()->comment('NIM / NIP / NIDN');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['superadmin', 'mahasiswa'])->default('mahasiswa');
            $table->string('angkatan', 10)->nullable()->comment('Tahun angkatan mahasiswa');
            $table->string('class_name', 30)->nullable()->comment('Kelas mahasiswa');
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(false)->comment('Paksa ganti sandi saat login pertama');
            $table->rememberToken();
            $table->timestamps();

            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
