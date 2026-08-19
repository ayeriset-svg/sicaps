@props(['status'])
@php
    // Palette guideline: Approved/Lulus=emerald, Revision=pink, Pending/Eval=amber
    $map = [
        'Not Started'     => ['bg-slate-100 text-slate-600 border-slate-200', 'Belum Mulai'],
        'Pending'         => ['bg-amber-50 text-amber-700 border-amber-200', 'Menunggu Review'],
        'Revision Needed' => ['bg-pink-50 text-pink-700 border-pink-200', 'Perlu Revisi'],
        'Approved'        => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'Disetujui'],
        'none'            => ['bg-slate-100 text-slate-500 border-slate-200', 'Belum Ada'],
        'pending'         => ['bg-amber-50 text-amber-700 border-amber-200', 'Pending'],
        'approved'        => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'Disetujui'],
        'rejected'        => ['bg-pink-50 text-pink-700 border-pink-200', 'Ditolak'],
    ];
    [$cls, $label] = $map[$status] ?? ['bg-slate-100 text-slate-600 border-slate-200', $status];
@endphp
<span class="inline-flex items-center rounded-full border {{ $cls }} px-2.5 py-0.5 text-xs font-semibold">{{ $label }}</span>
