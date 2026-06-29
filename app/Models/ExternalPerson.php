<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExternalPerson extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'external_number',
        'name',
        'email',
        'phone',
        'institution_name',
        'institution_type',
        'position_title',
        'profession',
        'identity_number',
        'address',
        'status',
        'notes',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
