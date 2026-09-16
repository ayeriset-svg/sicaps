<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plo extends Model
{
    protected $fillable = ['academic_year_id', 'code', 'description', 'order_index'];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function clos(): HasMany
    {
        return $this->hasMany(Clo::class)->orderBy('order_index')->orderBy('id');
    }
}
