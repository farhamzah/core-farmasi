<?php

namespace Tests\Feature;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use Database\Seeders\KarirApplicationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KarirApplicationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_only_the_canonical_karir_catalog_idempotently(): void
    {
        $unrelatedApplication = CoreApplication::query()->create([
            'app_code' => 'existing-app',
            'name' => 'Existing Custom Name',
            'is_active' => false,
        ]);
        $unrelatedRole = CoreApplicationRole::query()->create([
            'core_application_id' => $unrelatedApplication->id,
            'app_code' => 'existing-app',
            'role_slug' => 'custom-role',
            'role_name' => 'Custom Role',
            'is_active' => false,
            'sort_order' => 91,
        ]);
        CoreApplicationRole::query()->create([
            'app_code' => 'karir-farmasi',
            'role_slug' => 'legacy-role',
            'role_name' => 'Legacy Role',
            'is_active' => true,
        ]);

        $this->seed(KarirApplicationSeeder::class);
        $this->seed(KarirApplicationSeeder::class);

        $this->assertSame('Existing Custom Name', $unrelatedApplication->fresh()->name);
        $this->assertFalse($unrelatedApplication->fresh()->is_active);
        $this->assertSame(91, $unrelatedRole->fresh()->sort_order);
        $this->assertFalse($unrelatedRole->fresh()->is_active);

        $application = CoreApplication::query()->where('app_code', 'karir-farmasi')->sole();
        $this->assertSame('Alumni Farmasi', $application->name);
        $this->assertSame('https://alumni.safaubp.com', $application->base_url);
        $this->assertSame(
            ['admin-karir', 'kandidat-karir', 'petugas-karir', 'viewer-karir'],
            $application->roles()->where('is_active', true)->orderBy('role_slug')->pluck('role_slug')->all(),
        );
        $this->assertFalse(CoreApplicationRole::query()
            ->where('app_code', 'karir-farmasi')
            ->where('role_slug', 'legacy-role')
            ->value('is_active'));
    }
}
