<?php

namespace Tests\Feature;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\UserAppAccess;
use Database\Seeders\CoreApplicationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaAppRegistryReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_ta_app_registry_and_roles_are_seeded_idempotently(): void
    {
        $this->seed(CoreApplicationSeeder::class);
        $this->seed(CoreApplicationSeeder::class);

        $this->assertSame(1, CoreApplication::where('app_code', 'ta-farmasi')->count());
        $this->assertDatabaseHas('core_applications', [
            'app_code' => 'ta-farmasi',
            'name' => 'TA Farmasi UBP',
            'base_url' => 'http://127.0.0.1:8007',
            'admin_url' => 'http://127.0.0.1:8007/admin',
            'is_active' => true,
            'is_public_visible' => false,
            'requires_login' => true,
        ]);

        foreach ($this->requiredRoles() as $roleSlug) {
            $this->assertSame(
                1,
                CoreApplicationRole::where('app_code', 'ta-farmasi')->where('role_slug', $roleSlug)->count(),
                "Missing or duplicated {$roleSlug}",
            );
        }

        $this->assertSame(0, UserAppAccess::where('app_code', 'ta-farmasi')->count());
    }

    public function test_ta_app_readiness_command_does_not_expose_secret_or_grant_access(): void
    {
        $this->seed(CoreApplicationSeeder::class);

        $this->artisan('core:ta-app-readiness')
            ->expectsOutputToContain('Core TA Farmasi app readiness')
            ->expectsOutputToContain('ready')
            ->expectsOutputToContain('no user access auto-grant')
            ->doesntExpectOutputToContain('client_secret')
            ->doesntExpectOutputToContain('password=')
            ->doesntExpectOutputToContain('token=')
            ->assertExitCode(0);

        $this->assertSame(0, UserAppAccess::where('app_code', 'ta-farmasi')->count());
    }

    /**
     * @return array<int, string>
     */
    protected function requiredRoles(): array
    {
        return [
            'mahasiswa',
            'dosen',
            'dosen-pembimbing',
            'penguji',
            'koordinator-ta',
            'admin-ta',
            'kaprodi',
            'dekan',
            'validator',
        ];
    }
}
