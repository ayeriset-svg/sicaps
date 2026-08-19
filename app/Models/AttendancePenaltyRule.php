<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendancePenaltyRule extends Model
{
    protected $fillable = [
        'academic_year_id', 'label', 'min_days', 'max_days', 'penalty_type', 'deduction_points',
    ];

    protected $casts = [
        'deduction_points' => 'decimal:2',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function matches(int $absentDays): bool
    {
        if ($absentDays < $this->min_days) {
            return false;
        }

        return $this->max_days === null || $absentDays <= $this->max_days;
    }
}
