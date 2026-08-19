@props(['label', 'value', 'icon' => '📌', 'color' => 'brand', 'hint' => null, 'progress' => null])
@php
    $tints = [
        'brand'   => 'bg-rose-100 text-brand',
        'purple'  => 'bg-rose-100 text-brand',
        'blue'    => 'bg-rose-100 text-brand',
        'pink'    => 'bg-pink-100 text-pink-600',
        'rose'    => 'bg-rose-100 text-rose-600',
        'indigo'  => 'bg-pink-100 text-pink-600',
        'green'   => 'bg-emerald-50 text-emerald-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'amber'   => 'bg-amber-50 text-amber-600',
    ];
    $tint = $tints[$color] ?? $tints['brand'];
@endphp
<div class="bg-white p-5 rounded-2xl border border-rose-100 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
    <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-brand-dark/60 uppercase tracking-wider">{{ $label }}</span>
        <span class="h-9 w-9 rounded-xl {{ $tint }} flex items-center justify-center text-lg">{{ $icon }}</span>
    </div>
    <div class="mt-2 flex items-baseline gap-2">
        <h4 class="text-2xl font-bold text-slate-800">{{ $value }}</h4>
    </div>
    @if(! is_null($progress))
        <div class="mt-3 w-full bg-rose-50 rounded-full h-2 overflow-hidden">
            <div class="bg-gradient-to-r from-brand to-pink-500 h-2 rounded-full" style="width: {{ max(0, min(100, $progress)) }}%"></div>
        </div>
    @endif
    @if($hint)
        <p class="mt-3 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
