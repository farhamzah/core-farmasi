<?php

namespace App\Services\TuFarmasi;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TuPortalPasswordVerificationService
{
    public function verify(string $login, string $password, string $appCode = 'tu-farmasi'): array
    {
        $user = $this->findUserForLogin($login);

        if (! $user || ! $user->active || ! Hash::check($password, (string) $user->password)) {
            return $this->genericFailure();
        }

        if (! $this->userHasAppAccess($user, $appCode)) {
            return $this->genericFailure();
        }

        return [
            'authenticated' => true,
            'has_access' => true,
            ...$this->buildSafeIdentityPayload($user, $appCode),
        ];
    }

    public function findUserForLogin(string $login): ?User
    {
        $login = trim($login);

        if ($login === '') {
            return null;
        }

        return User::query()
            ->with(['student', 'lecturer', 'employee', 'appAccesses'])
            ->where(function ($query) use ($login): void {
                $query->where('email', $login)
                    ->orWhere('username', $login)
                    ->orWhere('identity_number', $login)
                    ->orWhereHas('student', fn ($query) => $query->where('student_number', $login))
                    ->orWhereHas('lecturer', fn ($query) => $query->where('lecturer_number', $login))
                    ->orWhereHas('employee', fn ($query) => $query->where('employee_number', $login));
            })
            ->first();
    }

    public function userHasAppAccess(User $user, string $appCode): bool
    {
        if (! CoreApplication::query()->where('app_code', $appCode)->where('is_active', true)->exists()) {
            return false;
        }

        return $this->activeRoleSlugs($user, $appCode) !== [];
    }

    public function buildSafeIdentityPayload(User $user, string $appCode): array
    {
        $user->loadMissing(['student', 'lecturer', 'employee']);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'identity_type' => $user->identity_type,
                'identity_number' => $user->identity_number,
                'profile_type' => $this->profileType($user),
                'student' => $user->student ? [
                    'id' => $user->student->id,
                    'nim' => $user->student->student_number,
                    'name' => $user->student->name,
                ] : null,
                'lecturer' => $user->lecturer ? [
                    'id' => $user->lecturer->id,
                    'nidn' => $user->lecturer->lecturer_number,
                    'name' => $user->lecturer->name,
                ] : null,
                'employee' => $user->employee ? [
                    'id' => $user->employee->id,
                    'employee_number' => $user->employee->employee_number,
                    'name' => $user->employee->name,
                    'staff_type' => $user->employee->staff_type,
                ] : null,
            ],
            'app_access' => [
                'app_code' => $appCode,
                'roles' => $this->activeRoleSlugs($user, $appCode),
            ],
        ];
    }

    public function genericFailure(): array
    {
        return [
            'authenticated' => false,
            'has_access' => false,
            'reason' => 'invalid_credentials_or_access',
        ];
    }

    protected function profileType(User $user): ?string
    {
        return match (true) {
            $user->student !== null => 'student',
            $user->lecturer !== null => 'lecturer',
            $user->employee !== null => 'employee',
            default => null,
        };
    }

    protected function activeRoleSlugs(User $user, string $appCode): array
    {
        return $user->appAccesses()
            ->where('app_code', $appCode)
            ->where('is_active', true)
            ->whereNotNull('role_slug')
            ->pluck('role_slug')
            ->filter(fn (?string $roleSlug): bool => filled($roleSlug) && CoreApplicationRole::query()
                ->where('app_code', $appCode)
                ->where('role_slug', $roleSlug)
                ->where('is_active', true)
                ->exists())
            ->unique()
            ->values()
            ->all();
    }
}
