<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TopicController extends Controller
{
    /**
     * Modul 1 - Topik: pilih dari katalog superadmin ATAU ajukan mandiri.
     */
    public function index()
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404);
        $team = Auth::user()->activeTeam($ay->id);
        abort_unless($team, 403, 'Anda harus tergabung dalam tim terlebih dahulu.');

        $team->load('topic.partner');

        // Katalog topik yang tersedia (belum diambil tim lain).
        $takenTopicIds = $ay->teams()->whereNotNull('topic_id')->where('id', '!=', $team->id)->pluck('topic_id');
        $catalog = Topic::with('partner')
            ->where('academic_year_id', $ay->id)
            ->where('origin', 'katalog')
            ->where('is_available', true)
            ->whereNotIn('id', $takenTopicIds)
            ->orderBy('title')->get();

        $partners = \App\Models\Partner::orderBy('name')->get();

        return view('topic.index', compact('team', 'catalog', 'partners', 'ay'));
    }

    /**
     * Pilih topik dari katalog.
     */
    public function choose(Request $request)
    {
        $ay = AcademicYear::active();
        $team = $this->leaderTeam($ay);

        $data = $request->validate(['topic_id' => ['required', 'exists:topics,id']]);
        $topic = Topic::where('academic_year_id', $ay->id)->findOrFail($data['topic_id']);

        $team->update([
            'topic_id' => $topic->id,
            'topic_status' => 'pending',
            'topic_review_note' => null,
            // Salin fitur master sebagai titik awal yang bisa disesuaikan tim.
            'custom_general_features' => $topic->general_features,
            'custom_ai_features' => $topic->ai_features,
        ]);

        return back()->with('success', 'Topik katalog dipilih & menunggu persetujuan koordinator. Anda dapat menyesuaikan daftar fitur.');
    }

    /**
     * Tim menyesuaikan (menambah/mengurangi) fitur yang ditawarkan, tanpa mengubah master topik.
     */
    public function updateFeatures(Request $request)
    {
        $ay = AcademicYear::active();
        $team = $this->leaderTeam($ay);
        abort_unless($team->topic_id, 422, 'Belum ada topik terpilih.');

        $data = $request->validate([
            'custom_general_features' => ['nullable', 'string'],
            'custom_ai_features' => ['nullable', 'string'],
        ]);

        $team->update($data);

        return back()->with('success', 'Daftar fitur tim diperbarui (master topik tidak berubah).');
    }

    /**
     * Ajukan topik mandiri (buat entri topik origin=mandiri, lalu link ke tim).
     */
    public function submitMandiri(Request $request)
    {
        $ay = AcademicYear::active();
        $team = $this->leaderTeam($ay);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            // Mitra topik mandiri = input teks bebas (bukan dropdown master).
            'partner_name' => ['nullable', 'string', 'max:255'],
            'general_features' => ['nullable', 'string'],
            'ai_features' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);
        $data['partner_id'] = null;

        // Perbarui topik mandiri lama milik tim bila ada, else buat baru.
        $existing = $team->topic && $team->topic->origin === 'mandiri' ? $team->topic : null;

        $topic = Topic::updateOrCreate(
            ['id' => $existing?->id],
            array_merge($data, [
                'academic_year_id' => $ay->id,
                'origin' => 'mandiri',
                'is_available' => false,
                'created_by' => Auth::id(),
            ])
        );

        $team->update(['topic_id' => $topic->id, 'topic_status' => 'pending', 'topic_review_note' => null]);

        return back()->with('success', 'Topik mandiri diajukan & menunggu persetujuan koordinator.');
    }

    private function leaderTeam(?AcademicYear $ay)
    {
        $team = Auth::user()->activeTeam($ay?->id);
        abort_unless($team, 403);
        abort_unless(Auth::id() === $team->leader_id, 403, 'Hanya ketua tim yang dapat mengelola topik.');

        return $team;
    }
}
