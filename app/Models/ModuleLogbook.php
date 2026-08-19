<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModuleLogbook extends Model
{
    protected $fillable = [
        'team_id', 'module_id', 'user_id', 'payload_json', 'status_approval',
        'feedback', 'updated_by', 'submitted_at', 'reviewed_at', 'revision_count',
        'ai_percentage', 'ai_text_percentage', 'ai_image_percentage', 'ai_detail_json', 'ai_checked_at',
        'proofread_score', 'proofread_json', 'proofread_checked_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'ai_checked_at' => 'datetime',
        'ai_detail_json' => 'array',
        'ai_percentage' => 'decimal:2',
        'ai_text_percentage' => 'decimal:2',
        'ai_image_percentage' => 'decimal:2',
        'proofread_json' => 'array',
        'proofread_checked_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Pemilik submission untuk tugas individu (NULL = logbook tim). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ModuleLogbookVersion::class)->orderByDesc('version_number');
    }
}
