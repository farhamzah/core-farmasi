<?php

namespace App\Services\Karir;

use App\Models\CareerIdentitySubject;
use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class KarirIdentityVerificationService
{
    public function __construct(protected KarirEligibilityService $eligibility) {}

    public function findUser(string $identifier): ?User
    {
        return User::query()
            ->with(['student.studyProgram'])
            ->where(function ($query) use ($identifier): void {
                $query->where('email', $identifier)
                    ->orWhere('username', $identifier)
                    ->orWhere('identity_number', $identifier)
                    ->orWhereHas('student', fn ($student) => $student->where('student_number', $identifier));
            })->first();
    }

    public function credentialsAreValid(?User $user, string $password): bool
    {
        return $user !== null && Hash::check($password, (string) $user->password);
    }

    public function principal(User $user): ?array
    {
        $appCode = config('core_karir.app_code', 'karir-farmasi');
        $issuer = config('core_karir.issuer');

        if (! is_string($issuer) || trim($issuer) === '') {
            throw new \RuntimeException('Karir identity issuer is not configured.');
        }

        if (! $user->active || ! CoreApplication::query()->where('app_code', $appCode)->where('is_active', true)->exists()) {
            return null;
        }

        $roles = $user->appAccesses()
            ->where('app_code', $appCode)
            ->where('is_active', true)
            ->whereNotNull('role_slug')
            ->pluck('role_slug')
            ->filter(fn ($slug) => CoreApplicationRole::query()
                ->where('app_code', $appCode)->where('role_slug', $slug)->where('is_active', true)->exists())
            ->unique()->values();

        $source = $this->eligibility->source($user);

        if ($roles->isEmpty() || $source === null) {
            return null;
        }

        $subject = CareerIdentitySubject::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['subject' => (string) Str::uuid()],
        );

        return [
            'issuer' => $issuer,
            'subject' => $subject->subject,
            'core_user_id' => (string) $user->id,
            'display_name' => $user->name,
            'email' => $user->email,
            'active' => true,
            'app_code' => $appCode,
            'has_app_access' => true,
            'roles' => $roles->map(fn ($slug) => ['slug' => $slug, 'active' => true])->all(),
            'program_ids' => $this->eligibility->programIds($user),
            'career_scope' => ['farmasi'],
            'eligibility_source' => $source,
            'verified_at' => now()->toIso8601String(),
            'synthetic' => false,
        ];
    }
}
