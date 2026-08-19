<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = [
        'academic_year_id', 'order_index', 'week_label', 'code', 'type',
        'assessment_stage', 'is_individual', 'is_open', 'attendance_week', 'attendance_session',
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
        'ai_rules' => 'Aturan Penggunaan AI',
        'references' => 'Referensi',
        'description' => 'Deskripsi / Materi Teori',
        'tasks' => 'Tugas / Pertanyaan',
    ];

    protected $casts = [
        'fields_json' => 'array',
        'is_individual' => 'boolean',
        'is_open' => 'boolean',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function logbooks(): HasMany
    {
        return $this->hasMany(ModuleLogbook::class);
    }

    public function isLogbook(): bool
    {
        return in_array($this->type, ['module', 'other'], true);
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

    public function fields(): array
    {
        return $this->fields_json ?: config('capstone.default_logbook_fields');
    }
}
