<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — SIM-CAPSTONE</title>
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
            <h1 class="text-3xl font-extrabold tracking-tight">SIM-CAPSTONE</h1>
            <p class="text-pink-100/80 text-sm mt-1">Sistem Informasi Manajemen Capstone Project</p>
            <p class="text-pink-200/50 text-xs mt-1">D3 Sistem Informasi Akuntansi</p>
        </div>

        <div class="bg-white/95 backdrop-blur rounded-3xl shadow-2xl shadow-black/30 p-8 border border-white/20">
            <h2 class="text-lg font-bold text-brand-dark mb-1">Masuk ke Akun</h2>
            <p class="text-sm text-slate-500 mb-6">Gunakan Email atau NIM/NIP Anda.</p>

            @if($errors->any())
                <div class="mb-4 rounded-xl bg-pink-50 border border-pink-200 px-4 py-3 text-sm text-pink-700">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Email / NIM / NIP</label>
                    <input name="login" value="{{ old('login') }}" required autofocus
                           class="w-full rounded-xl border border-rose-200 px-3 py-2.5 focus:border-brand focus:ring focus:ring-rose-200 outline-none transition"
                           placeholder="cth: 2101001 atau nama@kampus.ac.id">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Kata Sandi</label>
                    <input name="password" type="password" required
                           class="w-full rounded-xl border border-rose-200 px-3 py-2.5 focus:border-brand focus:ring focus:ring-rose-200 outline-none transition"
                           placeholder="••••••••">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-rose-300 text-brand focus:ring-rose-200"> Ingat saya
                </label>
                <button class="w-full rounded-xl bg-gradient-to-r from-brand to-brand-dark hover:from-brand-dark hover:to-brand-night text-white font-semibold py-2.5 shadow-md shadow-rose-900/20 transition">
                    Masuk
                </button>
            </form>
        </div>
        <p class="text-center text-pink-200/50 text-xs mt-6">© {{ date('Y') }} Program Studi D3 SIA · SIM-CAPSTONE v2.0</p>
    </div>
</div>
</body>
</html>
