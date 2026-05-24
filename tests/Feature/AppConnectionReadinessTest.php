<?php

namespace Tests\Feature;

use App\Models\CoreApiClient;
use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Services\AppConnectionReadinessService;
use Database\Seeders\CoreApplicationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AppConnectionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_future_consumer_applications_are_seeded_as_private_active_apps(): void
    {
        $this->seed(CoreApplicationSeeder::class);

        foreach (['ta-farmasi' => 'TA Farmasi', 'lab-farmasi' => 'Lab Farmasi', 'helpdesk-farmasi' => 'Helpdesk Farmasi'] as $appCode => $name) {
            $application = CoreApplication::where('app_code', $appCode)->firstOrFail();

            $this->assertSame($name, $application->name);
            $this->assertTrue($application->is_active);
            $this->assertFalse($application->is_public_visible);
            $this->assertTrue($application->requires_login);
            $this->assertFalse($application->is_sensitive);
        }
    }

    public function test_future_consumer_required_roles_are_seeded(): void
    {
        $this->seed(CoreApplicationSeeder::class);
        $readiness = app(AppConnectionReadinessService::class);

        foreach (['ta-farmasi', 'lab-farmasi', 'helpdesk-farmasi'] as $appCode) {
            $roles = $readiness->checkApplicationRoles($appCode);

            $this->assertTrue($roles['complete']);
            $this->assertSame([], $roles['missing']);
        }
    }

    public function test_readiness_command_works_for_future_consumers_without_secret_output(): void
    {
        $this->seed(CoreApplicationSeeder::class);

        foreach (['ta-farmasi', 'lab-farmasi', 'helpdesk-farmasi'] as $appCode) {
            $exitCode = Artisan::call('core:app-connection-readiness', ['app_code' => $appCode]);
            $output = Artisan::output();

            $this->assertSame(0, $exitCode);
            $this->assertStringContainsString($appCode, $output);
            $this->assertStringContainsString('missing_api_client', $output);
            $this->assertStringNotContainsString('secret_hash', $output);
            $this->assertStringNotContainsString('client_secret', $output);
            $this->assertStringNotContainsString('password', strtolower($output));
            $this->assertStringNotContainsString('token', strtolower($output));
        }
    }

    public function test_readiness_is_ready_when_fake_client_has_required_abilities(): void
    {
        $this->seed(CoreApplicationSeeder::class);
        $readiness = app(AppConnectionReadinessService::class);

        foreach (['ta-farmasi', 'lab-farmasi', 'helpdesk-farmasi'] as $appCode) {
            $this->makeClient($appCode, $readiness->requiredAbilities($appCode));

            $summary = $readiness->readinessSummary($appCode);

            $this->assertSame('ready_for_staging_config', $summary['verdict']);
            $this->assertTrue($summary['api_clients']['has_complete_client']);
            $this->assertTrue($summary['endpoints']['complete']);
            $this->assertTrue($summary['endpoints']['profile_route_available']);
        }
    }

    public function test_seeder_is_idempotent_for_future_consumer_registry(): void
    {
        $this->seed(CoreApplicationSeeder::class);
        $firstCounts = $this->counts();

        $this->seed(CoreApplicationSeeder::class);

        $this->assertSame($firstCounts, $this->counts());
    }

    public function test_unsupported_app_code_fails_safely(): void
    {
        $exitCode = Artisan::call('core:app-connection-readiness', ['app_code' => 'unknown-app']);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Unsupported app code', $output);
        $this->assertStringNotContainsString('secret', strtolower($output));
        $this->assertStringNotContainsString('password', strtolower($output));
        $this->assertStringNotContainsString('token', strtolower($output));
    }

    private function makeClient(string $appCode, array $abilities): CoreApiClient
    {
        $application = CoreApplication::where('app_code', $appCode)->firstOrFail();

        return CoreApiClient::create([
            'core_application_id' => $application->id,
            'app_code' => $appCode,
            'name' => str($appCode)->replace('-', ' ')->title().' Test Client',
            'client_id' => $appCode.'_test_client',
            'secret_hash' => Hash::make('fake-test-secret'),
            'abilities' => $abilities,
            'is_active' => true,
            'last_rotated_at' => now(),
        ]);
    }

    private function counts(): array
    {
        return [
            'ta_applications' => CoreApplication::where('app_code', 'ta-farmasi')->count(),
            'lab_applications' => CoreApplication::where('app_code', 'lab-farmasi')->count(),
            'helpdesk_applications' => CoreApplication::where('app_code', 'helpdesk-farmasi')->count(),
            'ta_roles' => CoreApplicationRole::where('app_code', 'ta-farmasi')->count(),
            'lab_required_roles' => CoreApplicationRole::where('app_code', 'lab-farmasi')
                ->whereIn('role_slug', app(AppConnectionReadinessService::class)->requiredRoleSlugs('lab-farmasi'))
                ->count(),
            'helpdesk_required_roles' => CoreApplicationRole::where('app_code', 'helpdesk-farmasi')
                ->whereIn('role_slug', app(AppConnectionReadinessService::class)->requiredRoleSlugs('helpdesk-farmasi'))
                ->count(),
        ];
    }
}
