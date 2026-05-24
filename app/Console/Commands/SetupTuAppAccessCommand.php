<?php

namespace App\Console\Commands;

use App\Models\CoreApplicationRole;
use App\Models\User;
use App\Models\UserAppAccess;
use Illuminate\Console\Command;

class SetupTuAppAccessCommand extends Command
{
    protected $signature = 'core:setup-tu-app-access
        {--user-id= : Core user id to assign}
        {--role= : TU app role slug to assign}
        {--apply : Create or update one TU app access}
        {--auto-pick : Pick a safe demo/test/local active user candidate}';

    protected $description = 'Dry-run or assign one safe Core user test access for TU smoke test';

    public function handle(): int
    {
        $user = $this->resolveUser();

        if (! $user) {
            $this->error('No safe TU test user candidate found. Provide --user-id with an explicitly approved test user.');

            return self::FAILURE;
        }

        $profileType = $this->profileType($user);
        $role = (string) ($this->option('role') ?: $this->roleFor($profileType));

        if ($role === '') {
            $this->error('Unable to infer role for selected user. Provide --role explicitly after confirming the user type.');

            return self::FAILURE;
        }

        if (! $this->roleExists($role)) {
            $this->error("TU app role '{$role}' does not exist or is inactive.");

            return self::FAILURE;
        }

        $existing = UserAppAccess::query()
            ->where('user_id', $user->id)
            ->where('app_code', 'tu-farmasi')
            ->where('role_slug', $role)
            ->first();

        $status = $existing
            ? ($existing->is_active ? 'existing' : 'would-reactivate')
            : 'would-create';

        $this->table(
            ['Metric', 'Value'],
            [
                ['Mode', $this->option('apply') ? 'apply' : 'dry-run'],
                ['User ID', $user->id],
                ['Display name', $user->name],
                ['Username', $user->username ?: '-'],
                ['Profile type', $profileType],
                ['App code', 'tu-farmasi'],
                ['Role slug', $role],
                ['Access status', $status],
            ],
        );

        if (! $this->option('apply')) {
            $this->info('Dry-run only. Re-run with --apply to create/update this one TU app access.');

            return self::SUCCESS;
        }

        $access = UserAppAccess::updateOrCreate(
            [
                'user_id' => $user->id,
                'app_code' => 'tu-farmasi',
                'role_slug' => $role,
            ],
            [
                'is_active' => true,
                'activated_at' => now(),
                'deactivated_at' => null,
            ],
        );

        $this->info($access->wasRecentlyCreated ? 'TU app access created.' : 'TU app access updated.');

        return self::SUCCESS;
    }

    protected function resolveUser(): ?User
    {
        if ($this->option('user-id')) {
            return User::query()
                ->with(['student', 'lecturer', 'employee'])
                ->where('active', true)
                ->find((int) $this->option('user-id'));
        }

        if (! $this->option('auto-pick')) {
            return null;
        }

        return User::query()
            ->with(['student', 'lecturer', 'employee'])
            ->where('active', true)
            ->where(function ($query): void {
                $query
                    ->where('username', 'like', '%test%')
                    ->orWhere('username', 'like', '%demo%')
                    ->orWhere('username', 'like', '%dummy%')
                    ->orWhere('username', 'like', '%local%')
                    ->orWhere('email', 'like', '%test%')
                    ->orWhere('email', 'like', '%demo%')
                    ->orWhere('email', 'like', '%dummy%')
                    ->orWhere('name', 'like', '%test%')
                    ->orWhere('name', 'like', '%demo%')
                    ->orWhere('name', 'like', '%dummy%');
            })
            ->where(function ($query): void {
                $query
                    ->whereHas('student')
                    ->orWhereHas('lecturer')
                    ->orWhereHas('employee');
            })
            ->orderBy('id')
            ->first();
    }

    protected function profileType(User $user): string
    {
        return match (true) {
            $user->student !== null => 'mahasiswa',
            $user->lecturer !== null => 'dosen',
            $user->employee !== null => 'staf',
            default => 'unknown',
        };
    }

    protected function roleFor(string $profileType): string
    {
        return match ($profileType) {
            'mahasiswa' => 'mahasiswa',
            'dosen' => 'dosen',
            'staf' => 'staf-tu',
            default => '',
        };
    }

    protected function roleExists(string $role): bool
    {
        return CoreApplicationRole::query()
            ->where('app_code', 'tu-farmasi')
            ->where('role_slug', $role)
            ->where('is_active', true)
            ->exists();
    }
}
