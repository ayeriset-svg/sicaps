<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
