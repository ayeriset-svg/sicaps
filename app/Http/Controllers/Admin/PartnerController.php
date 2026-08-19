<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PartnerController extends Controller
{
    public function index()
    {
        $ay = AcademicYear::active();
        $partners = Partner::withCount('topics')->orderBy('name')->get();

        return view('admin.partners.index', compact('partners', 'ay'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['academic_year_id'] = optional(AcademicYear::active())->id;

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('partners/logos', 'local');
        }

        Partner::create($data);

        return back()->with('success', 'Mitra ditambahkan.');
    }

    public function update(Request $request, Partner $partner)
    {
        $data = $this->validated($request);

        if ($request->hasFile('logo')) {
            if ($partner->logo) {
                Storage::disk('local')->delete($partner->logo);
            }
            $data['logo'] = $request->file('logo')->store('partners/logos', 'local');
        }

        $partner->update($data);

        return back()->with('success', 'Mitra diperbarui.');
    }

    public function destroy(Partner $partner)
    {
        if ($partner->logo) {
            Storage::disk('local')->delete($partner->logo);
        }
        $partner->delete();

        return back()->with('success', 'Mitra dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(config('capstone.partner_types')))],
            'address' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);
    }
}
