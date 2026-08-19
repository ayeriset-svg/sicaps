<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleLogbookVersion extends Model
{
    protected $fillable = [
        'module_logbook_id', 'version_number', 'payload_json',
        'status_snapshot', 'feedback_snapshot', 'submitted_by',
    ];

    protected $casts = [
        'payload_json' => 'array',
    ];

    public function logbook(): BelongsTo
    {
        return $this->belongsTo(ModuleLogbook::class, 'module_logbook_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
