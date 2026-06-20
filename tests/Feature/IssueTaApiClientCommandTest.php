<?php

namespace Tests\Feature;

use App\Models\CoreApiClient;
use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Services\AppConnectionReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class IssueTaApiClientCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_does_not_create_core_api_client_or_secret(): void
    {
        $this->makeApplication();

        $exitCode = Artisan::call('core:issue-ta-api-client');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, CoreApiClient::query()->count());
        $this->assertStringContainsString('dry-run', $output);
        $this->assertStringNotContainsString('Client Secret (shown once):', $output);
        $this->assertStringNotContainsString('secret_hash', $output);
        $this->assertStringNotContainsString('password', strtolower($output));
        $this->assertStringNotContainsString('token', strtolower($output));
    }

    public function test_apply_creates_active_ta_client_with_required_abilities(): void
    {
        $this->makeApplication();
        $this->makeRoles();

        $exitCode = Artisan::call('core:issue-ta-api-client', ['--apply' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);

        $client = CoreApiClient::query()->where('app_code', 'ta-farmasi')->firstOrFail();

        $this->assertTrue($client->is_active);
        $this->assertNull($client->revoked_at);
        $this->assertNotEmpty($client->secret_hash);
        $this->assertSame(app(AppConnectionReadinessService::class)->requiredAbilities('ta-farmasi'), $client->abilities);
        $this->assertStringContainsString('Client Secret (shown once): core_', $output);
        $this->assertStringNotContainsString($client->secret_hash, $output);
        $this->assertDatabaseMissing('core_api_clients', ['secret_hash' => 'core_']);
    }

    public function test_apply_again_does_not_create_duplicate_client(): void
    {
        $this->makeApplication();
        $this->makeRoles();

        Artisan::call('core:issue-ta-api-client', ['--apply' => true]);
        Artisan::call('core:issue-ta-api-client', ['--apply' => true]);
        $output = Artisan::output();

        $this->assertSame(1, CoreApiClient::query()->where('app_code', 'ta-farmasi')->count());
        $this->assertStringContainsString('No duplicate client was created.', $output);
        $this->assertStringNotContainsString('Client Secret (shown once):', $output);
    }

    public function test_rotate_existing_rotates_hash_and_shows_secret_once(): void
    {
        $this->makeApplication();
        $this->makeRoles();
        Artisan::call('core:issue-ta-api-client', ['--apply' => true]);

        $client = CoreApiClient::query()->where('app_code', 'ta-farmasi')->firstOrFail();
        $oldHash = $client->secret_hash;

        $exitCode = Artisan::call('core:issue-ta-api-client', [
            '--apply' => true,
            '--rotate-existing' => true,
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $client->refresh();
        $this->assertNotSame($oldHash, $client->secret_hash);
        $this->assertStringContainsString('Client Secret (shown once): core_', $output);
        $this->assertStringNotContainsString($client->secret_hash, $output);
    }

    public function test_show_env_template_uses_placeholders_only(): void
    {
        $this->makeApplication();

        $exitCode = Artisan::call('core:issue-ta-api-client', ['--show-env-template' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('TA_CORE_CLIENT_ID=<client_id>', $output);
        $this->assertStringContainsString('TA_CORE_CLIENT_SECRET=<copy-secret-once>', $output);
        $this->assertStringNotContainsString('core_', $output);
        $this->assertSame(0, CoreApiClient::query()->count());
    }

    public function test_command_refuses_when_ta_application_missing_or_inactive(): void
    {
        $missingExitCode = Artisan::call('core:issue-ta-api-client', ['--apply' => true]);
        $this->assertSame(1, $missingExitCode);

        $this->makeApplication(['is_active' => false]);

        $inactiveExitCode = Artisan::call('core:issue-ta-api-client', ['--apply' => true]);
        $output = Artisan::output();

        $this->assertSame(1, $inactiveExitCode);
        $this->assertStringContainsString('missing or inactive', $output);
        $this->assertSame(0, CoreApiClient::query()->count());
    }

    public function test_readiness_after_apply_has_complete_api_client(): void
    {
        $this->makeApplication();
        $this->makeRoles();

        Artisan::call('core:issue-ta-api-client', ['--apply' => true]);

        $summary = app(AppConnectionReadinessService::class)->readinessSummary('ta-farmasi');

        $this->assertTrue($summary['api_clients']['has_complete_client']);
        $this->assertSame('ready_for_staging_config', $summary['verdict']);
    }

    private function makeApplication(array $overrides = []): CoreApplication
    {
        return CoreApplication::create(array_merge([
            'app_code' => 'ta-farmasi',
            'name' => 'TA Farmasi',
            'description' => 'Aplikasi Tugas Akhir Farmasi.',
            'is_active' => true,
            'is_public_visible' => false,
            'requires_login' => true,
            'is_sensitive' => false,
            'sort_order' => 40,
        ], $overrides));
    }

    private function makeRoles(): void
    {
        $application = CoreApplication::query()->where('app_code', 'ta-farmasi')->first();

        foreach (app(AppConnectionReadinessService::class)->requiredRoleSlugs('ta-farmasi') as $index => $roleSlug) {
            CoreApplicationRole::create([
                'core_application_id' => $application?->id,
                'app_code' => 'ta-farmasi',
                'role_slug' => $roleSlug,
                'role_name' => str($roleSlug)->replace('-', ' ')->title()->toString(),
                'is_active' => true,
                'sort_order' => ($index + 1) * 10,
            ]);
        }
    }
}
