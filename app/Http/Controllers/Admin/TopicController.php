<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Partner;
use App\Models\Team;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TopicController extends Controller
{
    /**
     * Kelola katalog topik + review topik yang dipilih/diajukan tim.
     */
    public function index(Request $request)
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404, 'Aktifkan tahun ajaran terlebih dahulu.');

        $topics = Topic::with('partner', 'team.leader')
            ->where('academic_year_id', $ay->id)
            ->orderByDesc('origin')->orderBy('title')->get();

        $partners = Partner::orderBy('name')->get();

        // Tim yang mengajukan topik untuk direview (+ filter kelas & status).
        $pendingTeams = Team::with('topic.partner', 'leader')
            ->where('academic_year_id', $ay->id)
            ->where('topic_status', '!=', 'none')
            ->when($request->filled('class'), fn ($q) => $q->whereHas('leader', fn ($l) => $l->where('class_name', $request->class)))
            ->when($request->filled('status'), fn ($q) => $q->where('topic_status', $request->status))
            ->orderByRaw("FIELD(topic_status,'pending','approved','rejected')")
            ->get();

        $classes = \App\Models\User::where('role', 'mahasiswa')->whereNotNull('class_name')->distinct()->orderBy('class_name')->pluck('class_name');

        return view('admin.topics.index', compact('topics', 'partners', 'pendingTeams', 'ay', 'classes'));
    }

    public function store(Request $request)
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404);

        $data = $this->validated($request);
        $data['academic_year_id'] = $ay->id;
        $data['origin'] = 'katalog';
        $data['created_by'] = Auth::id();
        $data['is_available'] = $request->boolean('is_available', true);

        Topic::create($data);

        return back()->with('success', 'Topik katalog ditambahkan.');
    }

    public function update(Request $request, Topic $topic)
    {
        $data = $this->validated($request);
        $data['is_available'] = $request->boolean('is_available');

        $topic->update($data);

        return back()->with('success', 'Topik diperbarui.');
    }

    public function destroy(Topic $topic)
    {
        abort_if($topic->team()->exists(), 422, 'Topik sedang dipakai tim, tidak dapat dihapus.');
        $topic->delete();

        return back()->with('success', 'Topik dihapus.');
    }

    /**
     * Review keputusan topik tim (approve/reject).
     */
    public function review(Request $request, Team $team)
    {
        $data = $request->validate([
            'topic_status' => ['required', Rule::in(['approved', 'rejected', 'pending'])],
            'topic_review_note' => ['nullable', 'string'],
        ]);

        $team->update($data);

        return back()->with('success', 'Status topik tim diperbarui.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'partner_id' => ['nullable', 'exists:partners,id'],
            'title' => ['required', 'string', 'max:255'],
            'general_features' => ['nullable', 'string'],
            'ai_features' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);
    }
}
