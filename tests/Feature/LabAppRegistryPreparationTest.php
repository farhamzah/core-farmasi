<?php

namespace Tests\Feature;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\UserAppAccess;
use Database\Seeders\CoreApplicationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabAppRegistryPreparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_lab_app_registry_and_roles_are_seeded_idempotently(): void
    {
        $this->seed(CoreApplicationSeeder::class);
        $this->seed(CoreApplicationSeeder::class);

        $this->assertSame(1, CoreApplication::where('app_code', 'lab-farmasi')->count());
        $this->assertDatabaseHas('core_applications', [
            'app_code' => 'lab-farmasi',
            'name' => 'Lab Farmasi',
            'is_active' => true,
            'is_public_visible' => false,
            'requires_login' => true,
        ]);

        foreach ($this->requiredRoles() as $roleSlug) {
            $this->assertSame(
                1,
                CoreApplicationRole::where('app_code', 'lab-farmasi')->where('role_slug', $roleSlug)->count(),
                "Missing or duplicated {$roleSlug}",
            );
        }

        $this->assertSame(0, UserAppAccess::where('app_code', 'lab-farmasi')->count());
    }

    public function test_lab_app_readiness_command_does_not_expose_secret_or_grant_access(): void
    {
        $this->seed(CoreApplicationSeeder::class);

        $this->artisan('core:lab-app-readiness')
            ->expectsOutputToContain('Core Lab Farmasi app readiness')
            ->expectsOutputToContain('ready')
            ->expectsOutputToContain('no user access auto-grant')
            ->doesntExpectOutputToContain('client_secret')
            ->doesntExpectOutputToContain('password')
            ->assertExitCode(0);

        $this->assertSame(0, UserAppAccess::where('app_code', 'lab-farmasi')->count());
    }

    /**
     * @return array<int, string>
     */
    protected function requiredRoles(): array
    {
        return [
            'lab-admin',
            'lab-koordinator',
            'lab-kepala-lab',
            'lab-laboran',
            'lab-dosen',
            'lab-asisten',
            'lab-mahasiswa',
            'lab-teknisi',
            'lab-viewer',
        ];
    }
}
