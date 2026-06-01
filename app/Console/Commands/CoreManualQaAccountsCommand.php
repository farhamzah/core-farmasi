<?php

namespace App\Console\Commands;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserAppAccess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CoreManualQaAccountsCommand extends Command
{
    protected $signature = 'core:manual-qa-accounts
        {--apply : Write local QA accounts to the database}
        {--reset-admin-password : Reset the local QA admin password}
        {--create-users : Create or update non-admin QA users and profiles}
        {--assign-app-access : Assign app access for QA users when app roles already exist}
        {--show-credentials : Print local QA credentials for manual testing}
        {--force-env : Allow apply outside local/testing with a warning}';

    protected $description = 'Prepare local Core manual QA accounts for admin, profile, role, and app access testing.';

    private const ADMIN = [
        'name' => 'Core Farmasi Admin',
        'email' => 'admin@core-farmasi.local',
        'username' => 'admin',
        'password' => 'AdminCore!2026',
    ];

    private const USERS = [
        'student' => [
            'name' => 'Mahasiswa QA Core',
            'email' => 'mahasiswa.qa@core-farmasi.local',
            'username' => '20260001',
            'identity_type' => 'student',
            'identity_number' => '20260001',
            'profile_key' => 'student_number',
            'profile_number' => '20260001',
            'password' => 'Mahasiswa QA Core',
        ],
        'lecturer' => [
            'name' => 'Dosen QA Core',
            'email' => 'dosen.qa@core-farmasi.local',
            'username' => '0012345678',
            'identity_type' => 'lecturer',
            'identity_number' => '0012345678',
            'profile_key' => 'lecturer_number',
            'profile_number' => '0012345678',
            'password' => 'Dosen QA Core',
        ],
        'employee' => [
            'name' => 'Tendik QA Core',
            'email' => 'tendik.qa@core-farmasi.local',
            'username' => 'TENDIK001',
            'identity_type' => 'employee',
            'identity_number' => 'TENDIK001',
            'profile_key' => 'employee_number',
            'profile_number' => 'TENDIK001',
            'password' => 'Tendik QA Core',
        ],
    ];

    private const APP_ACCESS = [
        'student' => [
            ['app_code' => 'tu-farmasi', 'role_slug' => 'mahasiswa'],
            ['app_code' => 'ta-farmasi', 'role_slug' => 'mahasiswa'],
            ['app_code' => 'lab-farmasi', 'role_slug' => 'mahasiswa'],
        ],
        'lecturer' => [
            ['app_code' => 'tu-farmasi', 'role_slug' => 'dosen'],
            ['app_code' => 'ta-farmasi', 'role_slug' => 'dosen-pembimbing'],
            ['app_code' => 'lab-farmasi', 'role_slug' => 'dosen'],
        ],
        'employee' => [
            ['app_code' => 'tu-farmasi', 'role_slug' => 'staf-tu'],
            ['app_code' => 'lab-farmasi', 'role_slug' => 'laboran'],
        ],
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->info('Core Manual QA Accounts');
        $this->line('Mode: '.($apply ? 'apply' : 'dry-run'));
        $this->line('Environment: '.app()->environment());

        if ($apply && ! app()->environment(['local', 'testing']) && ! $this->option('force-env')) {
            $this->error('Refusing to apply QA accounts outside local/testing. Re-run with --force-env only after explicit approval.');

            return self::FAILURE;
        }

        if ($apply && $this->option('force-env') && ! app()->environment(['local', 'testing'])) {
            $this->warn('Force environment override is active. This command is intended for local manual QA only.');
        }

        $this->showPlan();

        if (! $apply) {
            $this->line('Dry-run only. No database writes were performed.');

            return self::SUCCESS;
        }

        $admin = $this->ensureAdmin();

        $qaUsers = [];

        if ($this->option('create-users')) {
            $qaUsers = $this->ensureQaUsers();
        } else {
            $this->warn('Skipping QA non-admin users. Use --create-users to create/update them.');
        }

        if ($this->option('assign-app-access')) {
            $this->assignAppAccess($qaUsers);
        } else {
            $this->warn('Skipping app access assignment. Use --assign-app-access to assign QA app access.');
        }

        $this->newLine();
        $this->info('QA accounts are ready.');
        $this->table(
            ['Type', 'User ID', 'Username', 'Email', 'Profile', 'Admin'],
            [
                ['admin', $admin->id, $admin->username, $admin->email, '-', 'yes'],
                ...collect($qaUsers)->map(fn (User $user, string $type): array => [
                    $type,
                    $user->id,
                    $user->username,
                    $user->email,
                    $this->profileNumber($user, $type),
                    'no',
                ])->values()->all(),
            ],
        );

        if ($this->option('show-credentials')) {
            $this->showCredentials();
        }

        return self::SUCCESS;
    }

    private function showPlan(): void
    {
        $admin = User::query()->where('email', self::ADMIN['email'])->first();

        $this->newLine();
        $this->line('Admin candidate: '.($admin ? "existing user #{$admin->id}" : 'will be created'));
        $this->line('Admin password reset: '.($this->option('reset-admin-password') ? 'planned' : 'not planned'));

        $this->table(
            ['Type', 'Username', 'Email', 'Profile Identifier', 'Password Policy'],
            collect(self::USERS)->map(fn (array $data, string $type): array => [
                $type,
                $data['username'],
                $data['email'],
                $data['profile_number'],
                'name-based temporary, hashed, must_change_password=true',
            ])->values()->all(),
        );

        $this->line('Planned app access is only assigned with --assign-app-access and only if app/role catalog rows already exist.');
        $this->line('Admin URL: http://127.0.0.1:8000/admin/login');
        $this->line('Profile URL: http://127.0.0.1:8000/profile');
        $this->line('Change Password URL: http://127.0.0.1:8000/profile/change-password');
    }

