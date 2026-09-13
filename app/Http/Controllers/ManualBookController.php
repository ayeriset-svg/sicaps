<?php

namespace App\Http\Controllers;

use App\Models\ManualBook;
use App\Services\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        $book = ManualBook::create($data);
        $this->handleFile($request, $book);

        return redirect()->route('admin.manual-books.index')->with('success', 'Manual book ditambahkan.');
    }

    public function update(Request $request, ManualBook $manualBook)
    {
        $manualBook->update($this->validated($request));
        $this->handleFile($request, $manualBook);

        return redirect()->route('admin.manual-books.index')->with('success', 'Manual book diperbarui.');
    }

    public function destroy(ManualBook $manualBook)
    {
        if ($manualBook->file_path) {
            Storage::disk('local')->delete($manualBook->file_path);
        }
        $manualBook->delete();

        return back()->with('success', 'Manual book dihapus.');
    }

    private function validated(Request $request): array
    {
        $mimes = config('capstone.manual_file.mimes', ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'zip']);
        $maxKb = (int) config('capstone.manual_file.max_kb', 20480);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'order_index' => ['nullable', 'integer', 'min:0'],
            'file' => ['nullable', 'file', 'mimes:' . implode(',', $mimes), "max:{$maxKb}"],
        ]);

        $data['content'] = $this->sanitizer->clean($data['content'] ?? '');
        $data['order_index'] = $data['order_index'] ?? 0;
        $data['is_published'] = $request->boolean('is_published');
        unset($data['file']); // berkas ditangani terpisah

        return $data;
    }

    /** Simpan/ganti/hapus berkas lampiran manual book. */
    private function handleFile(Request $request, ManualBook $book): void
    {
        // Hapus berkas bila diminta.
        if ($request->boolean('remove_file') && $book->file_path) {
            Storage::disk('local')->delete($book->file_path);
            $book->update(['file_path' => null, 'file_name' => null]);
        }

        if ($request->hasFile('file')) {
            if ($book->file_path) {
                Storage::disk('local')->delete($book->file_path);
            }
            $file = $request->file('file');
            $safe = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $name = $safe . '-' . now()->format('YmdHis') . '.' . $file->getClientOriginalExtension();
            $book->update([
                'file_path' => $file->storeAs('manual-books', $name, 'local'),
                'file_name' => $file->getClientOriginalName(),
            ]);
        }
    }
}
