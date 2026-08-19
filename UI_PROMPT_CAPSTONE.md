# PROMPT & GUIDELINE UI SIM-CAPSTONE (Academic Orchid Pink & Purple Pastel)

**Versi:** 2.0 (Menyesuaikan PRD v2.0 RPS D3 SIA)  
**Tech Stack Target:** Laravel 10 (Blade + Controller/Service Layer), Tailwind CSS (CDN), Alpine.js (CDN)  
**Nuansa Visual:** Academic Soft Orchid (`#3B1E54`, `#5C2E7E`, `#8B5CF6`, `#F4E8F0`, `#FDF8FB`)

---

## 1. MAPPING SKEMA WARNA TAILWIND (ORCHID PINK & PURPLE)

Biar enggak standar dan kaku seperti template bawaan Bootstrap/AdminLTE, gunakan kombinasi warna Tailwind berikut secara konsisten:

| Elemen UI | Class Tailwind CSS | Keterangan & Hex Equivalent |
| :--- | :--- | :--- |
| **Top Navbar / Hero Header** | `bg-purple-950` / `bg-[#2E1441]` | Dark Imperial Violet (Kesan Akademik Elegan) |
| **Sidebar Active Item** | `bg-purple-800/60 text-pink-200 border-r-4 border-pink-400` | Highlight Menu Aktif |
| **Primary Button / Accent** | `bg-purple-700 hover:bg-purple-800 text-white shadow-sm shadow-purple-200` | Muted Plum (Aksi Utama) |
| **Secondary Button / Pill** | `bg-pink-100 hover:bg-pink-200 text-pink-900 border border-pink-200` | Soft Pastel Pink (Aksi Sekunder/Filter) |
| **Badge Approved / Lulus** | `bg-emerald-50 text-emerald-700 border border-emerald-200` | Status Positif |
| **Badge Revision / Warning**| `bg-pink-50 text-pink-700 border border-pink-200` | Revisi Logbook / Catatan |
| **Badge Pending / Evaluation**| `bg-amber-50 text-amber-700 border border-amber-200` | Menunggu Review / Peer Review |
| **Background Halaman (Canvas)**| `bg-[#FDF8FB]` atau `bg-slate-50/80` | Soft Warm Pastel White (Tidak Silau) |
| **Card Container** | `bg-white border border-purple-100/80 rounded-2xl shadow-sm hover:shadow-md transition-all` | Pembungkus Modul / Fitur |

---

## 2. PROMPT KHUSUS CLAUDE / CURSOR (SIAP COPAS)

Kamu tinggal copas prompt di bawah ini ke **Claude 3.5 Sonnet**, **Cursor**, atau **ChatGPT** tiap kali mau minta bikinin halaman Blade baru!

```markdown
Role: Lead UI/UX Engineer & Senior Laravel Developer.
Task: Buatkan file Blade Template lengkap (`.blade.php`) untuk aplikasi "SIM-CAPSTONE (D3 SIA)" halaman [SEBUTKAN NAMA HALAMAN, MISAL: Dashboard Superadmin / Logbook Mingguan / Evaluasi Peer 180°].

Aplikasi ini menggunakan Tech Stack:
- Laravel 10 (Blade + Alpine.js CDN + Tailwind CSS CDN)
- PRD v2.0 (2 Role: Superadmin & Mahasiswa, 16 Minggu Logbook Dinamis, 3 Assessment Milestones)

Panduan Design & Nuansa Visual (Wajib Dipatuhi):
1. Color Palette: "Academic Soft Orchid Pink & Purple"
   - Canvas/Background: `#FDF8FB` (Soft Pastel Off-White)
   - Primary Accent: Deep Purple/Plum (`bg-purple-900`, `bg-purple-700`)
   - Highlight & Badges: Orchid Pink (`bg-pink-100`, `text-pink-800`, `border-pink-200`)
   - Cards: White clean surface dengan border halus `border-purple-100/80` dan `rounded-2xl`.
2. Typography & Layout:
   - Header halaman yang mencolok dengan Breadcrumbs, Judul Utama, dan Ringkasan Stat (Stat Cards).
   - Tampilan bersih, modern, khas kampus bergengsi (akademik namun tidak kaku).
3. Interaktivitas (Alpine.js):
   - Sertakan kodingan Alpine.js (`x-data`, `x-show`, `@click`) untuk elemen interaktif seperti Modal Pop-up, Slide-over Drawer, Tab Switching, atau Dropdown Action.
4. Spesifikasi Halaman:
   [PASTE SPESIFIKASI MODUL DARI PRD DI SINI]

