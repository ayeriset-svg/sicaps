@extends('layouts.app')
@section('title', 'Kelola Topik')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-brand-dark">Kelola Topik</h1>
    <p class="text-slate-500">{{ $ay->label }} · katalog topik yang ditawarkan + review topik tim.</p>
</div>

<div x-data="{ tab: '{{ (request('class') || request('status')) ? 'review' : 'katalog' }}' }">
    <div class="flex gap-2 mb-5">
        <button @click="tab='katalog'" :class="tab==='katalog' ? 'bg-brand text-white' : 'bg-white text-slate-600 border border-slate-200'" class="rounded-lg px-4 py-2 text-sm">Katalog Topik</button>
        <button @click="tab='review'" :class="tab==='review' ? 'bg-brand text-white' : 'bg-white text-slate-600 border border-slate-200'" class="rounded-lg px-4 py-2 text-sm">Review Topik Tim ({{ $pendingTeams->where('topic_status','pending')->count() }})</button>
    </div>

    {{-- Katalog --}}
    <div x-show="tab==='katalog'">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-3">
                @forelse($topics->where('origin','katalog') as $t)
                    <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-4" x-data="{ edit:false }">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-semibold text-slate-800">{{ $t->title }}</h3>
                                <p class="text-sm text-slate-500">{{ $t->partner?->name ?? 'Tanpa mitra' }} @if(!$t->is_available)<span class="text-xs text-red-500">· tidak tersedia</span>@endif</p>
                                @if($t->ai_features)<p class="text-xs text-slate-400 mt-1">AI: {{ \Illuminate\Support\Str::limit($t->ai_features,60) }}</p>@endif
                            </div>
                            <div class="flex items-center gap-1 text-sm whitespace-nowrap">
                                <button @click="edit=true" title="Edit" class="p-1.5 rounded-lg text-brand hover:bg-rose-50"><x-icon name="edit" /></button>
                                <form method="POST" action="{{ route('admin.topics.destroy', $t) }}" onsubmit="return confirm('Hapus topik?')">@csrf @method('DELETE')<button title="Hapus" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50"><x-icon name="trash" /></button></form>
                            </div>
                        </div>
                        <div x-show="edit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="edit=false">
                            <div class="bg-white rounded-xl p-6 w-full max-w-lg">
                                <h3 class="font-semibold mb-4">Edit Topik</h3>
                                @include('admin.topics._form', ['action' => route('admin.topics.update', $t), 'method' => 'PUT', 'topic' => $t, 'partners' => $partners])
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-slate-400">Belum ada topik katalog.</p>
                @endforelse
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-6 h-fit">
                <h2 class="font-semibold text-slate-800 mb-4">Tambah Topik Katalog</h2>
                @include('admin.topics._form', ['action' => route('admin.topics.store'), 'method' => 'POST', 'topic' => null, 'partners' => $partners])
            </div>
        </div>
    </div>

    {{-- Review --}}
    <div x-show="tab==='review'" x-cloak class="space-y-3">
        <form method="GET" class="flex flex-wrap gap-2 text-sm mb-1">
            <select name="class" onchange="this.form.submit()" class="rounded-lg border-rose-200 border px-3 py-2 bg-white">
                <option value="">Semua Kelas</option>
                @foreach($classes as $c)<option value="{{ $c }}" @selected(request('class')==$c)>{{ $c }}</option>@endforeach
            </select>
            <select name="status" onchange="this.form.submit()" class="rounded-lg border-rose-200 border px-3 py-2 bg-white">
                <option value="">Semua Status</option>
                <option value="pending" @selected(request('status')==='pending')>Belum di-review (Pending)</option>
                <option value="approved" @selected(request('status')==='approved')>Approved</option>
                <option value="rejected" @selected(request('status')==='rejected')>Rejected</option>
            </select>
            @if(request('class') || request('status'))<a href="{{ route('admin.topics.index') }}" class="px-2 py-2 text-brand hover:underline">Reset</a>@endif
        </form>

        <p class="text-xs text-slate-400">Klik baris untuk melihat fitur umum & fitur AI yang diajukan. Klik kolom <span class="font-medium">Status</span> untuk mengubah keputusan (approve / reject) beserta catatan.</p>

        <div class="bg-white rounded-2xl shadow-sm border border-rose-100 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Kelas</th>
                        <th class="px-4 py-3 font-medium">Tim</th>
                        <th class="px-4 py-3 font-medium">Kategori</th>
                        <th class="px-4 py-3 font-medium">Mitra</th>
                        <th class="px-4 py-3 font-medium">Nama Sistem</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 w-8"></th>
                    </tr>
                </thead>
                @forelse($pendingTeams as $team)
                    <tbody x-data="{ open:false, editStatus:false }" class="border-t border-slate-100">
                        <tr @click="open=!open" class="cursor-pointer hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-500">{{ $team->class_name ?? '—' }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $team->team_name }}<span class="block text-xs font-normal text-slate-400">{{ $team->leader->name ?? '—' }}</span></td>
                            <td class="px-4 py-3">
                                <span class="text-xs rounded-full px-2 py-0.5 font-medium {{ $team->topic?->origin === 'mandiri' ? 'bg-purple-100 text-purple-700' : 'bg-sky-100 text-sky-700' }}">{{ $team->topic?->origin === 'mandiri' ? 'Mandiri' : 'Katalog' }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ $team->topic?->partner_label ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $team->topic?->title ?? '—' }}</td>
                            <td class="px-4 py-3" @click.stop="open=true; editStatus=true">
                                <span class="inline-flex items-center gap-1 cursor-pointer hover:opacity-80" title="Klik untuk ubah status">
                                    <x-status-badge :status="$team->topic_status" />
                                    <x-icon name="edit" class="w-3.5 h-3.5 text-slate-400" />
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-400"><svg class="w-4 h-4 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg></td>
                        </tr>
                        <tr x-show="open" x-cloak>
                            <td colspan="7" class="px-4 py-4 bg-slate-50">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-xs font-semibold text-slate-500 mb-1 flex items-center gap-1"><x-icon name="cog" class="w-3.5 h-3.5" /> Fitur Umum Diajukan</p>
                                        <div class="rounded-lg border border-slate-200 bg-white p-3 text-sm text-slate-700 whitespace-pre-line">{{ $team->topic?->general_features ?: '—' }}</div>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold text-slate-500 mb-1 flex items-center gap-1"><x-icon name="cpu" class="w-3.5 h-3.5" /> Fitur AI Diajukan</p>
                                        <div class="rounded-lg border border-slate-200 bg-white p-3 text-sm text-slate-700 whitespace-pre-line">{{ $team->topic?->ai_features ?: '—' }}</div>
                                    </div>
                                </div>
                                @if($team->topic?->description)
                                    <div class="mt-3">
                                        <p class="text-xs font-semibold text-slate-500 mb-1 flex items-center gap-1"><x-icon name="document" class="w-3.5 h-3.5" /> Deskripsi</p>
                                        <div class="rounded-lg border border-slate-200 bg-white p-3 text-sm text-slate-700 whitespace-pre-line">{{ $team->topic->description }}</div>
                                    </div>
                                @endif

                                <div x-show="editStatus" x-cloak class="mt-4 rounded-lg border border-rose-200 bg-white p-4">
                                    <p class="text-sm font-semibold text-slate-700 mb-2">Keputusan Review Topik</p>
                                    <form method="POST" action="{{ route('admin.topics.review', $team) }}" class="flex flex-col sm:flex-row gap-2 sm:items-end">
                                        @csrf @method('PUT')
                                        <div class="sm:w-48">
                                            <label class="block text-xs text-slate-500 mb-1">Status</label>
                                            <select name="topic_status" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm">
                                                <option value="approved" @selected($team->topic_status==='approved')>Approve</option>
                                                <option value="rejected" @selected($team->topic_status==='rejected')>Reject</option>
                                                <option value="pending" @selected($team->topic_status==='pending')>Pending</option>
                                            </select>
                                        </div>
                                        <div class="flex-1">
                                            <label class="block text-xs text-slate-500 mb-1">Catatan</label>
                                            <input name="topic_review_note" value="{{ $team->topic_review_note }}" placeholder="Catatan untuk tim (opsional)..." class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm">
                                        </div>
                                        <button class="rounded-lg bg-brand text-white px-4 py-2 text-sm hover:bg-brand-dark whitespace-nowrap">Simpan Keputusan</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                @empty
                    <tbody><tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">Belum ada topik tim yang diajukan.</td></tr></tbody>
                @endforelse
            </table>
        </div>
    </div>
</div>
@endsection
