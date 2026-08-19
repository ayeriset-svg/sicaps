<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    protected $fillable = ['year', 'semester', 'is_active', 'is_archived'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_archived' => 'boolean',
    ];

    public static function active(): ?self
    {
        return static::where('is_active', true)->first();
    }

    public function getLabelAttribute(): string
    {
        return $this->year . ' - ' . ucfirst($this->semester);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('order_index');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(AssessmentStage::class)->orderBy('order_index');
    }

    public function penaltyRules(): HasMany
    {
        return $this->hasMany(AttendancePenaltyRule::class)->orderBy('min_days');
    }

    public function partners(): HasMany
    {
        return $this->hasMany(Partner::class);
    }
}
