<?php

namespace Tests\Feature;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CoreManualQaAccountsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_does_not_create_users(): void
    {
        $this->artisan('core:manual-qa-accounts')
            ->expectsOutputToContain('Mode: dry-run')
            ->expectsOutputToContain('Dry-run only. No database writes were performed.')
            ->assertExitCode(0);

        $this->assertSame(0, User::query()->count());
        $this->assertSame(0, Student::query()->count());
        $this->assertSame(0, Lecturer::query()->count());
        $this->assertSame(0, Employee::query()->count());
        $this->assertSame(0, UserAppAccess::query()->count());
    }

    public function test_apply_creates_qa_users_in_testing_environment(): void
    {
        $this->referenceData();

        $this->artisan('core:manual-qa-accounts', [
            '--apply' => true,
            '--reset-admin-password' => true,
            '--create-users' => true,
        ])
            ->expectsOutputToContain('Mode: apply')
            ->expectsOutputToContain('QA accounts are ready.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@core-farmasi.local',
            'username' => 'admin',
            'active' => true,
            'must_change_password' => false,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'mahasiswa.qa@core-farmasi.local',
            'username' => '20260001',
            'identity_type' => 'student',
            'identity_number' => '20260001',
            'active' => true,
            'must_change_password' => true,
        ]);
        $this->assertDatabaseHas('students', [
            'student_number' => '20260001',
            'name' => 'Mahasiswa QA Core',
        ]);
        $this->assertDatabaseHas('lecturers', [
            'lecturer_number' => '0012345678',
            'name' => 'Dosen QA Core',
        ]);
        $this->assertDatabaseHas('employees', [
            'employee_number' => 'TENDIK001',
            'staff_type' => 'tendik',
        ]);
    }

    public function test_apply_creates_hashed_passwords_and_does_not_store_plaintext(): void
    {
        $this->referenceData();

        $this->artisan('core:manual-qa-accounts', [
            '--apply' => true,
            '--reset-admin-password' => true,
            '--create-users' => true,
        ])->assertExitCode(0);

        $admin = User::query()->where('email', 'admin@core-farmasi.local')->firstOrFail();
        $student = User::query()->where('email', 'mahasiswa.qa@core-farmasi.local')->firstOrFail();
        $lecturer = User::query()->where('email', 'dosen.qa@core-farmasi.local')->firstOrFail();
        $employee = User::query()->where('email', 'tendik.qa@core-farmasi.local')->firstOrFail();

        $this->assertTrue(Hash::check('AdminCore!2026', $admin->password));
        $this->assertTrue(Hash::check('Mahasiswa QA Core', $student->password));
        $this->assertTrue(Hash::check('Dosen QA Core', $lecturer->password));
        $this->assertTrue(Hash::check('Tendik QA Core', $employee->password));
        $this->assertNotSame('AdminCore!2026', $admin->password);
        $this->assertNotSame('Mahasiswa QA Core', $student->password);
        $this->assertTrue($student->must_change_password);
        $this->assertTrue($lecturer->must_change_password);
        $this->assertTrue($employee->must_change_password);
        $this->assertNull($student->password_changed_at);
    }

    public function test_admin_role_only_for_admin_qa(): void
    {
        $this->referenceData();

        $this->artisan('core:manual-qa-accounts', [
            '--apply' => true,
            '--reset-admin-password' => true,
            '--create-users' => true,
        ])->assertExitCode(0);

        $admin = User::query()->where('email', 'admin@core-farmasi.local')->firstOrFail();
        $student = User::query()->where('email', 'mahasiswa.qa@core-farmasi.local')->firstOrFail();
        $lecturer = User::query()->where('email', 'dosen.qa@core-farmasi.local')->firstOrFail();
        $employee = User::query()->where('email', 'tendik.qa@core-farmasi.local')->firstOrFail();

        $this->assertTrue($admin->roles()->where('name', 'super-admin')->exists());
        $this->assertFalse($student->roles()->whereIn('name', ['super-admin', 'admin-core'])->exists());
        $this->assertFalse($lecturer->roles()->whereIn('name', ['super-admin', 'admin-core'])->exists());
        $this->assertFalse($employee->roles()->whereIn('name', ['super-admin', 'admin-core'])->exists());
    }

    public function test_app_access_assigned_only_if_flag_is_present(): void
    {
        $this->referenceData();
        $this->appAccessCatalog();

        $this->artisan('core:manual-qa-accounts', [
            '--apply' => true,
            '--create-users' => true,
        ])->assertExitCode(0);

        $this->assertSame(0, UserAppAccess::query()->count());

        $this->artisan('core:manual-qa-accounts', [
            '--apply' => true,
            '--create-users' => true,
            '--assign-app-access' => true,
        ])->assertExitCode(0);

        $student = User::query()->where('email', 'mahasiswa.qa@core-farmasi.local')->firstOrFail();
        $lecturer = User::query()->where('email', 'dosen.qa@core-farmasi.local')->firstOrFail();
        $employee = User::query()->where('email', 'tendik.qa@core-farmasi.local')->firstOrFail();

        $this->assertDatabaseHas('user_app_accesses', [
            'user_id' => $student->id,
            'app_code' => 'tu-farmasi',
            'role_slug' => 'mahasiswa',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('user_app_accesses', [
            'user_id' => $lecturer->id,
            'app_code' => 'ta-farmasi',
            'role_slug' => 'dosen-pembimbing',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('user_app_accesses', [
            'user_id' => $employee->id,
            'app_code' => 'lab-farmasi',
            'role_slug' => 'laboran',
            'is_active' => true,
        ]);
        $this->assertSame(8, UserAppAccess::query()->count());
    }

    public function test_command_does_not_expose_password_hash_or_secret(): void
    {
        $this->referenceData();

        $this->artisan('core:manual-qa-accounts', [
            '--apply' => true,
            '--reset-admin-password' => true,
            '--create-users' => true,
            '--show-credentials' => true,
        ])
            ->doesntExpectOutputToContain('$2y$')
            ->doesntExpectOutputToContain('password_hash')
            ->doesntExpectOutputToContain('client_secret')
            ->assertExitCode(0);
    }

    public function test_command_refuses_non_local_environment_without_force(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('core:manual-qa-accounts', ['--apply' => true])
            ->expectsOutputToContain('Refusing to apply QA accounts outside local/testing.')
            ->assertExitCode(1);

        $this->assertSame(0, User::query()->count());
    }

    private function referenceData(): void
    {
        Role::create(['name' => 'super-admin', 'label' => 'Super Admin', 'active' => true]);

        $department = Department::create([
            'code' => 'FF',
            'name' => 'Fakultas Farmasi',
            'active' => true,
        ]);

        StudyProgram::create([
            'department_id' => $department->id,
            'code' => 'S1-FARMASI',
            'name' => 'S1 Farmasi',
            'active' => true,
        ]);
    }

    private function appAccessCatalog(): void
    {
        foreach (['tu-farmasi', 'ta-farmasi', 'lab-farmasi'] as $appCode) {
            CoreApplication::create([
                'app_code' => $appCode,
                'name' => $appCode,
                'is_active' => true,
                'is_public_visible' => false,
                'requires_login' => true,
                'is_sensitive' => false,
            ]);
        }

        foreach ([
            ['tu-farmasi', 'mahasiswa'],
            ['tu-farmasi', 'dosen'],
            ['tu-farmasi', 'staf-tu'],
            ['ta-farmasi', 'mahasiswa'],
            ['ta-farmasi', 'dosen-pembimbing'],
            ['lab-farmasi', 'mahasiswa'],
            ['lab-farmasi', 'dosen'],
            ['lab-farmasi', 'laboran'],
        ] as [$appCode, $roleSlug]) {
            $application = CoreApplication::query()->where('app_code', $appCode)->firstOrFail();

            CoreApplicationRole::create([
                'core_application_id' => $application->id,
                'app_code' => $appCode,
                'role_slug' => $roleSlug,
                'role_name' => str($roleSlug)->headline()->toString(),
                'is_active' => true,
            ]);
        }
    }
}
