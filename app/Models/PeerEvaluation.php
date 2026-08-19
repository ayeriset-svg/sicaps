<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeerEvaluation extends Model
{
    protected $fillable = [
        'stage_id', 'evaluator_id', 'evaluatee_id', 'team_id',
        'score_communication', 'score_contribution', 'score_responsibility',
        'score_attendance', 'final_peer_score', 'feedback',
    ];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(AssessmentStage::class, 'stage_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function evaluatee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluatee_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
