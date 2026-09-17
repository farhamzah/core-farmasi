<?php

namespace Tests\Feature;

use App\Models\CoreApiClient;
use App\Services\CoreApiClientCredentialService;
use Database\Seeders\CoreApplicationSeeder;
use Dotenv\Dotenv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class IssueKarirApiClientCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $envPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreApplicationSeeder::class);
        $directory = storage_path('framework/testing/karir-'.Str::uuid());
        mkdir($directory, 0700, true);
        $this->envPath = $directory.'/.env';
        file_put_contents($this->envPath, "DB_DATABASE=separate_alumni_database\nAPP_NAME=Alumni\nCORE_IDENTITY_CLIENT_ID=\nCORE_IDENTITY_CLIENT_SECRET=\n");
    }

    protected function tearDown(): void
    {
        unlink($this->envPath);
        rmdir(dirname($this->envPath));
        parent::tearDown();
    }

    public function test_dry_run_never_creates_a_client_or_changes_the_environment(): void
    {
        $before = file_get_contents($this->envPath);
        $this->artisan('core:issue-karir-api-client', ['--env-path' => $this->envPath])->assertSuccessful();
        $this->assertDatabaseCount('core_api_clients', 0);
        $this->assertSame($before, file_get_contents($this->envPath));
    }

    public function test_apply_delivers_secret_to_env_once_with_exact_abilities_without_printing_it(): void
    {
        $options = ['--env-path' => $this->envPath, '--apply' => true];
        $this->assertSame(0, Artisan::call('core:issue-karir-api-client', $options));
        $output = Artisan::output();
        $contents = file_get_contents($this->envPath);
        $env = Dotenv::parse($contents);
        $client = CoreApiClient::sole();
        $this->assertSame('karir-farmasi', $client->app_code);
        $this->assertSame(config('core_karir.client_abilities'), $client->abilities);
        $this->assertCount(8, $client->abilities);
        $this->assertSame($client->client_id, $env['CORE_IDENTITY_CLIENT_ID']);
        $this->assertTrue(Hash::check($env['CORE_IDENTITY_CLIENT_SECRET'], $client->secret_hash));
        $this->assertStringNotContainsString($env['CORE_IDENTITY_CLIENT_SECRET'], $output);
        $this->assertSame('Alumni', $env['APP_NAME']);
        $this->assertSame('separate_alumni_database', $env['DB_DATABASE']);

        $this->assertSame(0, Artisan::call('core:issue-karir-api-client', $options));
        $this->assertSame($contents, file_get_contents($this->envPath));
        $this->assertDatabaseCount('core_api_clients', 1);
        $this->assertStringNotContainsString($env['CORE_IDENTITY_CLIENT_SECRET'], Artisan::output());
    }

    public function test_an_existing_client_is_not_duplicated_or_rotated(): void
    {
        [$client] = app(CoreApiClientCredentialService::class)->createClient([
            'app_code' => 'karir-farmasi', 'name' => 'Previously issued', 'abilities' => config('core_karir.client_abilities'),
        ]);
        $hash = $client->secret_hash;
        $this->artisan('core:issue-karir-api-client', ['--env-path' => $this->envPath, '--apply' => true])->assertFailed();
        $this->assertDatabaseCount('core_api_clients', 1);
        $this->assertSame($hash, $client->fresh()->secret_hash);
    }

    public function test_a_shared_database_is_refused_before_issuing_credentials(): void
    {
        file_put_contents($this->envPath, "DB_DATABASE=\":memory:\"\n");
        $this->artisan('core:issue-karir-api-client', ['--env-path' => $this->envPath, '--apply' => true])->assertFailed();
        $this->assertDatabaseCount('core_api_clients', 0);
    }

    public function test_unknown_existing_env_credentials_are_preserved(): void
    {
        $before = "DB_DATABASE=separate_alumni_database\nCORE_IDENTITY_CLIENT_ID=existing\nCORE_IDENTITY_CLIENT_SECRET=do-not-overwrite\n";
        file_put_contents($this->envPath, $before);
        $this->artisan('core:issue-karir-api-client', ['--env-path' => $this->envPath, '--apply' => true])->assertFailed();
        $this->assertSame($before, file_get_contents($this->envPath));
        $this->assertDatabaseCount('core_api_clients', 0);
    }
}
