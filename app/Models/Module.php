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
        'attendance_week', 'attendance_session', 'counts_as_attendance', 'attendance_finalized_at',
        'ai_policy_level', 'title',
        'objectives', 'tools_materials', 'references', 'description', 'tasks',
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
        'attendance_finalized_at' => 'datetime',
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

    /**
     * Apakah modul ini memerlukan pengerjaan (form isian) mahasiswa — ditentukan
     * OLEH FLAG, bukan tipe. Modul/tugas/assessment sama-sama bisa punya form
     * bila koordinator mencentang "Membutuhkan pengerjaan".
     */
    public function requiresSubmission(): bool
    {
        return (bool) $this->requires_submission;
    }

    /** Tugas/assignment yang dikerjakan per mahasiswa (individu). */
    public function isIndividual(): bool
    {
        return (bool) $this->is_individual;
    }

    /** Label ringkas jenis pengerjaan. */
    public function workLabel(): string
    {
        return $this->isIndividual() ? 'Tugas Individu' : 'Logbook Tim';
    }

    /**
     * Status jadwal (SERAGAM untuk semua tipe): 'closed' (belum dibuka),
     * 'scheduled' (belum mulai), 'open' (berlangsung), 'ended' (deadline lewat).
     */
    public function scheduleState(): string
    {
        $now = now();

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
