<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIM-CAPSTONE')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: {
                    canvas: '#FDF6F6',
                    brand: { DEFAULT: '#A61010', dark: '#7E0B0B', night: '#5C0808', light: '#C6413F' },
                },
                fontFamily: { sans: ['Plus Jakarta Sans', 'Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
            } }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        [x-cloak]{display:none}
        body{font-family:'Plus Jakarta Sans','Inter',ui-sans-serif,system-ui,sans-serif}
        .rt-content{color:#1f2937;line-height:1.65}
        .rt-content img{max-width:100%;height:auto;border-radius:8px;margin:4px 0}
        .rt-content ul{list-style:disc;padding-left:1.5rem;margin:.5rem 0}
        .rt-content ol{list-style:decimal;padding-left:1.5rem;margin:.5rem 0}
        .rt-content a{color:#A61010;text-decoration:underline}
        .rt-content h1,.rt-content h2,.rt-content h3{font-weight:700;margin:.6rem 0 .3rem}
        .rt-content h1{font-size:1.5rem}.rt-content h2{font-size:1.25rem}.rt-content h3{font-size:1.1rem}
        .rt-content p{margin:.4rem 0}
        .rt-content blockquote{border-left:3px solid #e5a3a3;padding-left:12px;color:#6b7280;margin:.5rem 0}
        .rt-content table{border-collapse:collapse;margin:.5rem 0;max-width:100%}
        .rt-content table td,.rt-content table th{border:1px solid #d1d5db;padding:6px 8px}
        ::-webkit-scrollbar{height:9px;width:9px}
        ::-webkit-scrollbar-thumb{background:#f3c9c9;border-radius:9999px}
        .sidebar-scroll::-webkit-scrollbar-thumb{background:rgba(255,255,255,.18)}
    </style>
    @stack('head')
</head>
<body class="h-full bg-canvas text-slate-800">
@auth
<div x-data="{ sidebar: false }" class="min-h-full">
    {{-- ===================== SIDEBAR ===================== --}}
    <aside
        class="fixed inset-y-0 left-0 z-40 w-64 bg-gradient-to-b from-brand-dark to-brand-night text-white flex flex-col transition-transform duration-200 lg:translate-x-0"
        :class="sidebar ? 'translate-x-0' : '-translate-x-full'">
        <div class="h-16 flex items-center px-6 border-b border-white/10">
            <a href="{{ route('dashboard') }}" class="font-extrabold text-lg tracking-tight">SIM-CAPSTONE</a>
        </div>
        <div class="flex-1 overflow-y-auto sidebar-scroll py-4">
            @php $u = auth()->user(); @endphp
            @include('layouts.nav')
        </div>
        <div class="border-t border-white/10 p-4 text-[11px] text-pink-200/50">
            SIM-CAPSTONE · v2.0
        </div>
    </aside>

    {{-- overlay mobile --}}
    <div x-show="sidebar" x-cloak @click="sidebar=false" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

    {{-- ===================== MAIN ===================== --}}
    <div class="lg:pl-64 min-h-full flex flex-col">
        @if(session()->has('impersonator_id'))
            <div class="bg-pink-600 text-white text-sm px-4 sm:px-6 lg:px-8 py-2 flex items-center justify-between gap-3 flex-wrap">
                <span class="flex items-center gap-2">🔍 <strong>Mode Observasi</strong> — Anda melihat sebagai <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->identity_number }}).</span>
                <form method="POST" action="{{ route('observe.stop') }}">
                    @csrf
                    <button class="rounded-lg bg-white/20 hover:bg-white/30 px-3 py-1 font-semibold transition">↩︎ Kembali ke Superadmin</button>
                </form>
            </div>
        @endif
        <header class="sticky top-0 z-20 h-16 bg-white/90 backdrop-blur border-b border-rose-100 flex items-center justify-between px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <button @click="sidebar=true" class="lg:hidden text-brand text-2xl leading-none">☰</button>
                @if($ay = \App\Models\AcademicYear::active())
                    <span class="text-xs font-medium bg-rose-50 text-brand rounded-full px-3 py-1 border border-rose-100">{{ $ay->label }}</span>
                @else
                    <span class="text-xs text-slate-400">Tidak ada tahun ajaran aktif</span>
                @endif
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-slate-500 hidden lg:block">D3 Sistem Informasi Akuntansi</span>
                @php $observing = session()->has('impersonator_id'); $u = auth()->user(); @endphp
                <div class="relative" x-data="{ open:false }" @click.outside="open=false">
                    <button @click="open=!open" class="flex items-center gap-2 rounded-xl hover:bg-rose-50 pl-1.5 pr-2 py-1.5 transition border border-transparent hover:border-rose-100">
                        <div class="h-8 w-8 rounded-full bg-gradient-to-br from-brand to-pink-400 text-white flex items-center justify-center font-bold text-sm shadow-sm">{{ strtoupper(substr($u->name, 0, 1)) }}</div>
                        <div class="text-left hidden sm:block leading-tight">
                            <p class="text-sm font-semibold text-slate-800 max-w-[150px] truncate">{{ $u->name }}</p>
                            <p class="text-[11px] uppercase tracking-wide text-brand/70">{{ $observing ? 'observasi' : $u->role }}</p>
                        </div>
                        <span class="text-slate-400 text-[10px]">▾</span>
                    </button>
                    <div x-show="open" x-cloak x-transition.origin.top.right class="absolute right-0 mt-2 w-60 rounded-xl bg-white shadow-xl shadow-rose-900/10 ring-1 ring-rose-100 py-1.5 z-50">
                        <div class="px-4 py-2.5 border-b border-rose-50">
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ $u->name }}</p>
                            <p class="text-xs text-slate-400">{{ $u->identity_number }} · {{ ucfirst($u->role) }}</p>
                        </div>
                        <a href="{{ route('profile.show') }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-rose-50 {{ request()->routeIs('profile.*') ? 'text-brand font-medium' : 'text-slate-700' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            Profil Akun
                        </a>
                        @if($observing)
                            <form method="POST" action="{{ route('observe.stop') }}">
                                @csrf
                                <button class="w-full flex items-center gap-2 px-4 py-2 text-sm text-pink-700 hover:bg-pink-50">↩︎ Kembali ke Superadmin</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    Keluar
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 sm:px-6 lg:px-8 py-7 max-w-[1400px] w-full mx-auto">
            @if(session('success'))
                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 flex items-center gap-2"><span>✅</span> {{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 rounded-xl border border-pink-200 bg-pink-50 px-4 py-3 text-pink-800 flex items-center gap-2"><span>⚠️</span> {{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-4 rounded-xl border border-pink-200 bg-pink-50 px-4 py-3 text-pink-800">
                    <p class="font-semibold mb-1">Terdapat kesalahan input:</p>
                    <ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
@else
    @yield('content')
@endauth
@stack('scripts')
</body>
</html>
