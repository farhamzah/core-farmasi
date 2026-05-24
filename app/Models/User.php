<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Role;
use App\Models\UserActivityLog;
use App\Models\UserAppAccess;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'identity_type',
        'identity_number',
        'password',
        'active',
        'api_token',
        'must_change_password',
        'password_changed_at',
        'last_password_reset_at',
        'password_reset_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
            'last_password_reset_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function lecturer(): HasOne
    {
        return $this->hasOne(Lecturer::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function appAccesses(): HasMany
    {
        return $this->hasMany(UserAppAccess::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(UserActivityLog::class);
    }

    public function passwordResetBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'password_reset_by');
    }

    public function hasRole(string $role): bool
    {
        return $this->roles->pluck('name')->contains($role);
    }

    public function hasIdentity(): bool
    {
        return filled($this->username) || filled($this->identity_number);
    }

    public function getDisplayIdentityAttribute(): ?string
    {
        return $this->username ?: $this->identity_number ?: $this->email;
    }

    public function markPasswordChanged(): void
    {
        $this->forceFill([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();
    }

    public function markMustChangePassword(): void
    {
        $this->forceFill([
            'must_change_password' => true,
        ])->save();
    }

    public function clearMustChangePassword(): void
    {
        $this->forceFill([
            'must_change_password' => false,
        ])->save();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->active && $this->roles()
            ->whereIn('name', ['super-admin', 'admin-core'])
            ->where('active', true)
            ->exists();
    }

    public function generateApiToken(): string
    {
        $token = bin2hex(random_bytes(40));

        $this->api_token = hash('sha256', $token);
        $this->save();

        return $token;
    }

    public static function verifyApiToken(string $token): ?self
    {
        $hashedToken = hash('sha256', $token);

        return self::where('api_token', $hashedToken)
            ->where('active', true)
            ->first();
    }
}
