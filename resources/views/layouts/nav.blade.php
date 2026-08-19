@php
    $item = function ($route, $label, $icon) {
        $active = request()->routeIs($route . '*');
        $cls = $active
            ? 'bg-white/15 text-white border-l-4 border-pink-300'
            : 'text-pink-100/75 hover:bg-white/10 border-l-4 border-transparent';
        return '<a href="' . route($route) . '" class="flex items-center gap-3 px-5 py-2.5 text-sm transition ' . $cls . '">'
            . '<span class="w-5 text-center opacity-90">' . $icon . '</span><span>' . e($label) . '</span></a>';
    };
    $header = fn ($t) => '<p class="px-5 mt-5 mb-1 text-[11px] font-semibold uppercase tracking-wider text-pink-200/50">' . e($t) . '</p>';
@endphp

@if($u->isMahasiswa())
    {!! $item('dashboard', 'Dashboard', '🏠') !!}
    {!! $item('team.index', 'Tim Saya', '👥') !!}
    {!! $item('topic.index', 'Topik & Mitra', '📝') !!}
    {!! $item('logbook.index', 'Logbook', '📒') !!}
    {!! $item('peer.index', 'Peer 180°', '🤝') !!}
    {!! $item('grade.me', 'Nilai Saya', '📊') !!}
@endif

@if($u->isSuperadmin())
    {!! $item('dashboard', 'Dashboard', '🏠') !!}

    {!! $header('Master Data') !!}
    {!! $item('admin.academic-years.index', 'Tahun Ajaran', '📅') !!}
    {!! $item('admin.users.index', 'Master User', '👤') !!}
    {!! $item('admin.students.index', 'Master Mahasiswa', '🎓') !!}
    {!! $item('admin.partners.index', 'Kelola Mitra', '🏢') !!}
    {!! $item('admin.topics.index', 'Kelola Topik', '💡') !!}
    {!! $item('admin.teams.index', 'Manajemen Tim', '👥') !!}
    {!! $item('admin.modules.index', 'Kelola Modul', '🗂️') !!}

    {!! $header('Penilaian') !!}
    {!! $item('admin.logbook-review.index', 'Review Logbook', '📖') !!}
    {!! $item('admin.scores.index', 'Input Penilaian', '✍️') !!}
    {!! $item('admin.peer-result.index', 'Hasil Peer 180°', '🤝') !!}
    {!! $item('admin.stages.index', 'Bobot & Stage', '⚖️') !!}
    {!! $item('admin.penalty.index', 'Penalty Absen', '⚠️') !!}
    {!! $item('admin.attendance.index', 'Presensi', '🗓️') !!}
    {!! $item('admin.grades.index', 'Rekap Nilai', '🏆') !!}

    {!! $header('Laporan') !!}
    {!! $item('admin.reports.index', 'Summary Report', '📈') !!}
@endif
