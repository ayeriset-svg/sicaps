<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Topic extends Model
{
    protected $fillable = [
        'academic_year_id', 'partner_id', 'partner_name', 'title', 'general_features', 'ai_features',
        'description', 'origin', 'is_available', 'created_by',
    ];

    /** Nama mitra efektif: relasi master bila ada, else teks bebas. */
    public function getPartnerLabelAttribute(): ?string
    {
        return $this->partner?->name ?: $this->partner_name;
    }

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function team(): HasOne
    {
        return $this->hasOne(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
