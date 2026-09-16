<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SubClo extends Model
{
    protected $fillable = ['academic_year_id', 'clo_id', 'code', 'description', 'order_index'];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function clo(): BelongsTo
    {
        return $this->belongsTo(Clo::class);
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'module_sub_clo');
    }
}
