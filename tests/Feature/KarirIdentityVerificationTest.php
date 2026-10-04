<?php

namespace Tests\Feature;

use App\Models\CareerAlumniGrant;
use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\User;
use App\Models\UserAppAccess;
use App\Services\CoreApiClientCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KarirIdentityVerificationTest extends TestCase
{
    use RefreshDatabase;

    private string $clientId;

    private string $clientSecret;

    protected function setUp(): void
    {
        parent::setUp();

        config(['core_karir.issuer' => 'https://core.test.invalid']);

        $application = CoreApplication::create([
            'app_code' => 'karir-farmasi',
            'name' => 'Alumni Farmasi',
            'is_active' => true,
        ]);
        CoreApplicationRole::create([
            'core_application_id' => $application->id,
            'app_code' => 'karir-farmasi',
            'role_slug' => 'admin-karir',
            'role_name' => 'Admin Karir',
            'is_active' => true,
        ]);

        [$client, $this->clientSecret] = app(CoreApiClientCredentialService::class)->createClient([
            'app_code' => 'karir-farmasi',
            'name' => 'Karir identity test',
            'abilities' => ['verify:karir-identity'],
        ]);
        $this->clientId = $client->client_id;
    }

    public function test_existing_core_credentials_can_be_verified_without_changing_them(): void
    {
        $user = User::factory()->create([
            'password' => 'Existing-password-91',
            'api_token' => hash('sha256', 'existing-api-token'),
            'active' => true,
        ]);
        $passwordHash = $user->password;
        $apiToken = $user->api_token;
        $this->grantKarirAccess($user);

        $this->postJson($this->endpoint(), [
            'identifier' => $user->email,
            'password' => 'Existing-password-91',
        ], $this->headers())->assertOk()
            ->assertJsonPath('principal.app_code', 'karir-farmasi')
            ->assertJsonPath('principal.has_app_access', true)
            ->assertJsonPath('principal.eligibility_source', 'alumni_admin_approval')
            ->assertJsonPath('principal.roles.0.slug', 'admin-karir');

        $this->assertSame($passwordHash, $user->fresh()->password);
        $this->assertSame($apiToken, $user->fresh()->api_token);
    }

    public function test_invalid_credentials_and_missing_access_are_rejected(): void
    {
        $user = User::factory()->create(['password' => 'Existing-password-91', 'active' => true]);

        $this->postJson($this->endpoint(), [
            'identifier' => $user->email,
            'password' => 'incorrect-password',
        ], $this->headers())->assertUnauthorized();

        $this->postJson($this->endpoint(), [
            'identifier' => $user->email,
            'password' => 'Existing-password-91',
        ], $this->headers())->assertForbidden();

        $this->grantKarirAccess($user);
        $this->postJson($this->endpoint(), [
            'identifier' => $user->email,
            'password' => 'Existing-password-91',
        ], array_merge($this->headers(), ['X-Core-Client-Secret' => 'wrong-secret']))->assertUnauthorized();
    }

    public function test_active_karir_admin_role_can_verify_without_an_alumni_grant(): void
    {
        $user = User::factory()->create(['password' => 'Existing-password-91', 'active' => true]);
        UserAppAccess::create([
            'user_id' => $user->id,
            'app_code' => 'karir-farmasi',
            'role_slug' => 'admin-karir',
            'is_active' => true,
            'activated_at' => now(),
        ]);

        $this->postJson($this->endpoint(), [
            'identifier' => $user->email,
            'password' => 'Existing-password-91',
        ], $this->headers())->assertOk()
            ->assertJsonPath('principal.eligibility_source', 'core_operational_role');
    }

    private function grantKarirAccess(User $user): void
    {
        CareerAlumniGrant::create([
            'user_id' => $user->id,
            'career_scope' => 'farmasi',
            'eligibility_source' => 'alumni_admin_approval',
            'is_active' => true,
            'approved_at' => now(),
        ]);
        UserAppAccess::create([
            'user_id' => $user->id,
            'app_code' => 'karir-farmasi',
            'role_slug' => 'admin-karir',
            'is_active' => true,
            'activated_at' => now(),
        ]);
    }

    private function endpoint(): string
    {
        return '/api/v1/internal/apps/karir-farmasi/identity/verify';
    }

    private function headers(): array
    {
        return [
            'X-Core-App-Code' => 'karir-farmasi',
            'X-Core-Client-Id' => $this->clientId,
            'X-Core-Client-Secret' => $this->clientSecret,
            'Accept' => 'application/json',
        ];
    }
}
