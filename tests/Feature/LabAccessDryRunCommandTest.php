<?php

namespace Tests\Feature;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\User;
use App\Models\UserAppAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabAccessDryRunCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_does_not_create_access(): void
    {
        $user = User::factory()->create(['active' => true, 'name' => 'Demo Lab User']);
        $this->labAppAndRole('lab-viewer');

        $this->artisan('core:lab-access-dry-run', ['core_user_id' => $user->id, 'role' => 'lab-viewer'])
            ->expectsOutputToContain('dry-run')
            ->expectsOutputToContain('would-create')
            ->doesntExpectOutputToContain('password')
            ->assertExitCode(0);

        $this->assertSame(0, UserAppAccess::query()->count());
    }

    public function test_apply_creates_one_explicit_access_and_revoke_disables_it(): void
    {
        $user = User::factory()->create(['active' => true]);
        $this->labAppAndRole('lab-laboran');

        $this->artisan('core:lab-access-dry-run', ['core_user_id' => $user->id, 'role' => 'lab-laboran', '--apply' => true])
            ->expectsOutputToContain('Lab app access created.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('user_app_accesses', [
            'user_id' => $user->id,
            'app_code' => 'lab-farmasi',
            'role_slug' => 'lab-laboran',
            'is_active' => true,
        ]);
        $this->assertSame(1, UserAppAccess::query()->count());

        $this->artisan('core:lab-access-dry-run', ['core_user_id' => $user->id, 'role' => 'lab-laboran', '--revoke' => true])
            ->expectsOutputToContain('Lab app access deactivated.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('user_app_accesses', [
            'user_id' => $user->id,
            'app_code' => 'lab-farmasi',
            'role_slug' => 'lab-laboran',
            'is_active' => false,
        ]);
    }

    public function test_command_refuses_missing_role_or_user(): void
    {
        $this->labAppAndRole('lab-viewer');

        $this->artisan('core:lab-access-dry-run', ['core_user_id' => 999, 'role' => 'lab-viewer'])
            ->expectsOutputToContain('Core user not found or inactive.')
            ->assertExitCode(1);

        $user = User::factory()->create(['active' => true]);

        $this->artisan('core:lab-access-dry-run', ['core_user_id' => $user->id, 'role' => 'missing-role'])
            ->expectsOutputToContain("Lab role 'missing-role' does not exist or is inactive.")
            ->assertExitCode(1);
    }

    private function labAppAndRole(string $roleSlug): void
    {
        $application = CoreApplication::query()->create([
            'app_code' => 'lab-farmasi',
            'name' => 'Lab Farmasi UBP',
            'is_active' => true,
            'is_public_visible' => false,
            'requires_login' => true,
            'is_sensitive' => false,
        ]);

        CoreApplicationRole::query()->create([
            'core_application_id' => $application->id,
            'app_code' => 'lab-farmasi',
            'role_slug' => $roleSlug,
            'role_name' => $roleSlug,
            'is_active' => true,
        ]);
    }
}
