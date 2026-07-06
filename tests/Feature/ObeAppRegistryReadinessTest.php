<?php

namespace Tests\Feature;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\UserAppAccess;
use App\Services\AppConnectionReadinessService;
use Database\Seeders\CoreApplicationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObeAppRegistryReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_obe_app_registry_and_roles_are_seeded_idempotently(): void
    {
        $this->seed(CoreApplicationSeeder::class);
        $this->seed(CoreApplicationSeeder::class);

        $this->assertSame(1, CoreApplication::where('app_code', 'obe-farmasi')->count());
        $this->assertDatabaseHas('core_applications', [
            'app_code' => 'obe-farmasi',
            'name' => 'OBE Farmasi',
            'base_url' => 'http://127.0.0.1:8008',
            'admin_url' => 'http://127.0.0.1:8008/admin',
            'is_active' => true,
            'is_public_visible' => false,
            'requires_login' => true,
        ]);

        foreach ($this->requiredRoles() as $roleSlug) {
            $this->assertSame(
                1,
                CoreApplicationRole::where('app_code', 'obe-farmasi')->where('role_slug', $roleSlug)->count(),
                "Missing or duplicated {$roleSlug}",
            );
        }

        $this->assertSame(0, UserAppAccess::where('app_code', 'obe-farmasi')->count());
    }

    public function test_obe_readiness_uses_standard_read_only_abilities(): void
    {
        $readiness = app(AppConnectionReadinessService::class);

        $this->assertContains('obe-farmasi', $readiness->supportedAppCodes());
        $this->assertSame([
            'read:users',
            'read:students',
            'read:lecturers',
            'read:employees',
            'read:study-programs',
            'read:departments',
            'read:app-access',
            'read:leadership',
        ], $readiness->requiredAbilities('obe-farmasi'));
    }

    /**
     * @return array<int, string>
     */
    protected function requiredRoles(): array
    {
        return [
            'super-admin',
            'admin-fakultas',
            'dekan',
            'kaprodi-s1',
            'kaprodi-psppa',
            'gpm-upm',
            'koordinator-kurikulum',
            'koordinator-mk',
            'dosen-pengampu',
            'auditor',
        ];
    }
}
