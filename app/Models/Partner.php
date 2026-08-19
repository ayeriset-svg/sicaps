<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    protected $fillable = [
        'academic_year_id', 'name', 'type', 'logo', 'address', 'contact_person',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return config('capstone.partner_types.' . $this->type, $this->type);
    }
}
