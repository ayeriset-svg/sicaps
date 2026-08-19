<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentScore extends Model
{
    protected $fillable = ['criterion_id', 'team_id', 'score', 'evaluator_id'];

    protected $casts = ['score' => 'decimal:2'];

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(AssessmentCriterion::class, 'criterion_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
