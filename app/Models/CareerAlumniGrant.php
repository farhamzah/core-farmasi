<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerAlumniGrant extends Model
{
    protected $fillable = [
        'user_id', 'career_scope', 'eligibility_source', 'is_active',
        'approved_by', 'approval_reference', 'approved_at',
    ];

    protected $casts = ['is_active' => 'boolean', 'approved_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
