<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalGrade extends Model
{
    protected $fillable = [
        'student_id', 'academic_year_id', 'breakdown_json', 'raw_score',
        'absent_days', 'penalty_points', 'penalty_level', 'final_score',
        'grade_letter', 'override_score', 'override_note', 'calculated_at',
    ];

    protected $casts = [
        'breakdown_json' => 'array',
        'calculated_at' => 'datetime',
        'raw_score' => 'decimal:2',
        'penalty_points' => 'decimal:2',
        'final_score' => 'decimal:2',
        'override_score' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function getEffectiveScoreAttribute(): float
    {
        return (float) ($this->override_score ?? $this->final_score);
    }
}
