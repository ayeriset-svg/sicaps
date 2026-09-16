<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = [
        'academic_year_id', 'order_index', 'week_label', 'code', 'type',
        'assessment_stage', 'is_individual', 'requires_submission', 'is_open', 'opens_at', 'closes_at',
        'attendance_week', 'attendance_session', 'counts_as_attendance',
        'ai_policy_level', 'title',
        'objectives', 'tools_materials', 'ai_rules', 'references', 'description', 'tasks',
        'fields_json',
    ];

    public function aiLevel(): array
    {
        return config('capstone.ai_levels.' . ($this->ai_policy_level ?: 1), config('capstone.ai_levels.1'));
    }

    /** Field materi modul (rich HTML) mengikuti template dokumen. */
    public const MATERIAL_FIELDS = [
        'objectives' => 'Tujuan',
        'tools_materials' => 'Alat dan Bahan',
        'references' => 'Referensi',
        'description' => 'Deskripsi / Materi Teori',
        'tasks' => 'Tugas / Pertanyaan',
    ];

    protected $casts = [
        'fields_json' => 'array',
        'is_individual' => 'boolean',
        'requires_submission' => 'boolean',
        'is_open' => 'boolean',
        'counts_as_attendance' => 'boolean',
        'opens_at' => 'datetime',
        'closes_at' => 'datetime',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function logbooks(): HasMany
    {
        return $this->hasMany(ModuleLogbook::class);
    }

    public function subClos(): BelongsToMany
    {
        return $this->belongsToMany(SubClo::class, 'module_sub_clo');
    }

    public function isLogbook(): bool
    {
        return in_array($this->type, ['module', 'other'], true);
    }

    /** Apakah modul ini memerlukan pengerjaan (logbook) mahasiswa. */
    public function requiresSubmission(): bool
    {
        return $this->isLogbook() && (bool) $this->requires_submission;
    }

    /** Tugas/assignment yang dikerjakan per mahasiswa (individu). */
    public function isIndividual(): bool
    {
        return (bool) $this->is_individual && $this->isLogbook();
    }

    /** Label ringkas jenis pengerjaan. */
    public function workLabel(): string
    {
        return $this->isIndividual() ? 'Tugas Individu' : 'Logbook Tim';
    }

    /**
     * Status jadwal: 'closed' (ditutup manual), 'scheduled' (belum mulai),
     * 'open' (sedang berlangsung), 'ended' (deadline lewat), 'none' (tanpa jadwal).
     * Assessment murni memakai jendela tanggal (tanpa gate is_open).
     */
    public function scheduleState(): string
    {
        $now = now();

        if ($this->type === 'assessment') {
            if ($this->opens_at && $now->lt($this->opens_at)) {
                return 'scheduled';
            }
            if ($this->closes_at && $now->gt($this->closes_at)) {
                return 'ended';
            }

            return ($this->opens_at || $this->closes_at) ? 'open' : 'none';
        }

        if (! $this->is_open) {
            return 'closed';
        }
        if ($this->opens_at && $now->lt($this->opens_at)) {
            return 'scheduled';
        }
        if ($this->closes_at && $now->gt($this->closes_at)) {
            return 'ended';
        }

        return 'open';
    }

    /** Boleh menerima submission mahasiswa saat ini (logbook/tugas dibuka & dalam jendela waktu). */
    public function acceptsSubmission(): bool
    {
        return $this->requiresSubmission() && $this->scheduleState() === 'open';
    }

    /** Sisa hari menuju deadline (bulat ke atas); null bila tak ada closes_at. */
    public function daysToDeadline(): ?int
    {
        if (! $this->closes_at) {
            return null;
        }

        return (int) ceil(now()->diffInHours($this->closes_at, false) / 24);
    }

    public function fields(): array
    {
        return $this->fields_json ?: config('capstone.default_logbook_fields');
    }
}
