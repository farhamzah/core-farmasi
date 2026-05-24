<?php

namespace Tests\Feature;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\User;
use App\Models\UserAppAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupTuAppAccessCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_pick_dry_run_does_not_create_access(): void
    {
        $user = $this->demoLecturerUser();
        $this->tuRole('dosen');

        $this->artisan('core:setup-tu-app-access', ['--auto-pick' => true])
            ->expectsOutputToContain('dry-run')
            ->expectsOutputToContain((string) $user->id)
            ->expectsOutputToContain('would-create')
            ->assertExitCode(0);

        $this->assertSame(0, UserAppAccess::query()->count());
    }

    public function test_auto_pick_apply_creates_one_tu_access(): void
    {
        $user = $this->demoLecturerUser();
        $this->tuRole('dosen');

        $this->artisan('core:setup-tu-app-access', ['--auto-pick' => true, '--apply' => true])
            ->expectsOutputToContain('TU app access created.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('user_app_accesses', [
            'user_id' => $user->id,
            'app_code' => 'tu-farmasi',
            'role_slug' => 'dosen',
            'is_active' => true,
        ]);
        $this->assertSame(1, UserAppAccess::query()->count());
    }

    public function test_command_refuses_without_safe_candidate(): void
    {
        $this->tuRole('dosen');

        $this->artisan('core:setup-tu-app-access', ['--auto-pick' => true])
            ->expectsOutputToContain('No safe TU test user candidate found.')
            ->assertExitCode(1);
    }

    public function test_command_refuses_missing_role(): void
    {
        $this->demoLecturerUser();

        $this->artisan('core:setup-tu-app-access', ['--auto-pick' => true])
            ->expectsOutputToContain("TU app role 'dosen' does not exist or is inactive.")
            ->assertExitCode(1);
    }

    private function demoLecturerUser(): User
    {
        $department = Department::create([
            'code' => 'DEMO-TU',
            'name' => 'Demo TU Department',
            'active' => true,
        ]);
        $user = User::factory()->create([
            'name' => 'Demo Dosen TU',
            'email' => 'demo.dosen.tu@example.test',
            'active' => true,
        ]);

        Lecturer::create([
            'user_id' => $user->id,
            'lecturer_number' => 'DEMO-TU-LECT',
            'name' => $user->name,
            'email' => $user->email,
            'department_id' => $department->id,
            'active' => true,
        ]);

        return $user;
    }

    private function tuRole(string $roleSlug): void
    {
        $application = CoreApplication::create([
            'app_code' => 'tu-farmasi',
            'name' => 'TU Farmasi',
            'is_active' => true,
            'is_public_visible' => false,
            'requires_login' => true,
            'is_sensitive' => false,
        ]);

        CoreApplicationRole::create([
            'core_application_id' => $application->id,
            'app_code' => 'tu-farmasi',
            'role_slug' => $roleSlug,
            'role_name' => 'Dosen',
            'is_active' => true,
        ]);
    }
}
