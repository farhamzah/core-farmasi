<?php

namespace Tests\Feature;

use App\Models\CoreApiClient;
use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\User;
use App\Models\UserAppAccess;
use App\Services\TuFarmasi\TuConnectionReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TuConnectionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_detects_tu_farmasi_application(): void
    {
        $this->makeApplication();

        $summary = app(TuConnectionReadinessService::class)->checkApplication();

        $this->assertTrue($summary['exists']);
        $this->assertTrue($summary['is_active']);
        $this->assertFalse($summary['is_public_visible']);
    }

    public function test_service_detects_missing_roles(): void
    {
        $this->makeApplication();
        $this->makeRole('admin-tu');

        $roles = app(TuConnectionReadinessService::class)->checkApplicationRoles();

        $this->assertFalse($roles['complete']);
        $this->assertContains('staf-tu', $roles['missing']);
        $this->assertContains('penandatangan', $roles['missing']);
    }

    public function test_service_detects_active_api_client_without_exposing_secret(): void
    {
        $this->makeApplication();
        $this->makeRoles();
        $this->makeClient(['read:users']);

        $apiClients = app(TuConnectionReadinessService::class)->checkApiClients();

        $this->assertSame(1, $apiClients['active_count']);
        $this->assertFalse($apiClients['has_complete_client']);
        $this->assertArrayNotHasKey('secret_hash', $apiClients['clients'][0]);
        $this->assertArrayNotHasKey('client_secret', $apiClients['clients'][0]);
        $this->assertContains('read:students', $apiClients['clients'][0]['missing_abilities']);
    }

    public function test_readiness_returns_missing_api_client_without_client(): void
    {
        $this->makeApplication();
        $this->makeRoles();

        $summary = app(TuConnectionReadinessService::class)->readinessSummary();

        $this->assertSame('missing_api_client', $summary['verdict']);
    }

    public function test_readiness_returns_ready_when_required_parts_exist(): void
    {
        $this->makeApplication();
        $this->makeRoles();
        $this->makeClient(app(TuConnectionReadinessService::class)->requiredAbilities());
        UserAppAccess::create([
            'user_id' => User::factory()->create()->id,
            'app_code' => 'tu-farmasi',
            'role_slug' => 'admin-tu',
            'is_active' => true,
        ]);

        $summary = app(TuConnectionReadinessService::class)->readinessSummary();

        $this->assertSame('ready_for_staging_config', $summary['verdict']);
        $this->assertSame(1, $summary['user_app_access']['active_count']);
        $this->assertTrue($summary['endpoints']['complete']);
        $this->assertTrue($summary['endpoints']['profile_route_available']);
    }

    public function test_readiness_checks_do_not_mutate_data(): void
    {
        $this->makeApplication();
        $this->makeRoles();

        $countsBefore = $this->tableCounts();

        app(TuConnectionReadinessService::class)->readinessSummary();

        $this->assertSame($countsBefore, $this->tableCounts());
    }

    public function test_command_runs_without_secret_output(): void
    {
        $this->makeApplication();
        $this->makeRoles();
        $this->makeClient(app(TuConnectionReadinessService::class)->requiredAbilities());

        $this->artisan('core:tu-connection-readiness')
            ->assertExitCode(0)
            ->expectsOutputToContain('Core to TU connection readiness')
            ->expectsOutputToContain('ready_for_staging_config')
            ->doesntExpectOutputToContain('secret_hash')
            ->doesntExpectOutputToContain('client_secret')
            ->doesntExpectOutputToContain('password')
            ->doesntExpectOutputToContain('plain_secret');
    }

    private function makeApplication(): CoreApplication
    {
        return CoreApplication::create([
            'app_code' => 'tu-farmasi',
            'name' => 'TU Farmasi',
            'description' => 'Aplikasi Tata Usaha Farmasi.',
            'is_active' => true,
            'is_public_visible' => false,
            'requires_login' => true,
            'is_sensitive' => false,
            'sort_order' => 40,
        ]);
    }

    private function makeRoles(): void
    {
        foreach (app(TuConnectionReadinessService::class)->requiredRoleSlugs() as $roleSlug) {
            $this->makeRole($roleSlug);
        }
    }

    private function makeRole(string $roleSlug): CoreApplicationRole
    {
        $application = CoreApplication::query()->where('app_code', 'tu-farmasi')->first();

        return CoreApplicationRole::create([
            'core_application_id' => $application?->id,
            'app_code' => 'tu-farmasi',
            'role_slug' => $roleSlug,
            'role_name' => str($roleSlug)->replace('-', ' ')->title()->toString(),
            'is_active' => true,
            'sort_order' => 10,
        ]);
    }

    private function makeClient(array $abilities): CoreApiClient
    {
        $application = CoreApplication::query()->where('app_code', 'tu-farmasi')->first();

        return CoreApiClient::create([
            'core_application_id' => $application?->id,
            'app_code' => 'tu-farmasi',
            'name' => 'TU Staging Client',
            'client_id' => 'tu_test_client_1234567890',
            'secret_hash' => Hash::make('fake-local-test-value'),
            'abilities' => $abilities,
            'is_active' => true,
            'last_rotated_at' => now(),
        ]);
    }

    private function tableCounts(): array
    {
        return [
            'applications' => CoreApplication::count(),
            'roles' => CoreApplicationRole::count(),
            'clients' => CoreApiClient::count(),
            'accesses' => UserAppAccess::count(),
        ];
    }
}
