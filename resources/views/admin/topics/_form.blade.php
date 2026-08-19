<form method="POST" action="{{ $action }}" class="space-y-3">
    @csrf
    @if($method !== 'POST')@method($method)@endif
    <div><label class="block text-sm font-medium mb-1">Judul Topik</label><input name="title" value="{{ $topic->title ?? '' }}" required class="w-full rounded-lg border-slate-300 border px-3 py-2"></div>
    <div><label class="block text-sm font-medium mb-1">Mitra</label>
        <select name="partner_id" class="w-full rounded-lg border-slate-300 border px-3 py-2">
            <option value="">— Tanpa mitra —</option>
            @foreach($partners as $p)<option value="{{ $p->id }}" @selected(($topic->partner_id ?? null)==$p->id)>{{ $p->name }} ({{ $p->type_label }})</option>@endforeach
        </select>
    </div>
    <div><label class="block text-sm font-medium mb-1">Fitur Umum</label><textarea name="general_features" rows="2" class="w-full rounded-lg border-slate-300 border px-3 py-2">{{ $topic->general_features ?? '' }}</textarea></div>
    <div><label class="block text-sm font-medium mb-1">Fitur AI</label><textarea name="ai_features" rows="2" class="w-full rounded-lg border-slate-300 border px-3 py-2">{{ $topic->ai_features ?? '' }}</textarea></div>
    <div><label class="block text-sm font-medium mb-1">Deskripsi</label><textarea name="description" rows="2" class="w-full rounded-lg border-slate-300 border px-3 py-2">{{ $topic->description ?? '' }}</textarea></div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_available" value="1" @checked($topic->is_available ?? true)> Tersedia untuk dipilih tim</label>
    <button class="rounded-lg bg-brand text-white px-4 py-2 text-sm w-full hover:bg-brand-dark">{{ $topic ? 'Simpan Perubahan' : 'Tambah Topik' }}</button>
</form>
