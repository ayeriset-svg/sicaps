<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Team extends Model
{
    protected $fillable = [
        'academic_year_id', 'team_name', 'leader_id', 'case_type',
        'topic_id', 'topic_status', 'topic_review_note',
        'custom_general_features', 'custom_ai_features', 'hki_eligible',
    ];

    protected $casts = [
        'hki_eligible' => 'boolean',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function logbooks(): HasMany
    {
        return $this->hasMany(ModuleLogbook::class);
    }

    public function assessmentScores(): HasMany
    {
        return $this->hasMany(AssessmentScore::class);
    }

    /**
     * Susunan anggota terkunci untuk mahasiswa begitu Assessment pertama (A1)
     * tim ini mulai dinilai; setelahnya hanya koordinator yang dapat mengubah.
     */
    /**
     * Cek apakah mahasiswa sudah tergabung di tim mana pun pada tahun ajaran ini.
     */
    public static function studentHasTeam(int $studentId, int $academicYearId): bool
    {
        return TeamMember::where('student_id', $studentId)
            ->whereHas('team', fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->exists();
    }

    /**
     * Tambahkan mahasiswa ke tim ini secara aman: satu mahasiswa hanya boleh
     * berada di SATU tim per tahun ajaran. Baris mahasiswa & tim dikunci
     * (SELECT ... FOR UPDATE) agar dua permintaan bersamaan tidak sama-sama lolos.
     * Mengembalikan pesan error, atau null bila berhasil.
     */
    public function enrollStudent(int $studentId, string $role): ?string
    {
        return DB::transaction(function () use ($studentId, $role) {
            User::whereKey($studentId)->lockForUpdate()->first();
            static::whereKey($this->id)->lockForUpdate()->first();

            if (static::studentHasTeam($studentId, $this->academic_year_id)) {
                return 'Mahasiswa tersebut sudah tergabung di tim lain pada tahun ajaran ini.';
            }
            $max = config('capstone.team_max_members');
            if ($this->members()->count() >= $max) {
                return "Kapasitas tim maksimal {$max} orang.";
            }

            $this->members()->create(['student_id' => $studentId, 'assigned_role' => $role]);

            return null;
        });
    }

    /** Topik yang sudah disetujui koordinator tidak dapat diganti/diubah oleh tim. */
    public function isTopicLocked(): bool
    {
        return $this->topic_id && $this->topic_status === 'approved';
    }

    public function isMembershipLocked(): bool
    {
        $firstStage = AssessmentStage::where('academic_year_id', $this->academic_year_id)
            ->orderBy('order_index')->first();
        if (! $firstStage) {
            return false;
        }

        return AssessmentScore::where('team_id', $this->id)
            ->whereIn('criterion_id', $firstStage->criteria()->pluck('id'))
            ->exists();
    }

    public function studentUsers()
    {
        return $this->members()->with('student')->get()->pluck('student')->filter();
    }

    public function getCaseTypeLabelAttribute(): ?string
    {
        return $this->case_type ? config('capstone.case_types.' . $this->case_type) : null;
    }

    /** Kelas tim diambil dari kelas ketua. */
    public function getClassNameAttribute(): ?string
    {
        return $this->leader?->class_name;
    }

    /** Fitur efektif = kustom tim bila diisi, else fitur master topik. */
    public function getEffectiveGeneralFeaturesAttribute(): ?string
    {
        return $this->custom_general_features ?: $this->topic?->general_features;
    }

    public function getEffectiveAiFeaturesAttribute(): ?string
    {
        return $this->custom_ai_features ?: $this->topic?->ai_features;
    }
}
