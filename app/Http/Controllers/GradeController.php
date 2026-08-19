<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Services\GradeCalculationService;
use Illuminate\Support\Facades\Auth;

class GradeController extends Controller
{
    public function me(GradeCalculationService $service)
    {
        $ay = AcademicYear::active();
        abort_unless($ay, 404);
        $user = Auth::user();

        $grade = $service->recalculateStudent($user, $ay);

        return view('grade.me', compact('grade', 'ay', 'user'));
    }
}
