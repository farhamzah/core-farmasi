<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAppAccess extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'app_code',
        'role_slug',
        'permissions',
        'is_active',
        'activated_at',
        'deactivated_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(CoreApplication::class, 'app_code', 'app_code');
    }

    public function getApplicationRoleAttribute(): ?CoreApplicationRole
    {
        if (blank($this->app_code) || blank($this->role_slug)) {
            return null;
        }

        return CoreApplicationRole::query()
            ->where('app_code', $this->app_code)
            ->where('role_slug', $this->role_slug)
            ->first();
    }

    public function getApplicationRoleNameAttribute(): ?string
    {
        return $this->applicationRole?->role_name;
    }
}
