<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CoreProfileUserProvisioningService
{
    public function previewFor(Model $profile): array
    {
        $context = $this->profileContext($profile);

        if (! $context) {
            return [
                'action' => 'skip',
                'reason' => 'unsupported_profile',
                'context' => null,
                'user_id' => null,
                'initial_password' => null,
            ];
        }

        if (filled($profile->getAttribute('user_id'))) {
            return [
                'action' => 'skip',
                'reason' => 'already_linked',
                'context' => $context,
                'user_id' => $profile->getAttribute('user_id'),
                'initial_password' => null,
            ];
        }

        if (blank($context['identifier']) || blank($context['name']) || blank($context['email'])) {
            return [
                'action' => 'skip',
                'reason' => 'missing_identifier_name_or_email',
                'context' => $context,
                'user_id' => null,
                'initial_password' => null,
            ];
        }

        $matches = $this->matchingUsers($context)->get();

        if ($matches->count() > 1) {
            return [
                'action' => 'blocker',
                'reason' => 'multiple_matching_users',
                'context' => $context,
                'user_id' => null,
                'initial_password' => null,
            ];
        }

        if ($matches->count() === 1) {
            return [
                'action' => 'link',
                'reason' => 'matching_user_found',
                'context' => $context,
                'user_id' => $matches->first()->id,
                'initial_password' => null,
            ];
        }

        return [
            'action' => 'create',
            'reason' => 'no_matching_user',
            'context' => $context,
            'user_id' => null,
            'initial_password' => $this->generateInitialPassword($context['name'], $context['identifier']),
        ];
    }

    public function provisionFor(Model $profile): ?User
    {
        if (! config('core_identity.auto_user.enabled', true)) {
            return null;
        }

        $context = $this->profileContext($profile);

        if (! $context) {
            return null;
        }

        if (filled($profile->getAttribute('user_id'))) {
            $user = User::withTrashed()->find($profile->getAttribute('user_id'));

            if ($user?->trashed()) {
                $user->restore();
                $user->forceFill(['active' => $context['active']])->saveQuietly();
            }

            return $user && ! $user->trashed() ? $user : null;
        }

        if (blank($context['identifier']) || blank($context['name']) || blank($context['email'])) {
            return null;
        }

        $matches = $this->matchingUsers($context)->get();

        if ($matches->count() > 1) {
            return null;
        }

        if ($matches->count() === 1) {
            $user = $matches->first();

            if ($user->trashed()) {
                $user->restore();
                $user->forceFill([
                    'name' => $context['name'],
                    'email' => $context['email'],
                    'username' => $context['identifier'],
                    'identity_type' => $context['identity_type'],
                    'identity_number' => $context['identifier'],
                    'active' => $context['active'],
                ])->saveQuietly();
            }

            $profile->forceFill(['user_id' => $user->id])->saveQuietly();

            $this->log('profile.user_auto_linked', $user, $context, $profile);

            return $user;
        }

        $user = User::create([
            'name' => $context['name'],
            'email' => $context['email'],
            'username' => $context['identifier'],
            'identity_type' => $context['identity_type'],
            'identity_number' => $context['identifier'],
            'password' => Hash::make($this->generateInitialPassword($context['name'], $context['identifier'])),
            'active' => $context['active'],
            'must_change_password' => true,
            'password_changed_at' => null,
            'last_password_reset_at' => now(),
        ]);

        $profile->forceFill(['user_id' => $user->id])->saveQuietly();

        $this->log('profile.user_auto_created', $user, $context, $profile);

        return $user;
    }

    public function generateInitialPassword(string $name, string $identifier): string
    {
        $firstName = Str::of($name)
            ->squish()
            ->explode(' ')
            ->filter()
            ->first();

        $firstName = $firstName ?: 'User';
        $normalizedIdentifier = preg_replace('/[^A-Za-z0-9]+/', '', $identifier) ?: $identifier;
        $suffix = Str::of((string) $normalizedIdentifier)->substr(-4)->value();

        return Str::ucfirst(Str::lower((string) $firstName)).$suffix.'!';
    }

    protected function matchingUsers(array $context)
    {
        return User::withTrashed()
            ->where(function ($query) use ($context): void {
                $query->where('username', $context['identifier'])
                    ->orWhere('email', $context['email'])
                    ->orWhere(function ($query) use ($context): void {
                        $query
                            ->where('identity_type', $context['identity_type'])
                            ->where('identity_number', $context['identifier']);
                    });
            });
    }

    protected function profileContext(Model $profile): ?array
    {
        if ($profile instanceof Student) {
            return [
                'identity_type' => 'student',
                'identifier' => $profile->student_number,
                'name' => $profile->name,
                'email' => $profile->email,
                'active' => (bool) ($profile->active ?? $profile->status !== 'inactive'),
            ];
        }

        if ($profile instanceof Lecturer) {
            return [
                'identity_type' => 'lecturer',
                'identifier' => $profile->lecturer_number,
                'name' => $profile->name,
                'email' => $profile->email,
                'active' => (bool) ($profile->active ?? true),
            ];
        }

        if ($profile instanceof Employee) {
            return [
                'identity_type' => 'employee',
                'identifier' => $profile->employee_number,
                'name' => $profile->name,
                'email' => $profile->email,
                'active' => ($profile->status ?? 'active') !== 'inactive',
            ];
        }

        return null;
    }

    protected function log(string $action, User $user, array $context, Model $profile): void
    {
        UserActivityLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'meta' => [
                'source' => 'master_profile_auto_provisioning',
                'profile_type' => class_basename($profile),
                'profile_id' => $profile->getKey(),
                'identity_type' => $context['identity_type'],
                'identifier' => $context['identifier'],
                'password_policy' => 'first_name_identifier_suffix',
            ],
        ]);
    }
}
