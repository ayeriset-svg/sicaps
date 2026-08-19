<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentCriterion extends Model
{
    protected $table = 'assessment_criteria';

    protected $fillable = ['stage_id', 'name', 'order_index'];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(AssessmentStage::class, 'stage_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(AssessmentScore::class, 'criterion_id');
    }
}
