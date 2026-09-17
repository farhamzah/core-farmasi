<?php

namespace Tests\Feature;

use App\Models\CareerAlumniRegistration;
use App\Models\CoreApiClient;
use App\Models\User;
use App\Models\UserAppAccess;
use App\Services\CoreApiClientCredentialService;
use App\Services\Karir\KarirAlumniRegistrationService;
use Database\Seeders\CoreApplicationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KarirAlumniOperationalApiTest extends TestCase
{
    use RefreshDatabase;

    private CoreApiClient $client;

    private string $secret;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreApplicationSeeder::class);
        [$this->client, $this->secret] = app(CoreApiClientCredentialService::class)->createClient([
            'app_code' => 'karir-farmasi',
            'name' => 'Operational test client',
            'client_id' => 'karir-operational-test',
            'abilities' => [
                'read:karir-alumni-registrations',
                'read:karir-alumni-registration-status',
                'approve:karir-alumni-registration',
                'reject:karir-alumni-registration',
            ],
        ]);
    }

    public function test_list_detail_and_status_are_minimum_and_never_expose_credentials(): void
    {
        $registration = $this->registration();

        $list = $this->getJson($this->baseUrl().'?status=pending', $this->headers())->assertOk();
        $detail = $this->getJson($this->baseUrl().'/'.$registration->reference, $this->headers())->assertOk();
        $status = $this->getJson($this->baseUrl().'/'.$registration->reference.'/status', $this->headers())->assertOk();

        $this->assertSame(1, $list->json('meta.total'));
        $this->assertStringNotContainsString('password', json_encode($list->json(), JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('password', json_encode($detail->json(), JSON_THROW_ON_ERROR));
        $this->assertSame(['reference', 'status', 'account_resolution'], array_keys($status->json('data')));
    }

    public function test_active_admin_karir_can_approve_and_existing_password_is_unchanged(): void
    {
        $existing = User::factory()->create([
            'email' => 'existing@example.test',
            'username' => '20120002',
            'identity_number' => '20120002',
            'password' => 'Existing-password-91',
            'active' => true,
        ]);
        $originalHash = $existing->password;
        $registration = $this->registration([
            'student_number' => '20120002',
            'personal_email' => 'existing@example.test',
        ]);
        $admin = $this->adminKarir();

        $response = $this->postJson($this->baseUrl().'/'.$registration->reference.'/approve', [
            'approver_core_user_id' => $admin->id,
        ], $this->headers())->assertOk();

        $response->assertJsonPath('data.account_resolution', 'existing_core_user');
        $this->assertSame($originalHash, $existing->fresh()->password);
        $this->assertTrue(Hash::check('Existing-password-91', $existing->fresh()->password));
    }

    public function test_spoofed_non_admin_and_inactive_admin_are_forbidden(): void
    {
        $registration = $this->registration();
        $nonAdmin = User::factory()->create(['active' => true]);
        $inactiveAdmin = $this->adminKarir(false);

        $this->postJson($this->baseUrl().'/'.$registration->reference.'/approve', [
            'approver_core_user_id' => $nonAdmin->id,
        ], $this->headers())->assertForbidden();
        $this->postJson($this->baseUrl().'/'.$registration->reference.'/approve', [
            'approver_core_user_id' => $inactiveAdmin->id,
        ], $this->headers())->assertForbidden();
        $this->assertSame('pending', $registration->fresh()->status);
    }

    public function test_reject_clears_pending_credential_and_never_grants_access(): void
    {
        $registration = $this->registration();
        $admin = $this->adminKarir();

        $this->postJson($this->baseUrl().'/'.$registration->reference.'/reject', [
            'approver_core_user_id' => $admin->id,
            'reason' => 'Data alumni belum dapat diverifikasi.',
        ], $this->headers())->assertOk()->assertJsonPath('data.status', 'rejected');

        $registration->refresh();
        $this->assertNull($registration->password_hash);
        $this->assertSame('Data alumni belum dapat diverifikasi.', $registration->review_note);
        $this->assertDatabaseMissing('user_app_accesses', ['app_code' => 'karir-farmasi', 'role_slug' => 'kandidat-karir', 'is_active' => true]);
    }

    public function test_missing_decision_ability_is_forbidden(): void
    {
        $registration = $this->registration();
        $admin = $this->adminKarir();
        $this->client->update(['abilities' => ['read:karir-alumni-registrations']]);

        $this->postJson($this->baseUrl().'/'.$registration->reference.'/approve', [
            'approver_core_user_id' => $admin->id,
        ], $this->headers())->assertForbidden();
    }

    private function registration(array $overrides = []): CareerAlumniRegistration
    {
        return app(KarirAlumniRegistrationService::class)->register(array_merge([
            'student_number' => '20120001',
            'full_name' => 'Alumni Operasional',
            'claimed_program' => 'S1 Farmasi',
            'graduation_year' => 2016,
            'personal_email' => 'operational@example.test',
            'whatsapp' => '081234567890',
            'password' => 'Initial-password-91',
        ], $overrides));
    }

    private function adminKarir(bool $active = true): User
    {
        $user = User::factory()->create(['active' => $active]);
        UserAppAccess::create([
            'user_id' => $user->id,
            'app_code' => 'karir-farmasi',
            'role_slug' => 'admin-karir',
            'is_active' => true,
            'activated_at' => now(),
        ]);

        return $user;
    }

    private function headers(): array
    {
        return [
            'X-Core-Client-Id' => $this->client->client_id,
            'X-Core-Client-Secret' => $this->secret,
            'X-Core-App-Code' => 'karir-farmasi',
        ];
    }

    private function baseUrl(): string
    {
        return '/api/v1/internal/apps/karir-farmasi/alumni-registrations';
    }
}
