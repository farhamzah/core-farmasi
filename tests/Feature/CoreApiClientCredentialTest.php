<?php

namespace Tests\Feature;

use App\Models\CoreApiClient;
use App\Models\CoreApplication;
use App\Models\CoreApiRequestLog;
use App\Models\Role;
use App\Models\User;
use App\Services\CoreApiClientCredentialService;
use App\Services\CoreApiLogPruningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CoreApiClientCredentialTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_api_clients_table_exists_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('core_api_clients'));

        foreach ([
            'core_application_id',
            'app_code',
            'name',
            'client_id',
            'secret_hash',
            'abilities',
            'allowed_ips',
            'last_used_at',
            'last_rotated_at',
            'revoked_at',
            'is_active',
            'created_by',
            'rotated_by',
            'revoked_by',
            'deleted_at',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('core_api_clients', $column), "Missing core_api_clients.{$column}");
        }
    }

    public function test_core_api_request_logs_table_exists_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('core_api_request_logs'));

        foreach ([
            'core_api_client_id',
            'app_code',
            'client_id',
            'method',
            'path',
            'route_name',
            'status_code',
            'ability',
            'ip_address',
            'user_agent',
            'request_id',
            'duration_ms',
            'is_success',
            'error_code',
            'error_message',
            'created_at',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('core_api_request_logs', $column), "Missing core_api_request_logs.{$column}");
        }
    }

    public function test_api_log_pruning_service_dry_run_counts_without_deleting(): void
    {
        config(['core_api.audit_logs.keep_recent_minimum' => 0]);
        $oldLog = $this->apiLog(['created_at' => now()->subDays(120), 'is_success' => true]);
        $newLog = $this->apiLog(['created_at' => now()->subDays(5), 'is_success' => true]);

        $summary = app(CoreApiLogPruningService::class)->prune([
            'dry_run' => true,
            'retention_days' => 90,
        ]);

        $this->assertSame(1, $summary['total_eligible']);
        $this->assertSame(0, $summary['deleted_count']);
        $this->assertDatabaseHas('core_api_request_logs', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('core_api_request_logs', ['id' => $newLog->id]);
    }

    public function test_api_log_pruning_service_force_deletes_only_old_logs(): void
    {
        config(['core_api.audit_logs.keep_recent_minimum' => 0]);
        $oldLog = $this->apiLog(['created_at' => now()->subDays(120), 'is_success' => true]);
        $newLog = $this->apiLog(['created_at' => now()->subDays(5), 'is_success' => true]);

        $summary = app(CoreApiLogPruningService::class)->prune([
            'dry_run' => false,
            'retention_days' => 90,
            'chunk_size' => 1,
        ]);

        $this->assertSame(1, $summary['total_eligible']);
        $this->assertSame(1, $summary['deleted_count']);
        $this->assertDatabaseMissing('core_api_request_logs', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('core_api_request_logs', ['id' => $newLog->id]);
    }

    public function test_api_log_pruning_invalid_retention_does_not_delete(): void
    {
        config(['core_api.audit_logs.keep_recent_minimum' => 0]);
        $oldLog = $this->apiLog(['created_at' => now()->subDays(120), 'is_success' => true]);

        $summary = app(CoreApiLogPruningService::class)->prune([
            'dry_run' => false,
            'retention_days' => 0,
        ]);

        $this->assertNotNull($summary['error']);
        $this->assertSame(0, $summary['deleted_count']);
        $this->assertDatabaseHas('core_api_request_logs', ['id' => $oldLog->id]);
    }

    public function test_api_log_pruning_keeps_failed_requests_longer_by_default(): void
    {
        config(['core_api.audit_logs.keep_recent_minimum' => 0]);
        $oldSuccess = $this->apiLog(['created_at' => now()->subDays(120), 'is_success' => true, 'status_code' => 200]);
        $oldFailure = $this->apiLog(['created_at' => now()->subDays(120), 'is_success' => false, 'status_code' => 401]);

        $summary = app(CoreApiLogPruningService::class)->prune([
            'dry_run' => false,
            'retention_days' => 90,
            'keep_failed_requests_days' => 180,
        ]);

        $this->assertSame(1, $summary['deleted_count']);
        $this->assertDatabaseMissing('core_api_request_logs', ['id' => $oldSuccess->id]);
        $this->assertDatabaseHas('core_api_request_logs', ['id' => $oldFailure->id]);

        app(CoreApiLogPruningService::class)->prune([
            'dry_run' => false,
            'retention_days' => 90,
            'include_failed' => true,
        ]);

        $this->assertDatabaseMissing('core_api_request_logs', ['id' => $oldFailure->id]);
    }

    public function test_prune_api_request_logs_command_dry_run_and_force(): void
    {
        config(['core_api.audit_logs.keep_recent_minimum' => 0]);
        $oldLog = $this->apiLog(['created_at' => now()->subDays(120), 'is_success' => true]);

        $this->artisan('core:prune-api-request-logs', ['--dry-run' => true, '--days' => 90])
            ->assertExitCode(0);

        $this->assertDatabaseHas('core_api_request_logs', ['id' => $oldLog->id]);

        $this->artisan('core:prune-api-request-logs', ['--force' => true, '--days' => 90])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('core_api_request_logs', ['id' => $oldLog->id]);
    }

    public function test_pruning_does_not_delete_api_clients(): void
    {
        config(['core_api.audit_logs.keep_recent_minimum' => 0]);
        $this->application('kp-farmasi');
        [$client] = $this->client('kp-farmasi', ['read:app-access']);
        $this->apiLog([
            'core_api_client_id' => $client->id,
            'client_id' => $client->client_id,
            'app_code' => $client->app_code,
            'created_at' => now()->subDays(120),
            'is_success' => true,
        ]);

        app(CoreApiLogPruningService::class)->prune([
            'dry_run' => false,
            'retention_days' => 90,
        ]);

        $this->assertDatabaseHas('core_api_clients', ['id' => $client->id]);
    }

    public function test_create_client_stores_only_hashed_secret(): void
    {
        $this->application('kp-farmasi');
        $actor = $this->coreAdmin();

        [$client, $secret] = app(CoreApiClientCredentialService::class)->createClient([
            'app_code' => 'kp-farmasi',
            'name' => 'KP API Client',
            'abilities' => ['read:app-access'],
        ], $actor);

        $this->assertNotSame($secret, $client->secret_hash);
        $this->assertTrue(Hash::check($secret, $client->secret_hash));
        $this->assertArrayNotHasKey('secret_hash', $client->toArray());
        $this->assertDatabaseMissing('core_api_clients', ['secret_hash' => $secret]);
    }

    public function test_validate_rejects_invalid_revoked_inactive_mismatch_and_missing_ability_clients(): void
    {
        $this->application('kp-farmasi');
        $this->application('tu-farmasi');
        [$client, $secret] = $this->client('kp-farmasi', ['read:app-access']);
        $service = app(CoreApiClientCredentialService::class);

        $this->assertTrue($service->validate($client->client_id, $secret, 'kp-farmasi', 'read:app-access')->is($client));
        $this->assertNull($service->validate($client->client_id, 'bad-secret', 'kp-farmasi', 'read:app-access'));
        $this->assertNull($service->validate($client->client_id, $secret, 'tu-farmasi', 'read:app-access'));
        $this->assertNull($service->validate($client->client_id, $secret, 'kp-farmasi', 'read:leadership'));

        $client->forceFill(['is_active' => false])->save();
        $this->assertNull($service->validate($client->client_id, $secret, 'kp-farmasi', 'read:app-access'));

        $client->forceFill(['is_active' => true, 'revoked_at' => now()])->save();
        $this->assertNull($service->validate($client->client_id, $secret, 'kp-farmasi', 'read:app-access'));
    }

    public function test_rotate_changes_secret_and_old_secret_fails(): void
    {
        $this->application('kp-farmasi');
        [$client, $oldSecret] = $this->client('kp-farmasi', ['read:app-access']);
        $service = app(CoreApiClientCredentialService::class);

        $newSecret = $service->rotateSecret($client, $this->coreAdmin());
        $client->refresh();

        $this->assertFalse(Hash::check($oldSecret, $client->secret_hash));
        $this->assertTrue(Hash::check($newSecret, $client->secret_hash));
        $this->assertNull($service->validate($client->client_id, $oldSecret, 'kp-farmasi', 'read:app-access'));
        $this->assertNotNull($service->validate($client->client_id, $newSecret, 'kp-farmasi', 'read:app-access'));
        $this->assertNotNull($client->last_rotated_at);
    }

    public function test_revoke_blocks_access(): void
    {
        $this->application('kp-farmasi');
        [$client, $secret] = $this->client('kp-farmasi', ['read:app-access']);

        app(CoreApiClientCredentialService::class)->revoke($client, $this->coreAdmin());
        $client->refresh();

        $this->assertFalse($client->is_active);
        $this->assertNotNull($client->revoked_at);
        $this->assertNull(app(CoreApiClientCredentialService::class)->validate($client->client_id, $secret, 'kp-farmasi', 'read:app-access'));
    }

    public function test_middleware_rejects_query_string_token_and_accepts_headers(): void
    {
        $this->application('kp-farmasi');
        $target = User::factory()->create(['active' => true]);
        [$client, $secret] = $this->client('kp-farmasi', ['read:app-access']);

        $this->getJson("/api/v1/internal/apps/kp-farmasi/users/{$target->id}/access?client_id={$client->client_id}&client_secret={$secret}")
            ->assertUnauthorized();

        $this->withHeaders([
            'X-Core-Client-Id' => $client->client_id,
            'X-Core-Client-Secret' => $secret,
            'X-Core-App-Code' => 'kp-farmasi',
        ])
            ->getJson("/api/v1/internal/apps/kp-farmasi/users/{$target->id}/access")
            ->assertOk();
    }

    public function test_app_client_requests_are_audited_without_secret_or_body(): void
    {
        $this->application('kp-farmasi');
        $target = User::factory()->create(['active' => true]);
        [$client, $secret] = $this->client('kp-farmasi', ['read:app-access']);

        $this->withHeaders([
            'X-Core-Client-Id' => $client->client_id,
            'X-Core-Client-Secret' => $secret,
            'X-Core-App-Code' => 'kp-farmasi',
            'X-Request-Id' => 'req-core-api-test',
        ])
            ->getJson("/api/v1/internal/apps/kp-farmasi/users/{$target->id}/access")
            ->assertOk();

        $log = CoreApiRequestLog::latest('id')->firstOrFail();

        $this->assertSame($client->id, $log->core_api_client_id);
        $this->assertSame('kp-farmasi', $log->app_code);
        $this->assertSame($client->client_id, $log->client_id);
        $this->assertSame('GET', $log->method);
        $this->assertSame(200, $log->status_code);
        $this->assertSame('read:app-access', $log->ability);
        $this->assertTrue($log->is_success);
        $this->assertSame('req-core-api-test', $log->request_id);
        $this->assertStringNotContainsString($secret, json_encode($log->toArray()));
        $this->assertStringNotContainsString('X-Core-Client-Secret', json_encode($log->toArray()));
    }

    public function test_invalid_and_revoked_clients_are_rejected_and_safely_logged(): void
    {
        $this->application('kp-farmasi');
        $target = User::factory()->create(['active' => true]);
        [$client, $secret] = $this->client('kp-farmasi', ['read:app-access']);

        $this->withHeaders([
            'X-Core-Client-Id' => $client->client_id,
            'X-Core-Client-Secret' => 'wrong-secret',
            'X-Core-App-Code' => 'kp-farmasi',
        ])
            ->getJson("/api/v1/internal/apps/kp-farmasi/users/{$target->id}/access")
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthorized']);

        app(CoreApiClientCredentialService::class)->revoke($client, $this->coreAdmin());

        $this->withHeaders([
            'X-Core-Client-Id' => $client->client_id,
            'X-Core-Client-Secret' => $secret,
            'X-Core-App-Code' => 'kp-farmasi',
        ])
            ->getJson("/api/v1/internal/apps/kp-farmasi/users/{$target->id}/access")
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthorized']);

        $this->assertSame(2, CoreApiRequestLog::where('client_id', $client->client_id)->where('status_code', 401)->count());
        $this->assertStringNotContainsString($secret, CoreApiRequestLog::where('client_id', $client->client_id)->get()->toJson());
    }

    public function test_rate_limit_is_per_client_and_returns_429(): void
    {
        config([
            'core_api.default_client_rate_limit' => 1,
            'core_api.client_rate_window_seconds' => 60,
        ]);
        RateLimiter::clear('core-api-client:' . sha1(''));

        $this->application('kp-farmasi');
        $this->application('tu-farmasi');
        $target = User::factory()->create(['active' => true]);
        [$firstClient, $firstSecret] = $this->client('kp-farmasi', ['read:app-access']);
        [$secondClient, $secondSecret] = $this->client('tu-farmasi', ['read:app-access']);

        $this->withHeaders($this->headers($firstClient, $firstSecret))
            ->getJson("/api/v1/internal/apps/kp-farmasi/users/{$target->id}/access")
            ->assertOk();

        $this->withHeaders($this->headers($firstClient, $firstSecret))
            ->getJson("/api/v1/internal/apps/kp-farmasi/users/{$target->id}/access")
            ->assertStatus(429)
            ->assertJson(['message' => 'Too Many Requests']);

        $this->withHeaders($this->headers($secondClient, $secondSecret))
            ->getJson("/api/v1/internal/apps/tu-farmasi/users/{$target->id}/access")
            ->assertOk();
    }

    public function test_api_client_resource_is_protected(): void
    {
        $admin = $this->coreAdmin();

        $this->actingAs($admin)->get('/admin/core-api-clients')->assertOk();
        $this->actingAs($admin)->get('/admin/core-api-clients/create')->assertOk();
        $this->actingAs($admin)->get('/admin/core-api-request-logs')->assertOk();

        $this->app['auth']->guard()->logout();
        $this->flushSession();

        $this->get('/admin/core-api-clients')->assertRedirect('/admin/login');
        $this->get('/admin/core-api-request-logs')->assertRedirect('/admin/login');

        $user = User::factory()->create(['active' => true]);
        $role = Role::create(['name' => 'mahasiswa', 'label' => 'Mahasiswa', 'active' => true]);
        $user->roles()->attach($role);

        $this->actingAs($user)->get('/admin/core-api-clients')->assertForbidden();
        $this->actingAs($user)->get('/admin/core-api-request-logs')->assertForbidden();
    }

    private function application(string $appCode): CoreApplication
    {
        return CoreApplication::create([
            'app_code' => $appCode,
            'name' => str($appCode)->headline()->toString(),
            'is_active' => true,
            'is_public_visible' => false,
            'requires_login' => true,
        ]);
    }

    private function client(string $appCode, array $abilities): array
    {
        $service = app(CoreApiClientCredentialService::class);
        $secret = $service->generatePlainSecret();

        $client = CoreApiClient::create([
            'app_code' => $appCode,
            'name' => "{$appCode} Client",
            'client_id' => $service->generateClientId($appCode),
            'secret_hash' => $service->hashSecret($secret),
            'abilities' => $abilities,
            'is_active' => true,
        ]);

        return [$client, $secret];
    }

    private function apiLog(array $attributes = []): CoreApiRequestLog
    {
        return CoreApiRequestLog::create(array_merge([
            'method' => 'GET',
            'path' => '/api/v1/internal/test',
            'status_code' => 200,
            'is_success' => true,
            'created_at' => now(),
        ], $attributes));
    }

    private function headers(CoreApiClient $client, string $secret): array
    {
        return [
            'X-Core-Client-Id' => $client->client_id,
            'X-Core-Client-Secret' => $secret,
            'X-Core-App-Code' => $client->app_code,
        ];
    }

    private function coreAdmin(): User
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create(['name' => 'super-admin', 'label' => 'Super Admin', 'active' => true]);
        $user->roles()->attach($role);

        return $user;
    }
}
