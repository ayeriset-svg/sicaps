<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clo extends Model
{
    protected $fillable = ['academic_year_id', 'plo_id', 'code', 'description', 'order_index'];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function plo(): BelongsTo
    {
        return $this->belongsTo(Plo::class);
    }

    public function subClos(): HasMany
    {
        return $this->hasMany(SubClo::class)->orderBy('order_index')->orderBy('id');
    }
}
