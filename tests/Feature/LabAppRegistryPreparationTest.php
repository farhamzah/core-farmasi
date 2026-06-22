<?php

namespace Tests\Feature;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\Lecturer;
use App\Models\UserAppAccess;
use Database\Seeders\CoreApplicationSeeder;
use Database\Seeders\LabFarmasiDevUserSeeder;
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

    public function test_local_lab_demo_users_are_seeded_one_role_each(): void
    {
        $this->seed(LabFarmasiDevUserSeeder::class);
        $this->seed(LabFarmasiDevUserSeeder::class);

        foreach ($this->demoUsers() as $email => $roleSlug) {
            $this->assertDatabaseHas('users', [
                'email' => $email,
                'active' => true,
                'identity_type' => 'dev_lab_demo',
            ]);

            $this->assertDatabaseHas('user_app_accesses', [
                'app_code' => 'lab-farmasi',
                'role_slug' => $roleSlug,
                'is_active' => true,
            ]);
        }

        $this->assertSame(7, UserAppAccess::where('app_code', 'lab-farmasi')
            ->whereIn('role_slug', array_values($this->demoUsers()))
            ->count());

        $this->assertDatabaseHas('lecturers', [
            'email' => 'lab.demo.dosen@example.test',
            'name' => 'Dosen Lab Demo',
            'front_title' => 'Dr.',
            'back_title' => 'M.Farm.',
        ]);

        $this->assertSame(
            'Dr. Dosen Lab Demo, M.Farm.',
            Lecturer::where('email', 'lab.demo.dosen@example.test')->firstOrFail()->display_name_with_title,
        );
    }

    /**
     * @return array<int, string>
     */
    protected function requiredRoles(): array
    {
        return [
            'admin_lab',
            'koordinator_lab',
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

    /**
     * @return array<string, string>
     */
    protected function demoUsers(): array
    {
        return [
            'lab.demo.mahasiswa@example.test' => 'mahasiswa',
            'lab.demo.dosen@example.test' => 'dosen',
            'lab.demo.laboran@example.test' => 'laboran',
            'lab.demo.teknisi@example.test' => 'teknisi',
            'lab.demo.koordinator@example.test' => 'koordinator_lab',
            'lab.demo.admin@example.test' => 'admin_lab',
            'lab.demo.viewer@example.test' => 'viewer',
        ];
    }
}
