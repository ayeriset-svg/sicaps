@extends('layouts.app')
@section('title', 'Bobot & Stage Penilaian')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-brand-dark">Bobot & Stage Penilaian</h1>
    <p class="text-slate-500">{{ $ay->label }} · atur bobot tiap assessment, porsi peer 180, buka/tutup peer, dan kriteria rubrik.</p>
</div>

@php $totalOk = abs($total - 100) < 0.01; @endphp
<div class="mb-4 rounded-lg px-4 py-3 text-sm {{ $totalOk ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
    Total bobot assessment: <span class="font-bold">{{ number_format($total,2) }}%</span> {{ $totalOk ? '(seimbang ✓)' : '(disarankan 100%)' }}
</div>

<div class="space-y-6">
    @foreach($stages as $stage)
        <div class="bg-white rounded-2xl shadow-sm border border-rose-100">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h2 class="font-semibold text-slate-800">{{ $stage->code }} — {{ $stage->name }}</h2>
                    <p class="text-xs text-slate-400">Bobot {{ number_format($stage->weight_percentage,0) }}% · Peer {{ number_format($stage->peer_weight_percentage,0) }}%</p>
                </div>
                <form method="POST" action="{{ route('admin.stages.toggle-peer', $stage) }}">
                    @csrf
                    <button class="rounded-lg px-3 py-1.5 text-sm {{ $stage->peer_open ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                        Peer 180°: {{ $stage->peer_open ? 'DIBUKA ✓' : 'Tertutup' }} — klik untuk {{ $stage->peer_open ? 'tutup' : 'buka' }}
                    </button>
                </form>
            </div>
            <div class="p-5 grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <form method="POST" action="{{ route('admin.stages.update', $stage) }}" class="space-y-3">
                        @csrf @method('PUT')
                        <div><label class="block text-sm font-medium mb-1">Nama Stage</label><input name="name" value="{{ $stage->name }}" class="w-full rounded-lg border-slate-300 border px-3 py-2"></div>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="block text-sm font-medium mb-1">Bobot (%)</label><input type="number" step="0.01" name="weight_percentage" value="{{ $stage->weight_percentage }}" class="w-full rounded-lg border-slate-300 border px-3 py-2"></div>
                            <div><label class="block text-sm font-medium mb-1">Porsi Peer (%)</label><input type="number" step="0.01" name="peer_weight_percentage" value="{{ $stage->peer_weight_percentage }}" class="w-full rounded-lg border-slate-300 border px-3 py-2"></div>
                        </div>
                        <button class="rounded-lg bg-slate-700 text-white px-4 py-2 text-sm hover:bg-slate-800">Simpan Bobot</button>
                    </form>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-700 mb-2">Kriteria Rubrik</p>
                    <div class="space-y-1 mb-3">
                        @foreach($stage->criteria as $c)
                            <div class="flex items-center justify-between rounded border border-slate-200 px-3 py-1.5 text-sm">
                                <span>{{ $c->name }}</span>
                                <form method="POST" action="{{ route('admin.criteria.destroy', $c) }}" onsubmit="return confirm('Hapus kriteria?')">@csrf @method('DELETE')<button title="Hapus kriteria" class="p-1 rounded text-red-500 hover:bg-red-50"><x-icon name="x" class="w-3.5 h-3.5" /></button></form>
                            </div>
                        @endforeach
                    </div>
                    <form method="POST" action="{{ route('admin.stages.criteria.add', $stage) }}" class="flex gap-2">
                        @csrf
                        <input name="name" required placeholder="Kriteria baru..." class="flex-1 rounded-lg border-slate-300 border px-3 py-2 text-sm">
                        <button class="rounded-lg bg-brand text-white px-3 py-2 text-sm hover:bg-brand-dark">+ Tambah</button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
