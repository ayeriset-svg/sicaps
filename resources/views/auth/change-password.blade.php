<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aktivasi Akun — SIM-CAPSTONE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body{font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif}</style>
</head>
<body class="h-full bg-gradient-to-br from-[#5C0808] via-[#7E0B0B] to-[#A61010]">
<div class="min-h-full flex items-center justify-center p-4 relative overflow-hidden">
    <div class="absolute -top-24 -left-24 h-72 w-72 rounded-full bg-pink-400/25 blur-3xl"></div>
    <div class="absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-rose-500/20 blur-3xl"></div>

    <div class="w-full max-w-md relative">
        <div class="text-center text-white mb-6">
            <h1 class="text-2xl font-extrabold tracking-tight">Aktivasi Akun</h1>
            <p class="text-pink-100/80 text-sm mt-1">Buat kata sandi baru untuk mengamankan akun Anda.</p>
        </div>

        <div class="bg-white/95 backdrop-blur rounded-3xl shadow-2xl shadow-black/30 p-8 border border-white/20">
            <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-brand-dark">
                Masuk sebagai <strong>{{ $user->name }}</strong> ({{ $user->identity_number }}).
                Ini login pertama Anda — silakan ganti kata sandi default terlebih dahulu.
            </div>

            @if($errors->any())
                <div class="mb-4 rounded-xl bg-pink-50 border border-pink-200 px-4 py-3 text-sm text-pink-700">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('password.change.update') }}" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Kata Sandi Baru</label>
                    <input name="password" type="password" required autofocus
                           class="w-full rounded-xl border border-rose-200 px-3 py-2.5 focus:border-brand focus:ring focus:ring-rose-200 outline-none transition"
                           placeholder="Minimal 8 karakter">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Konfirmasi Kata Sandi</label>
                    <input name="password_confirmation" type="password" required
                           class="w-full rounded-xl border border-rose-200 px-3 py-2.5 focus:border-brand focus:ring focus:ring-rose-200 outline-none transition"
                           placeholder="Ulangi kata sandi baru">
                </div>
                <p class="text-xs text-slate-400">Tidak boleh sama dengan NIM Anda. Minimal 8 karakter.</p>
                <button class="w-full rounded-xl bg-gradient-to-r from-brand to-brand-dark hover:from-brand-dark hover:to-brand-night text-white font-semibold py-2.5 shadow-md shadow-rose-900/20 transition">
                    Simpan & Aktifkan Akun
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">
                @csrf
                <button class="text-sm text-slate-500 hover:text-brand">Keluar</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
