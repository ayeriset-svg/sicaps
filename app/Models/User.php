<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'identity_number', 'name', 'email', 'password', 'role',
        'angkatan', 'class_name', 'is_active', 'must_change_password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'must_change_password' => 'boolean',
    ];

    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isMahasiswa(): bool
    {
        return $this->role === 'mahasiswa';
    }

    /**
     * Batas aktivasi: akun yang masih wajib ganti sandi awal hanya dapat dipakai
     * selama N hari sejak dibuat / terakhir direset koordinator (updated_at).
     */
    public function activationDeadline(): ?\Illuminate\Support\Carbon
    {
        if (! $this->must_change_password) {
            return null;
        }
        $since = $this->updated_at ?? $this->created_at;

        return $since?->copy()->addDays((int) config('capstone.activation_days', 7));
    }

    public function activationExpired(): bool
    {
        $deadline = $this->activationDeadline();

        return $deadline !== null && now()->gt($deadline);
    }

    /** Pesan penolakan akses bila akun tidak dapat dipakai (null = boleh). */
    public function accessBlockedReason(): ?string
    {
        if (! $this->is_active) {
            return 'Akun Anda tidak aktif. Hubungi koordinator.';
        }
        if ($this->activationExpired()) {
            return 'Batas waktu aktivasi akun telah habis. Hubungi koordinator untuk mereset kata sandi Anda.';
        }

        return null;
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(TeamMember::class, 'student_id');
    }

    public function ledTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'leader_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    public function finalGrades(): HasMany
    {
        return $this->hasMany(FinalGrade::class, 'student_id');
    }

    /**
     * Tim mahasiswa pada tahun ajaran tertentu (via membership atau leadership).
     */
    public function activeTeam(?int $academicYearId = null): ?Team
    {
        $academicYearId ??= optional(AcademicYear::active())->id;
        if (! $academicYearId) {
            return null;
        }

        $membership = $this->memberships()
            ->whereHas('team', fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->with('team')
            ->first();

        if ($membership) {
            return $membership->team;
        }

        return $this->ledTeams()->where('academic_year_id', $academicYearId)->first();
    }
}
