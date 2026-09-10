<?php

namespace App\Http\Controllers;

use App\Models\ManualBook;
use App\Services\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManualBookController extends Controller
{
    public function __construct(private HtmlSanitizer $sanitizer)
    {
    }

    /* ============ Tampilan untuk semua pengguna ============ */

    /** Daftar manual book yang dipublikasikan (mahasiswa & superadmin). */
    public function publicIndex()
    {
        $books = ManualBook::published()->get();

        return view('manual.index', compact('books'));
    }

    /** Panduan lengkap per role (step-by-step, siap disimpan sebagai PDF). */
    public function guide(string $role)
    {
        abort_unless(in_array($role, ['superadmin', 'mahasiswa'], true), 404);
        $guide = config("manual.$role");
        abort_unless($guide, 404);

        return view('manual.guide', ['role' => $role, 'guide' => $guide]);
    }

    /** Baca satu manual book. Draf (belum publish) hanya untuk superadmin. */
    public function show(ManualBook $manualBook)
    {
        abort_unless($manualBook->is_published || Auth::user()->isSuperadmin(), 404);

        return view('manual.show', ['book' => $manualBook]);
    }

    /* ============ Pengelolaan (superadmin) ============ */

    public function index()
    {
        $books = ManualBook::orderBy('order_index')->orderBy('id')->get();

        return view('admin.manual-books.index', compact('books'));
    }

    public function create()
    {
        return view('admin.manual-books.form', ['book' => null]);
    }

    public function edit(ManualBook $manualBook)
    {
        return view('admin.manual-books.form', ['book' => $manualBook]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = Auth::id();
        ManualBook::create($data);

        return redirect()->route('admin.manual-books.index')->with('success', 'Manual book ditambahkan.');
    }

    public function update(Request $request, ManualBook $manualBook)
    {
        $manualBook->update($this->validated($request));

        return redirect()->route('admin.manual-books.index')->with('success', 'Manual book diperbarui.');
    }

    public function destroy(ManualBook $manualBook)
    {
        $manualBook->delete();

        return back()->with('success', 'Manual book dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'order_index' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['content'] = $this->sanitizer->clean($data['content'] ?? '');
        $data['order_index'] = $data['order_index'] ?? 0;
        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }
}
