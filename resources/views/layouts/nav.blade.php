@php
    $item = function ($route, $label, $icon) {
        $active = request()->routeIs($route . '*');
        $cls = $active
            ? 'bg-white/15 text-white border-l-4 border-pink-300'
            : 'text-pink-100/75 hover:bg-white/10 border-l-4 border-transparent';
        return '<a href="' . route($route) . '" class="flex items-center gap-3 px-5 py-2.5 text-sm transition ' . $cls . '">'
            . \App\Support\Icon::svg($icon, 'w-5 h-5 shrink-0 opacity-90') . '<span>' . e($label) . '</span></a>';
    };
    $header = fn ($t) => '<p class="px-5 mt-5 mb-1 text-[11px] font-semibold uppercase tracking-wider text-pink-200/50">' . e($t) . '</p>';
@endphp

@if($u->isMahasiswa())
    {!! $item('dashboard', 'Dashboard', 'home') !!}
    {!! $item('team.index', 'Tim Saya', 'users') !!}
    {!! $item('topic.index', 'Topik & Mitra', 'pencil') !!}
    {!! $item('logbook.index', 'Logbook', 'clipboard') !!}
    {!! $item('peer.index', 'Peer 180°', 'chat') !!}
    {!! $item('grade.me', 'Nilai Saya', 'chart') !!}
    {!! $item('manual.index', 'Manual Book', 'book') !!}
@endif

@if($u->isSuperadmin())
    {!! $item('dashboard', 'Dashboard', 'home') !!}

    {!! $header('Master Data') !!}
    {!! $item('admin.academic-years.index', 'Tahun Ajaran', 'calendar') !!}
    {!! $item('admin.users.index', 'Master User', 'user') !!}
    {!! $item('admin.students.index', 'Master Mahasiswa', 'academic') !!}
    {!! $item('admin.partners.index', 'Kelola Mitra', 'building') !!}
    {!! $item('admin.topics.index', 'Kelola Topik', 'bulb') !!}
    {!! $item('admin.teams.index', 'Manajemen Tim', 'users') !!}
    {!! $item('admin.outcomes.index', 'Capaian (CLO)', 'flag') !!}
    {!! $item('admin.modules.index', 'Kelola Modul', 'folder') !!}

    {!! $header('Penilaian') !!}
    {!! $item('admin.logbook-review.index', 'Review Logbook', 'clipboard-check') !!}
    {!! $item('admin.scores.index', 'Input Penilaian', 'pencil') !!}
    {!! $item('admin.peer-result.index', 'Hasil Peer 180°', 'chat') !!}
    {!! $item('admin.stages.index', 'Bobot & Stage', 'scale') !!}
    {!! $item('admin.penalty.index', 'Penalty Absen', 'warning') !!}
    {!! $item('admin.attendance.index', 'Presensi', 'calendar') !!}
    {!! $item('admin.grades.index', 'Rekap Nilai', 'trophy') !!}

    {!! $header('Laporan') !!}
    {!! $item('admin.reports.index', 'Summary Report', 'presentation') !!}
    {!! $item('admin.activity-logs.index', 'Audit Log', 'shield') !!}

    {!! $header('Panduan') !!}
    {!! $item('admin.manual-books.index', 'Manual Book', 'book') !!}
@endif
