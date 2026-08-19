@extends('layouts.app')
@section('title', 'Peer Evaluation 180°')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-brand-dark">Peer Evaluation 180°</h1>
    <p class="text-slate-500">Dilakukan per tahap assessment. Hanya tahap yang dibuka koordinator yang dapat diisi.</p>
</div>

@php $openStages = $stages->where('peer_open', true); @endphp

@if($openStages->isEmpty())
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-amber-800">
        Belum ada tahap Peer 180° yang dibuka. Peer dibuka koordinator setelah tiap assessment.
    </div>
@endif

<div x-data="{ stage: '{{ $openStages->first()->id ?? '' }}' }">
    @if($openStages->isNotEmpty())
        <div class="flex flex-wrap gap-2 mb-5">
            @foreach($openStages as $s)
                <button @click="stage='{{ $s->id }}'" :class="stage==='{{ $s->id }}' ? 'bg-brand text-white' : 'bg-white text-slate-600 border border-slate-200'" class="rounded-lg px-4 py-2 text-sm">{{ $s->code }} — {{ $s->name }}</button>
            @endforeach
        </div>
    @endif

    @foreach($openStages as $s)
        <div x-show="stage==='{{ $s->id }}'" class="space-y-4">
            @foreach($members as $member)
                @php $ev = $existing[$s->id][$member->id] ?? null; @endphp
                <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-semibold text-slate-800">{{ $member->name }}
                                @if($member->id === $user->id)<span class="ml-1 text-xs bg-slate-100 text-slate-600 rounded px-1.5 py-0.5">Diri sendiri</span>@endif
                            </h3>
                            <p class="text-sm text-slate-500">{{ $member->identity_number }}</p>
                        </div>
                        @if($ev)<span class="text-sm font-semibold text-green-700">Nilai: {{ number_format($ev->final_peer_score,1) }}</span>
                        @else<span class="text-sm text-amber-600">Belum dinilai</span>@endif
                    </div>
                    {{-- Layout vertikal: tiap kriteria satu baris ke bawah --}}
                    <form method="POST" action="{{ route('peer.store') }}" class="space-y-3">
                        @csrf
                        <input type="hidden" name="stage_id" value="{{ $s->id }}">
                        <input type="hidden" name="evaluatee_id" value="{{ $member->id }}">
                        @foreach($criteria as $field => $label)
                            <div class="flex items-center gap-3">
                                <label class="flex-1 text-sm text-slate-600">{{ $label }}</label>
                                <input type="number" min="0" max="100" step="0.5" name="{{ $field }}" required
                                       value="{{ $ev->$field ?? '' }}" class="w-28 rounded-lg border-slate-300 border px-3 py-2">
                            </div>
                        @endforeach
                        <div>
                            <label class="block text-sm text-slate-600 mb-1">Feedback (opsional)</label>
                            <input name="feedback" value="{{ $ev->feedback ?? '' }}" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm">
                        </div>
                        <button class="rounded-lg bg-brand text-white px-4 py-2 text-sm hover:bg-brand-dark">{{ $ev ? 'Perbarui' : 'Simpan' }} Nilai</button>
                    </form>
                </div>
            @endforeach
        </div>
    @endforeach
</div>
@endsection
