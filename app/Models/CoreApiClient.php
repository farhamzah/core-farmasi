<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoreApiClient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'core_application_id',
        'app_code',
        'name',
        'client_id',
        'secret_hash',
        'abilities',
        'allowed_ips',
        'last_used_at',
        'last_rotated_at',
        'revoked_at',
        'is_active',
        'notes',
        'created_by',
        'rotated_by',
        'revoked_by',
    ];

    protected $hidden = [
        'secret_hash',
    ];

    protected $casts = [
        'abilities' => 'array',
        'allowed_ips' => 'array',
        'last_used_at' => 'datetime',
        'last_rotated_at' => 'datetime',
        'revoked_at' => 'datetime',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(CoreApplication::class, 'core_application_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNull('revoked_at');
    }

    public function scopeRevoked(Builder $query): Builder
    {
        return $query->whereNotNull('revoked_at');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function canUseAbility(?string $ability): bool
    {
        if (blank($ability)) {
            return true;
        }

        $abilities = $this->abilities ?: [];

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    public function markUsed(): void
    {
        $this->forceFill(['last_used_at' => now()])->save();
    }
}
