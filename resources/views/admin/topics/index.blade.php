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
        @forelse($pendingTeams as $team)
            <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs rounded-full bg-slate-100 px-2 py-0.5">{{ $team->topic?->origin === 'mandiri' ? 'Mandiri' : 'Katalog' }}</span>
                            <x-status-badge :status="$team->topic_status" />
                        </div>
                        <h3 class="font-semibold text-slate-800">{{ $team->topic?->title ?? '—' }}</h3>
                        <p class="text-sm text-slate-500">Tim {{ $team->team_name }} · {{ $team->leader->name }}</p>
                        @if($team->topic?->partner_label)<p class="text-xs text-slate-400">Mitra: {{ $team->topic->partner_label }}</p>@endif
                        @if($team->topic?->ai_features)<p class="text-xs text-slate-400">Fitur AI: {{ $team->topic->ai_features }}</p>@endif
                    </div>
                    <form method="POST" action="{{ route('admin.topics.review', $team) }}" class="flex flex-col gap-2 w-full sm:w-72">
                        @csrf @method('PUT')
                        <select name="topic_status" class="rounded-lg border-slate-300 border px-3 py-2 text-sm">
                            <option value="approved" @selected($team->topic_status==='approved')>Approve</option>
                            <option value="rejected" @selected($team->topic_status==='rejected')>Reject</option>
                            <option value="pending" @selected($team->topic_status==='pending')>Pending</option>
                        </select>
                        <input name="topic_review_note" value="{{ $team->topic_review_note }}" placeholder="Catatan..." class="rounded-lg border-slate-300 border px-3 py-2 text-sm">
                        <button class="rounded-lg bg-brand text-white px-4 py-2 text-sm hover:bg-brand-dark">Simpan Keputusan</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-slate-400">Belum ada topik tim yang diajukan.</p>
        @endforelse
    </div>
</div>
@endsection
