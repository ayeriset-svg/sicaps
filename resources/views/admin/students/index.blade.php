@extends('layouts.app')
@section('title', 'Master Mahasiswa')

@section('content')
<div class="mb-6 flex items-start justify-between flex-wrap gap-3">
    <div>
        <h1 class="text-2xl font-bold text-brand-dark">Master Data Mahasiswa</h1>
        <p class="text-slate-500">Data lengkap per kelas & angkatan. Import mendukung data + nilai historis.</p>
    </div>
    <div class="flex gap-2" x-data="{ addOpen:false, importOpen:false }">
        <button @click="importOpen=true" class="rounded-lg bg-slate-100 px-4 py-2 text-sm hover:bg-slate-200">Import Excel</button>
        <button @click="addOpen=true" class="rounded-lg bg-brand text-white px-4 py-2 text-sm hover:bg-brand-dark">+ Tambah Mahasiswa</button>

        {{-- Modal import --}}
        <div x-show="importOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 text-left" @click.self="importOpen=false">
            <div class="bg-white rounded-xl p-6 w-full max-w-lg">
                <h3 class="font-semibold mb-2">Import Master Mahasiswa + Nilai Historis</h3>
                <p class="text-xs text-slate-500 mb-3">Kolom (baris pertama):<br>
                    <code class="text-[11px]">identity_number, name, angkatan, class_name, password, year, semester, final_score, grade_letter</code><br>
                    Kolom <code>year..grade_letter</code> opsional — bila diisi, dibuat nilai historis (tahun ajaran otomatis diarsipkan). Isi langsung di template Excel, lalu unggah.
                </p>
                <a href="{{ route('admin.templates.students') }}" class="inline-flex items-center gap-1.5 mb-3 text-sm text-brand hover:underline">⬇️ Unduh Template Excel (.xlsx)</a>
                <form method="POST" action="{{ route('admin.students.import') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <input type="file" name="file" accept=".xlsx,.csv" required class="w-full text-sm">
                    <div class="flex justify-end gap-2"><button type="button" @click="importOpen=false" class="px-4 py-2 text-sm">Batal</button><button class="rounded-lg bg-brand text-white px-4 py-2 text-sm">Import</button></div>
                </form>
            </div>
        </div>

        {{-- Modal tambah mahasiswa --}}
        <div x-show="addOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 text-left" @click.self="addOpen=false">
            <div class="bg-white rounded-xl p-6 w-full max-w-lg">
                <h3 class="font-semibold mb-4">Tambah Mahasiswa</h3>
                <form method="POST" action="{{ route('admin.students.store') }}" class="grid grid-cols-2 gap-3">
                    @csrf
                    <div><label class="block text-xs font-medium mb-1">NIM</label><input name="identity_number" required class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm"></div>
                    <div><label class="block text-xs font-medium mb-1">Nama</label><input name="name" required class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm"></div>
                    <div><label class="block text-xs font-medium mb-1">Angkatan</label><input name="angkatan" placeholder="2021" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm"></div>
                    <div><label class="block text-xs font-medium mb-1">Kelas</label><input name="class_name" placeholder="SIA-3A" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm"></div>
                    <div class="col-span-2"><label class="block text-xs font-medium mb-1">Password <span class="text-slate-400">(opsional — kosong = NIM)</span></label><input name="password" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm"></div>
                    <div class="col-span-2 flex justify-end gap-2 mt-2">
                        <button type="button" @click="addOpen=false" class="px-4 py-2 text-sm">Batal</button>
                        <button class="rounded-lg bg-brand text-white px-4 py-2 text-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@if($pendingCount > 0)
    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 flex items-center justify-between flex-wrap gap-2">
        <span>⏳ <strong>{{ $pendingCount }}</strong> mahasiswa <strong>belum aktivasi</strong> (masih pakai sandi default = NIM, belum ganti sandi saat login pertama).</span>
        <a href="{{ route('admin.students.index', ['activation' => 'pending']) }}" class="rounded-lg bg-amber-600 text-white px-3 py-1.5 hover:bg-amber-700">Lihat yang belum aktivasi →</a>
    </div>
@endif

<form method="GET" class="mb-4 flex flex-wrap gap-2 text-sm">
    <input name="q" value="{{ request('q') }}" placeholder="Cari nama / NIM..." class="rounded-lg border-slate-300 border px-3 py-2 flex-1 max-w-xs">
    <select name="angkatan" class="rounded-lg border-slate-300 border px-3 py-2"><option value="">Semua Angkatan</option>@foreach($angkatans as $a)<option value="{{ $a }}" @selected(request('angkatan')==$a)>{{ $a }}</option>@endforeach</select>
    <select name="class" class="rounded-lg border-slate-300 border px-3 py-2"><option value="">Semua Kelas</option>@foreach($classes as $c)<option value="{{ $c }}" @selected(request('class')==$c)>{{ $c }}</option>@endforeach</select>
    <select name="activation" class="rounded-lg border-slate-300 border px-3 py-2">
        <option value="">Semua Status</option>
        <option value="pending" @selected(request('activation')==='pending')>Belum Aktivasi</option>
        <option value="active" @selected(request('activation')==='active')>Sudah Aktivasi</option>
    </select>
    <select name="per_page" onchange="this.form.submit()" class="rounded-lg border-rose-200 border px-3 py-2 bg-white" title="Baris per halaman">
        @foreach([10,20,50] as $pp)<option value="{{ $pp }}" @selected((string)request('per_page','20')===(string)$pp)>Show {{ $pp }}</option>@endforeach
        <option value="all" @selected(request('per_page')==='all')>Show All</option>
    </select>
    <button class="rounded-lg bg-brand text-white px-4 py-2 hover:bg-brand-dark">Filter</button>
    @if(request()->hasAny(['q','angkatan','class','activation','per_page']))<a href="{{ route('admin.students.index') }}" class="px-2 py-2 text-brand hover:underline">Reset</a>@endif
