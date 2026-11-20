<?php

namespace Tests\Feature;

use App\Models\CoreApiClient;
use App\Models\CoreApplication;
use App\Models\User;
use App\Services\CoreApiClientCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CoreInternalRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_internal_access_does_not_share_public_ip_throttle_and_still_requires_credentials(): void
    {
        for ($request = 0; $request < 60; $request++) {
            $this->getJson('/api/v1/health')->assertOk();
        }
        $this->getJson('/api/v1/health')->assertStatus(429);

        $user = User::factory()->create(['active' => true]);
        $path = "/api/v1/internal/apps/ta-farmasi/users/{$user->id}/access";
        $this->getJson($path)->assertUnauthorized();
        $this->withHeaders($this->clientHeaders('ta-farmasi'))->getJson($path)->assertOk();
    }

    public function test_client_limit_is_enforced_with_retry_after_and_recovers_after_window(): void
    {
        config(['core_api.per_app_rate_limits.ta-farmasi' => 2]);
        $user = User::factory()->create(['active' => true]);
        $headers = $this->clientHeaders('ta-farmasi');
        $path = "/api/v1/internal/apps/ta-farmasi/users/{$user->id}/access";
        $this->withHeaders($headers)->getJson($path)->assertOk();
        $this->withHeaders($headers)->getJson($path)->assertOk();
        $response = $this->withHeaders($headers)->getJson($path)->assertStatus(429);
        $this->assertGreaterThan(0, (int) $response->headers->get('Retry-After'));
        $this->assertLessThanOrEqual(60, (int) $response->headers->get('Retry-After'));

        $otherUser = User::factory()->create(['active' => true]);
        $otherPath = "/api/v1/internal/apps/kp-farmasi/users/{$otherUser->id}/access";
        $this->withHeaders($this->clientHeaders('kp-farmasi'))->getJson($otherPath)->assertOk();

        $this->travel(61)->seconds();
        $this->withHeaders($headers)->getJson($path)->assertOk();
    }

    public function test_internal_endpoint_still_requires_client_ability(): void
    {
        $user = User::factory()->create(['active' => true]);
        $this->withHeaders($this->clientHeaders('ta-farmasi', ['read:students']))
            ->getJson("/api/v1/internal/apps/ta-farmasi/users/{$user->id}/access")
            ->assertForbidden();
    }

    private function clientHeaders(string $appCode, array $abilities = ['read:app-access']): array
    {
        $service = app(CoreApiClientCredentialService::class);
        CoreApplication::firstOrCreate(['app_code' => $appCode], ['name' => $appCode, 'is_active' => true]);
        $secret = $service->generatePlainSecret();
        $client = CoreApiClient::create([
            'app_code' => $appCode, 'name' => 'Rate limit test',
            'client_id' => $service->generateClientId($appCode), 'secret_hash' => $service->hashSecret($secret),
            'abilities' => $abilities, 'is_active' => true,
        ]);

        return ['X-Core-Client-Id' => $client->client_id, 'X-Core-Client-Secret' => $secret, 'X-Core-App-Code' => $appCode];
    }
}
