@props(['level' => 1, 'showDesc' => false])
@php
    $lv = (int) $level;
    $info = config('capstone.ai_levels.' . $lv, config('capstone.ai_levels.1'));
    // Warna: level rendah (ketat) = merah, level tinggi (bebas) = hijau.
    $tints = [
        1 => 'bg-red-50 text-red-700 border-red-200',
        2 => 'bg-orange-50 text-orange-700 border-orange-200',
        3 => 'bg-amber-50 text-amber-700 border-amber-200',
        4 => 'bg-lime-50 text-lime-700 border-lime-200',
        5 => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    ];
    $cls = $tints[$lv] ?? $tints[1];
@endphp
<span class="inline-flex items-center gap-1 rounded-full border {{ $cls }} px-2.5 py-0.5 text-xs font-semibold" title="{{ $info['desc'] }}">
    🤖 Level {{ $lv }} · {{ $info['name'] }}
</span>
@if($showDesc)
    <p class="text-xs text-slate-500 mt-1">{{ $info['desc'] }}</p>
@endif