</form>

<div x-data="{ selected: [], allIds: @js($students->pluck('id')->map(fn ($i) => (string) $i)) }">
    {{-- Bulk action bar --}}
    <div x-show="selected.length" x-cloak class="mb-3 flex items-center justify-between gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5">
        <span class="text-sm text-brand-dark"><strong x-text="selected.length"></strong> mahasiswa dipilih</span>
        <div class="flex items-center gap-2">
            <button type="button" @click="selected = []" class="text-sm text-slate-500 hover:underline">Batal pilih</button>
            <form method="POST" action="{{ route('admin.students.bulk-destroy') }}"
                  @submit="if(!confirm('Hapus '+selected.length+' mahasiswa terpilih? Yang masih tergabung tim akan dilewati. Tindakan ini tidak dapat dibatalkan.')){ $event.preventDefault(); }">
                @csrf
                <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                <button class="rounded-lg bg-red-600 text-white px-4 py-1.5 text-sm font-medium hover:bg-red-700">🗑️ Hapus Terpilih</button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-rose-100 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
            <tr>
                <th class="px-5 py-3 w-8"><input type="checkbox" title="Pilih semua"
                        :checked="allIds.length && selected.length === allIds.length"
                        @change="selected = $event.target.checked ? [...allIds] : []"></th>
                <th class="px-5 py-3 font-medium">NIM</th><th class="px-5 py-3 font-medium">Nama</th><th class="px-5 py-3 font-medium">Angkatan</th><th class="px-5 py-3 font-medium">Kelas</th><th class="px-5 py-3 font-medium">Aktivasi</th><th class="px-5 py-3"></th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($students as $s)
                <tr x-data="{ edit:false }" :class="selected.includes('{{ $s->id }}') && 'bg-rose-50/50'">
                    <td class="px-5 py-3"><input type="checkbox" value="{{ $s->id }}" x-model="selected"></td>
                    <td class="px-5 py-3 font-mono text-slate-600">{{ $s->identity_number }}</td>
                    <td class="px-5 py-3 font-medium text-slate-800">{{ $s->name }}</td>
                    <td class="px-5 py-3">{{ $s->angkatan ?? '—' }}</td>
                    <td class="px-5 py-3">{{ $s->class_name ?? '—' }}</td>
                    <td class="px-5 py-3">
                        @if($s->must_change_password)
                            <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 text-amber-700 px-2.5 py-0.5 text-xs font-semibold">⏳ Belum Aktivasi</span>
                        @else
                            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700 px-2.5 py-0.5 text-xs font-semibold">✓ Aktif</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right whitespace-nowrap">
                        <div class="inline-flex items-center gap-1">
                            <button @click="edit=true" title="Edit" class="p-1.5 rounded-lg text-brand hover:bg-rose-50"><x-icon name="edit" /></button>
                            <form method="POST" action="{{ route('admin.students.destroy', $s) }}" class="inline" onsubmit="return confirm('Hapus mahasiswa {{ $s->name }}? Tindakan ini tidak dapat dibatalkan.')">@csrf @method('DELETE')<button title="Hapus" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50"><x-icon name="trash" /></button></form>
                        </div>

                        {{-- Modal edit --}}
                        <div x-show="edit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 text-left" @click.self="edit=false">
                            <div class="bg-white rounded-xl p-6 w-full max-w-lg">
                                <h3 class="font-semibold mb-4">Edit {{ $s->name }}</h3>
                                <form method="POST" action="{{ route('admin.students.update', $s) }}" class="grid grid-cols-2 gap-3">
                                    @csrf @method('PUT')
                                    <div><label class="block text-xs font-medium mb-1">NIM</label><input name="identity_number" value="{{ $s->identity_number }}" required class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm"></div>
                                    <div><label class="block text-xs font-medium mb-1">Nama</label><input name="name" value="{{ $s->name }}" required class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm"></div>
                                    <div><label class="block text-xs font-medium mb-1">Angkatan</label><input name="angkatan" value="{{ $s->angkatan }}" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm"></div>
                                    <div><label class="block text-xs font-medium mb-1">Kelas</label><input name="class_name" value="{{ $s->class_name }}" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm"></div>
                                    <div class="col-span-2"><label class="block text-xs font-medium mb-1">Reset Password <span class="text-slate-400">(kosongkan = tetap)</span></label><input name="password" placeholder="Sandi baru → wajib ganti saat login" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm"></div>
                                    <div class="col-span-2 flex justify-end gap-2 mt-2">
                                        <button type="button" @click="edit=false" class="px-4 py-2 text-sm">Batal</button>
                                        <button class="rounded-lg bg-brand text-white px-4 py-2 text-sm">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-5 py-8 text-center text-slate-400">Tidak ada data mahasiswa.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
<div class="mt-4">{{ $students->links() }}</div>
@endsection
