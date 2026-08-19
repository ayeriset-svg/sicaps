<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentStage extends Model
{
    protected $fillable = [
        'academic_year_id', 'code', 'name', 'weight_percentage',
        'peer_weight_percentage', 'peer_open', 'order_index',
    ];

    protected $casts = [
        'weight_percentage' => 'decimal:2',
        'peer_weight_percentage' => 'decimal:2',
        'peer_open' => 'boolean',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(AssessmentCriterion::class, 'stage_id')->orderBy('order_index');
    }

    public function peerEvaluations(): HasMany
    {
        return $this->hasMany(PeerEvaluation::class, 'stage_id');
    }
}
