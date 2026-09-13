<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualBook extends Model
{
    protected $fillable = [
        'title', 'content', 'file_path', 'file_name', 'order_index', 'is_published', 'created_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Scope: hanya yang dipublikasikan, terurut. */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)->orderBy('order_index')->orderBy('id');
    }
}
