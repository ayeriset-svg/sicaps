<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

/**
 * Membuat / mereset akun Superadmin (Koordinator Capstone).
 *
 * Contoh:
 *   php artisan sicaps:create-admin --email="koordinator@kampus.ac.id" --password="SandiKuat123!"
 *   php artisan sicaps:create-admin --email="koordinator@kampus.ac.id"   (sandi digenerate acak)
 *
 * Bersifat idempotent: bila email/NIM sudah ada, akun tersebut di-set jadi superadmin
 * dan sandinya diperbarui (tidak menggandakan akun).
 */
class CreateSuperadmin extends Command
{
    protected $signature = 'sicaps:create-admin
        {--email= : Email login superadmin}
        {--password= : Sandi (kosong = digenerate acak & ditampilkan sekali)}
        {--name=Koordinator Capstone : Nama tampilan}
        {--nim=ADMIN01 : Nomor identitas (NIP/kode) unik}';

    protected $description = 'Membuat atau mereset akun Superadmin SIM-CAPSTONE';

    public function handle(): int
    {
        $email = $this->option('email') ?: $this->ask('Email login superadmin');
        $name = $this->option('name') ?: 'Koordinator Capstone';
        $nim = $this->option('nim') ?: 'ADMIN01';

        $password = $this->option('password');
        $generated = false;
        if (! $password) {
            $password = Str::password(16, symbols: false);
            $generated = true;
        }

        $validator = Validator::make(
            compact('email', 'name', 'nim', 'password'),
            [
                'email' => ['required', 'email', 'max:255'],
                'name' => ['required', 'string', 'max:255'],
                'nim' => ['required', 'string', 'max:30'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $err) {
                $this->error($err);
            }

            return self::FAILURE;
        }

        // Cari akun berdasarkan email ATAU NIM agar tidak bentrok unique.
        $user = User::where('email', $email)->orWhere('identity_number', $nim)->first();
        $isNew = ! $user;

        $user = User::updateOrCreate(
            ['id' => $user?->id],
            [
                'identity_number' => $nim,
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'superadmin',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        $this->newLine();
        $this->info($isNew ? '✅ Akun superadmin dibuat.' : '✅ Akun ditemukan & di-reset menjadi superadmin.');
        $this->line('  Email : ' . $user->email);
        $this->line('  NIM   : ' . $user->identity_number);
        if ($generated) {
            $this->warn('  Sandi : ' . $password . '   (disimpan sekali ini saja — catat & segera ganti!)');
        } else {
            $this->line('  Sandi : (sesuai yang Anda masukkan)');
        }
        $this->newLine();
        $this->line('Silakan login, lalu ganti sandi lewat menu Profil.');

        return self::SUCCESS;
    }
}
