<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoreApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'app_code',
        'name',
        'description',
        'base_url',
        'admin_url',
        'icon',
        'color',
        'is_active',
        'is_public_visible',
        'requires_login',
        'is_sensitive',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_public_visible' => 'boolean',
        'requires_login' => 'boolean',
        'is_sensitive' => 'boolean',
        'sort_order' => 'integer',
        'deleted_at' => 'datetime',
    ];

    public function roles(): HasMany
    {
        return $this->hasMany(CoreApplicationRole::class);
    }

    public function userAppAccesses(): HasMany
    {
        return $this->hasMany(UserAppAccess::class, 'app_code', 'app_code');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
