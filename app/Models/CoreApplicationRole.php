<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoreApplicationRole extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'core_application_id',
        'app_code',
        'role_slug',
        'role_name',
        'description',
        'is_active',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'deleted_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(CoreApplication::class, 'core_application_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
