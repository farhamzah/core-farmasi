<?php

namespace Tests\Feature;

use App\Models\CoreApiClient;
use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\User;
use App\Models\UserAppAccess;
use App\Services\TuFarmasi\TuConnectionReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GrantTuApiClientAbilityCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_does_not_change_client(): void
    {
        $client = $this->makeClient(['read:users']);
        $originalHash = $client->secret_hash;

        $exitCode = Artisan::call('core:grant-tu-api-client-ability');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $client->refresh();
        $this->assertSame(['read:users'], $client->abilities);
        $this->assertSame($originalHash, $client->secret_hash);
        $this->assertStringContainsString('dry-run', $output);
        $this->assertStringContainsString('verify:tu-portal-auth', $output);
        $this->assertStringNotContainsString('secret_hash', $output);
        $this->assertStringNotContainsString($originalHash, $output);
        $this->assertStringNotContainsString('password', strtolower($output));
        $this->assertStringNotContainsString('token', strtolower($output));
    }

    public function test_apply_adds_ability_without_removing_existing_or_rotating_secret(): void
    {
        $client = $this->makeClient(['read:users', 'read:students']);
        $originalHash = $client->secret_hash;

        $exitCode = Artisan::call('core:grant-tu-api-client-ability', ['--apply' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $client->refresh();
        $this->assertContains('read:users', $client->abilities);
        $this->assertContains('read:students', $client->abilities);
        $this->assertContains('verify:tu-portal-auth', $client->abilities);
        $this->assertSame($originalHash, $client->secret_hash);
        $this->assertStringContainsString('Secret rotation', $output);
        $this->assertStringContainsString('no', $output);
        $this->assertStringNotContainsString($originalHash, $output);
        $this->assertStringNotContainsString('Client Secret', $output);
    }

    public function test_apply_all_required_makes_readiness_ready_when_other_parts_exist(): void
    {
        $this->makeClient([
            'read:users',
            'read:students',
            'read:lecturers',
            'read:employees',
            'read:study-programs',
            'read:departments',
            'read:app-access',
            'read:leadership',
        ]);
        $this->makeUserAccess();

        Artisan::call('core:grant-tu-api-client-ability', [
            '--apply' => true,
            '--all-required' => true,
        ]);

        $summary = app(TuConnectionReadinessService::class)->readinessSummary();

        $this->assertTrue($summary['api_clients']['has_complete_client']);
        $this->assertSame('ready_for_staging_config', $summary['verdict']);
        $this->assertSame([], $summary['api_clients']['clients'][0]['missing_abilities']);
    }

    public function test_client_without_verify_ability_is_reported_as_missing(): void
    {
        $this->makeClient([
            'read:users',
            'read:students',
            'read:lecturers',
            'read:employees',
            'read:study-programs',
            'read:departments',
            'read:app-access',
            'read:leadership',
        ]);

        $summary = app(TuConnectionReadinessService::class)->checkApiClients();

        $this->assertContains('verify:tu-portal-auth', $summary['clients'][0]['missing_abilities']);
        $this->assertFalse($summary['has_complete_client']);
    }

    private function makeClient(array $abilities): CoreApiClient
    {
        $application = CoreApplication::create([
            'app_code' => 'tu-farmasi',
            'name' => 'TU Farmasi',
            'is_active' => true,
            'is_public_visible' => false,
            'requires_login' => true,
            'is_sensitive' => false,
            'sort_order' => 40,
        ]);

        foreach (app(TuConnectionReadinessService::class)->requiredRoleSlugs() as $roleSlug) {
            CoreApplicationRole::create([
                'core_application_id' => $application->id,
                'app_code' => 'tu-farmasi',
                'role_slug' => $roleSlug,
                'role_name' => str($roleSlug)->replace('-', ' ')->title()->toString(),
                'is_active' => true,
                'sort_order' => 10,
            ]);
        }

        return CoreApiClient::create([
            'core_application_id' => $application->id,
            'app_code' => 'tu-farmasi',
            'name' => 'TU Test Client',
            'client_id' => 'tu_test_client_ability',
            'secret_hash' => Hash::make('fake-client-secret'),
            'abilities' => $abilities,
            'is_active' => true,
            'last_rotated_at' => now(),
        ]);
    }

    private function makeUserAccess(): void
    {
        UserAppAccess::create([
            'user_id' => User::factory()->create()->id,
            'app_code' => 'tu-farmasi',
            'role_slug' => 'dosen',
            'is_active' => true,
        ]);
    }
}
