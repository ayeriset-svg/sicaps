<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\LogbookController;
use App\Http\Controllers\PeerEvaluationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\AssessmentStageController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\GradeController as AdminGradeController;
use App\Http\Controllers\Admin\LogbookReviewController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Admin\PeerResultController;
use App\Http\Controllers\Admin\PenaltyRuleController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ScoreController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TeamController as AdminTeamController;
use App\Http\Controllers\Admin\TopicController as AdminTopicController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/* ---------------- Auth ---------------- */
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1'); // maks 10 percobaan/menit
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('auth')->group(function () {
    // Aktivasi akun: wajib ganti sandi saat login pertama (accessible tanpa ensure.password).
    Route::get('/ganti-password', [PasswordChangeController::class, 'show'])->name('password.change');
    Route::put('/ganti-password', [PasswordChangeController::class, 'update'])->name('password.change.update');

    // Semua akses lain memerlukan sandi sudah diganti.
    Route::middleware('ensure.password')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/files/{path}', [FileController::class, 'show'])->where('path', '.*')->name('file.show');

    // Profil akun
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Mode Observasi (impersonation)
    Route::post('/observe-stop', [ImpersonationController::class, 'stop'])->name('observe.stop');
    Route::post('/observe/{user}', [ImpersonationController::class, 'start'])->middleware('role:superadmin')->name('observe.start');

    /* ---------------- Mahasiswa ---------------- */
    Route::middleware('role:mahasiswa')->group(function () {
        Route::get('/team', [TeamController::class, 'index'])->name('team.index');
        Route::post('/team', [TeamController::class, 'store'])->name('team.store');
        Route::put('/team/{team}', [TeamController::class, 'update'])->name('team.update');
        Route::post('/team/{team}/members', [TeamController::class, 'addMember'])->name('team.members.add');
        Route::delete('/team/{team}/members/{member}', [TeamController::class, 'removeMember'])->name('team.members.remove');

        Route::get('/topic', [TopicController::class, 'index'])->name('topic.index');
        Route::post('/topic/choose', [TopicController::class, 'choose'])->name('topic.choose');
        Route::post('/topic/mandiri', [TopicController::class, 'submitMandiri'])->name('topic.mandiri');
        Route::put('/topic/features', [TopicController::class, 'updateFeatures'])->name('topic.features');

        Route::get('/logbook', [LogbookController::class, 'index'])->name('logbook.index');
        Route::get('/logbook/{module}', [LogbookController::class, 'show'])->name('logbook.show');
        Route::get('/logbook/{module}/print', [LogbookController::class, 'print'])->name('logbook.print');
        Route::put('/logbook/{module}', [LogbookController::class, 'update'])->name('logbook.update');

        Route::get('/peer', [PeerEvaluationController::class, 'index'])->name('peer.index');
        Route::post('/peer', [PeerEvaluationController::class, 'store'])->name('peer.store');

        Route::get('/nilai-saya', [GradeController::class, 'me'])->name('grade.me');
    });

    /* ---------------- Superadmin ---------------- */
    Route::middleware('role:superadmin')->prefix('admin')->name('admin.')->group(function () {
        // Tahun ajaran
        Route::get('/academic-years', [AcademicYearController::class, 'index'])->name('academic-years.index');
        Route::post('/academic-years', [AcademicYearController::class, 'store'])->name('academic-years.store');
        Route::post('/academic-years/{academicYear}/activate', [AcademicYearController::class, 'activate'])->name('academic-years.activate');
        Route::post('/academic-years/{academicYear}/archive', [AcademicYearController::class, 'archive'])->name('academic-years.archive');

        // Master user & mahasiswa
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/import', [UserController::class, 'import'])->name('users.import');

        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::post('/students/import', [StudentController::class, 'import'])->name('students.import');

        // Mitra & Topik
        Route::get('/partners', [PartnerController::class, 'index'])->name('partners.index');
        Route::post('/partners', [PartnerController::class, 'store'])->name('partners.store');
        Route::put('/partners/{partner}', [PartnerController::class, 'update'])->name('partners.update');
        Route::delete('/partners/{partner}', [PartnerController::class, 'destroy'])->name('partners.destroy');

        Route::get('/topics', [AdminTopicController::class, 'index'])->name('topics.index');
        Route::post('/topics', [AdminTopicController::class, 'store'])->name('topics.store');
        Route::put('/topics/{topic}', [AdminTopicController::class, 'update'])->name('topics.update');
        Route::delete('/topics/{topic}', [AdminTopicController::class, 'destroy'])->name('topics.destroy');
        Route::put('/topics/team/{team}/review', [AdminTopicController::class, 'review'])->name('topics.review');

        // Tim
        Route::get('/teams', [AdminTeamController::class, 'index'])->name('teams.index');
        Route::post('/teams/{team}/hki', [AdminTeamController::class, 'toggleHki'])->name('teams.hki');

        // Modul dinamis
        Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
        Route::get('/modules/create', [ModuleController::class, 'create'])->name('modules.create');
        Route::get('/modules/{module}/edit', [ModuleController::class, 'edit'])->name('modules.edit');
        Route::post('/modules', [ModuleController::class, 'store'])->name('modules.store');
        Route::put('/modules/{module}', [ModuleController::class, 'update'])->name('modules.update');
        Route::delete('/modules/{module}', [ModuleController::class, 'destroy'])->name('modules.destroy');

        // Review logbook + feedback
        Route::get('/logbook-review', [LogbookReviewController::class, 'index'])->name('logbook-review.index');
        Route::get('/logbook-review/{logbook}', [LogbookReviewController::class, 'show'])->name('logbook-review.show');
        Route::get('/logbook-review/{logbook}/print', [LogbookReviewController::class, 'print'])->name('logbook-review.print');
        Route::post('/logbook-review/{logbook}/check-ai', [LogbookReviewController::class, 'checkAi'])->name('logbook-review.check-ai');
        Route::post('/logbook-review/{logbook}/proofread', [LogbookReviewController::class, 'proofread'])->name('logbook-review.proofread');
        Route::put('/logbook-review/{logbook}', [LogbookReviewController::class, 'review'])->name('logbook-review.review');

        // Assessment stage (bobot, peer open, kriteria) + input nilai
        Route::get('/stages', [AssessmentStageController::class, 'index'])->name('stages.index');
        Route::put('/stages/{stage}', [AssessmentStageController::class, 'update'])->name('stages.update');
        Route::post('/stages/{stage}/toggle-peer', [AssessmentStageController::class, 'togglePeer'])->name('stages.toggle-peer');
        Route::post('/stages/{stage}/criteria', [AssessmentStageController::class, 'addCriterion'])->name('stages.criteria.add');
        Route::delete('/criteria/{criterion}', [AssessmentStageController::class, 'destroyCriterion'])->name('criteria.destroy');

        Route::get('/scores', [ScoreController::class, 'index'])->name('scores.index');
        Route::post('/scores', [ScoreController::class, 'store'])->name('scores.store');

        Route::get('/peer-result', [PeerResultController::class, 'index'])->name('peer-result.index');

        // Penalty & presensi
        Route::get('/penalty', [PenaltyRuleController::class, 'index'])->name('penalty.index');
        Route::post('/penalty', [PenaltyRuleController::class, 'store'])->name('penalty.store');
        Route::put('/penalty/{rule}', [PenaltyRuleController::class, 'update'])->name('penalty.update');
        Route::delete('/penalty/{rule}', [PenaltyRuleController::class, 'destroy'])->name('penalty.destroy');

        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');

        // Nilai & report
        Route::get('/grades', [AdminGradeController::class, 'index'])->name('grades.index');
        Route::post('/grades/recalculate', [AdminGradeController::class, 'recalculate'])->name('grades.recalculate');
        Route::put('/grades/{grade}/override', [AdminGradeController::class, 'override'])->name('grades.override');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });
    }); // ensure.password
}); // auth
