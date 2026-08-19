<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    protected $fillable = [
        'team_id', 'student_id', 'assigned_role',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /** Peran bersifat teks bebas (boleh > 1, dipisah koma). */
    public function getRoleLabelAttribute(): string
    {
        return $this->assigned_role;
    }
}