Format Output:
Berikan kode Blade `.blade.php` lengkap, valid, tanpa potongan (lengkap dengan mock data Blade `@forelse` / `@if` / `@json`), siap di-render di browser!
```

---

## 3. DESAIN KOMPONEN KUNCI (BLADE + ALPINE.JS CODE)

### A. Stat Cards (Overview Progress & Score)
```html
<div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-8">
    <!-- Stat Item 1 -->
    <div class="bg-white p-5 rounded-2xl border border-purple-100/80 shadow-sm hover:shadow-md transition-all">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-purple-900/60 uppercase tracking-wider">Progress Logbook</span>
            <span class="p-2 rounded-xl bg-purple-50 text-purple-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 9 11-18 0 9 9 0 0118 0z"></path></svg>
            </span>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
            <h4 class="text-2xl font-bold text-slate-800">12</h4>
            <span class="text-xs text-slate-500 font-medium">/ 16 Minggu</span>
        </div>
        <div class="mt-3 w-full bg-slate-100 rounded-full h-2 overflow-hidden">
            <div class="bg-gradient-to-r from-purple-700 to-pink-500 h-2 rounded-full" style="width: 75%"></div>
        </div>
    </div>

    <!-- Stat Item 2 -->
    <div class="bg-white p-5 rounded-2xl border border-purple-100/80 shadow-sm hover:shadow-md transition-all">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-purple-900/60 uppercase tracking-wider">Nilai Akhir (Estimasi)</span>
            <span class="p-2 rounded-xl bg-pink-50 text-pink-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </span>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
            <h4 class="text-2xl font-bold text-purple-900">88.50</h4>
            <span class="px-2 py-0.5 text-xs font-bold bg-pink-100 text-pink-800 rounded-lg border border-pink-200">Indeks A</span>
        </div>
        <p class="mt-3 text-xs text-slate-500">Berdasarkan A1, A2, & Peer Review</p>
    </div>
</div>
```

### B. Peer Evaluation Form (Vertikal ke Bawah Sesuai FR-5.1)
```html
<div x-data="{ scores: {} }" class="bg-white rounded-2xl border border-purple-100 p-6 shadow-sm">
    <div class="border-b border-purple-100 pb-4 mb-6">
        <h3 class="text-lg font-bold text-purple-950">Form Evaluasi Peer 180° — Assessment 1</h3>
        <p class="text-xs text-slate-500 mt-1">Berikan penilaian objektif untuk rekan tim Anda. Nilai disusun vertikal untuk kemudahan pengisian.</p>
    </div>

    <!-- Card Anggota Team Vertikal -->
    <template x-for="member in ['Ahmad Fauzi (Lead)', 'Siti Nurhaliza (UI/UX)', 'Budi Santoso (QA)']" :key="member">
        <div class="mb-6 p-5 rounded-xl border border-purple-100/60 bg-gradient-to-r from-purple-50/30 to-pink-50/20">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-purple-700 text-white font-bold flex items-center justify-center text-sm shadow-sm" x-text="member.charAt(0)"></div>
                    <div>
                        <h5 class="text-sm font-bold text-slate-800" x-text="member"></h5>
                        <span class="text-xs text-purple-700 font-medium">Anggota Kelompok</span>
                    </div>
                </div>
            </div>

            <!-- Kriteria Slider/Radio Vertikal -->
            <div class="space-y-4 bg-white p-4 rounded-xl border border-slate-100">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">1. Komunikasi & Kerjasama Tim (Skala 1-100)</label>
                    <input type="range" min="50" max="100" value="85" class="w-full accent-purple-700 h-2 bg-slate-200 rounded-lg cursor-pointer">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">2. Kontribusi Koding / Dokumen DPPL</label>
                    <input type="range" min="50" max="100" value="90" class="w-full accent-purple-700 h-2 bg-slate-200 rounded-lg cursor-pointer">
                </div>
            </div>
        </div>
    </template>

    <button class="w-full py-3 bg-purple-700 hover:bg-purple-800 text-white font-semibold text-sm rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        Simpan Penilaian Peer 180°
    </button>
</div>
```

---

## 4. CARA PENGGUNAAN FILE INI

1. Simpan file `UI_PROMPT_CAPSTONE.md` ini di dalam root folder project Laravel kamu (misal di `.docs/UI_PROMPT_CAPSTONE.md`).
2. Saat mau buat modul baru di Cursor atau Claude, sertakan file ini sebagai context.
3. Gunakan prompt template di Bab 2 untuk menghasilkan view Blade yang presisi dan cantik sesuai spesifikasi PRD!