    private function ensureAdmin(): User
    {
        $admin = User::query()->firstOrNew(['email' => self::ADMIN['email']]);

        $admin->forceFill([
            'name' => self::ADMIN['name'],
            'username' => self::ADMIN['username'],
            'identity_type' => 'admin',
            'identity_number' => self::ADMIN['username'],
            'active' => true,
        ]);

        if (! $admin->exists || $this->option('reset-admin-password')) {
            $admin->password = Hash::make(self::ADMIN['password']);
            $admin->must_change_password = false;
            $admin->password_changed_at = now();
        }

        $admin->save();

        $role = Role::query()->firstOrCreate(
            ['name' => 'super-admin'],
            ['label' => 'Super Admin', 'active' => true],
        );

        $admin->roles()->syncWithoutDetaching([$role->id]);

        return $admin;
    }

    /**
     * @return array<string, User>
     */
    private function ensureQaUsers(): array
    {
        $department = $this->department();
        $studyProgram = $this->studyProgram($department);
        $users = [];

        foreach (self::USERS as $type => $data) {
            $user = User::query()->firstOrNew(['email' => $data['email']]);
            $user->forceFill([
                'name' => $data['name'],
                'username' => $data['username'],
                'identity_type' => $data['identity_type'],
                'identity_number' => $data['identity_number'],
                'password' => Hash::make($data['password']),
                'active' => true,
                'must_change_password' => true,
                'password_changed_at' => null,
            ])->save();

            $this->ensureProfile($type, $user, $data, $department, $studyProgram);

            $users[$type] = $user;
        }

        return $users;
    }

    private function ensureProfile(string $type, User $user, array $data, Department $department, StudyProgram $studyProgram): void
    {
        match ($type) {
            'student' => Student::query()->updateOrCreate(
                ['student_number' => $data['profile_number']],
                [
                    'user_id' => $user->id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'study_program_id' => $studyProgram->id,
                    'status' => 'active',
                    'active' => true,
                    'phone' => null,
                    'address' => null,
                ],
            ),
            'lecturer' => Lecturer::query()->updateOrCreate(
                ['lecturer_number' => $data['profile_number']],
                [
                    'user_id' => $user->id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'department_id' => $department->id,
                    'study_program_id' => $studyProgram->id,
                    'phone' => null,
                    'address' => null,
                    'active' => true,
                ],
            ),
            'employee' => Employee::query()->updateOrCreate(
                ['employee_number' => $data['profile_number']],
                [
                    'user_id' => $user->id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'staff_type' => 'tendik',
                    'department_id' => $department->id,
                    'study_program_id' => $studyProgram->id,
                    'position_title' => 'QA Tendik',
                    'phone' => null,
                    'address' => null,
                    'status' => 'active',
                ],
            ),
        };
    }

    /**
     * @param  array<string, User>  $users
     */
    private function assignAppAccess(array $users): void
    {
        foreach (self::APP_ACCESS as $type => $assignments) {
            $user = $users[$type] ?? User::query()->where('email', self::USERS[$type]['email'])->first();

            if (! $user) {
                $this->warn("Skipping {$type} app access because QA user is missing.");

                continue;
            }

            foreach ($assignments as $assignment) {
                if (! $this->appRoleExists($assignment['app_code'], $assignment['role_slug'])) {
                    $this->warn("Skipping {$assignment['app_code']}:{$assignment['role_slug']} because app role is missing.");

                    continue;
                }

                UserAppAccess::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'app_code' => $assignment['app_code'],
                        'role_slug' => $assignment['role_slug'],
                    ],
                    [
                        'permissions' => [],
                        'is_active' => true,
                        'activated_at' => now(),
                        'deactivated_at' => null,
                    ],
                );
            }
        }
    }

    private function appRoleExists(string $appCode, string $roleSlug): bool
    {
        return CoreApplication::query()->where('app_code', $appCode)->where('is_active', true)->exists()
            && CoreApplicationRole::query()
                ->where('app_code', $appCode)
                ->where('role_slug', $roleSlug)
                ->where('is_active', true)
                ->exists();
    }

    private function department(): Department
    {
        return Department::query()
            ->where('code', 'FF')
            ->orWhere('name', 'Fakultas Farmasi')
            ->firstOrFail();
    }

    private function studyProgram(Department $department): StudyProgram
    {
        return StudyProgram::query()
            ->where('code', 'S1-FARMASI')
            ->orWhere('name', 'S1 Farmasi')
            ->firstOr(function () use ($department): StudyProgram {
                return StudyProgram::query()->where('department_id', $department->id)->firstOrFail();
            });
    }

    private function profileNumber(User $user, string $type): string
    {
        return match ($type) {
            'student' => (string) $user->student?->student_number,
            'lecturer' => (string) $user->lecturer?->lecturer_number,
            'employee' => (string) $user->employee?->employee_number,
            default => '-',
        };
    }

    private function showCredentials(): void
    {
        $this->newLine();
        $this->warn('Local QA credentials. Do not commit these values to docs or reports.');
        $this->table(
            ['Account', 'Login', 'Password', 'Next Step'],
            [
                ['Admin', self::ADMIN['email'].' / '.self::ADMIN['username'], self::ADMIN['password'], 'Admin login'],
                ['Mahasiswa', self::USERS['student']['username'], self::USERS['student']['password'], 'Must change password'],
                ['Dosen', self::USERS['lecturer']['username'], self::USERS['lecturer']['password'], 'Must change password'],
                ['Tendik', self::USERS['employee']['username'], self::USERS['employee']['password'], 'Must change password'],
            ],
        );
    }
}
