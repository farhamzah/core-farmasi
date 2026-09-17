<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\CareerAlumniGrant;
use App\Models\CareerAlumniRegistration;
use App\Models\CoreApiClient;
use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\Department;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserAppAccess;
use App\Services\CoreApiClientCredentialService;
use App\Services\Karir\KarirAlumniApprovalService;
use Database\Seeders\CoreApplicationSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class KarirIdentityContractTest extends TestCase
{
    use RefreshDatabase;

    private string $secret;

    private CoreApiClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        config(['core_karir.issuer' => 'https://core.test.invalid']);
        RateLimiter::clear('unused');
        $this->seed(CoreApplicationSeeder::class);
        [$this->client, $this->secret] = app(CoreApiClientCredentialService::class)->createClient([
            'app_code' => 'karir-farmasi',
            'name' => 'Karir contract test',
            'client_id' => 'karir-contract-test',
            'abilities' => [
                'verify:karir-identity',
                'create:karir-alumni-registration',
                'read:karir-person',
                'read:karir-study-program',
            ],
        ]);
    }

    public function test_registry_is_additive_idempotent_and_has_only_the_four_product_roles(): void
    {
        $this->seed(CoreApplicationSeeder::class);

        $this->assertSame(1, CoreApplication::where('app_code', 'karir-farmasi')->count());
        $this->assertSame(
            ['admin-karir', 'kandidat-karir', 'petugas-karir', 'viewer-karir'],
            CoreApplication::where('app_code', 'karir-farmasi')->firstOrFail()->roles()
                ->where('is_active', true)->orderBy('role_slug')->pluck('role_slug')->all(),
        );
        $this->assertDatabaseMissing('core_application_roles', ['app_code' => 'karir-farmasi', 'role_slug' => 'hr']);
    }

    public function test_pending_registration_is_idempotent_hashed_and_grants_no_access(): void
    {
        $payload = $this->registrationPayload();
        $first = $this->postJson($this->registrationEndpoint(), $payload, $this->headers())->assertCreated();
        $registration = CareerAlumniRegistration::firstOrFail();
        $hash = $registration->password_hash;

        $this->assertTrue(Hash::check($payload['password'], $hash));
        $this->assertStringNotContainsString($payload['password'], json_encode($first->json(), JSON_THROW_ON_ERROR));
        $this->assertDatabaseMissing('user_app_accesses', ['app_code' => 'karir-farmasi']);

        $second = $this->postJson($this->registrationEndpoint(), $payload + ['full_name' => 'Changed'], $this->headers())->assertOk();
        $second->assertJsonPath('data.reference', $first->json('data.reference'));
        $this->assertSame($hash, CareerAlumniRegistration::firstOrFail()->password_hash);
        $this->assertSame(1, CareerAlumniRegistration::count());
    }

    public function test_duplicate_claim_enters_manual_review(): void
    {
        $this->postJson($this->registrationEndpoint(), $this->registrationPayload(), $this->headers())->assertCreated();
        $response = $this->postJson($this->registrationEndpoint(), $this->registrationPayload([
            'personal_email' => 'different@example.test',
        ]), $this->headers())->assertCreated();

        $response->assertJsonPath('data.status', 'manual_review');
    }

    public function test_admin_can_approve_minimal_alumni_without_email_verification_or_core_profile_then_verify(): void
    {
        $payload = $this->registrationPayload();
        $this->postJson($this->registrationEndpoint(), $payload, $this->headers())->assertCreated();
        $registration = CareerAlumniRegistration::firstOrFail();
        $admin = $this->coreAdmin();

        $user = app(KarirAlumniApprovalService::class)->approve($registration, $admin);

        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->student);
        $this->assertDatabaseHas('career_alumni_grants', [
            'user_id' => $user->id, 'career_scope' => 'farmasi', 'eligibility_source' => 'alumni_admin_approval', 'is_active' => true,
        ]);
        $this->assertDatabaseHas('user_app_accesses', [
            'user_id' => $user->id, 'app_code' => 'karir-farmasi', 'role_slug' => 'kandidat-karir', 'is_active' => true,
        ]);
        $this->assertDatabaseHas('alumni', [
            'user_id' => $user->id,
            'student_number' => $payload['student_number'],
            'name' => $payload['full_name'],
            'program_name_snapshot' => $payload['claimed_program'],
            'graduation_year' => $payload['graduation_year'],
            'status' => 'verified',
            'source' => 'karir_approval',
            'active' => true,
        ]);
        $this->assertSame(1, Alumni::query()->where('user_id', $user->id)->count());
        $this->assertNull($registration->fresh()->password_hash);

        $response = $this->postJson($this->verifyEndpoint(), [
            'identifier' => $payload['student_number'], 'password' => $payload['password'],
        ], $this->headers())->assertOk();
        $response->assertJsonPath('principal.eligibility_source', 'alumni_admin_approval')
            ->assertJsonPath('principal.program_ids', [])
            ->assertJsonMissingPath('principal.password')
            ->assertJsonMissingPath('principal.api_token');
    }

    public function test_approval_is_idempotent_and_never_overwrites_existing_password(): void
    {
        $oldPassword = 'Existing-password-91';
        $user = User::factory()->create([
            'email' => 'alumni@example.test', 'username' => '20120001', 'identity_number' => '20120001',
            'password' => $oldPassword, 'active' => true,
        ]);
        $originalHash = $user->password;
        $this->postJson($this->registrationEndpoint(), $this->registrationPayload(), $this->headers())->assertCreated();
        $registration = CareerAlumniRegistration::firstOrFail();
        $service = app(KarirAlumniApprovalService::class);
        $admin = $this->coreAdmin();

        $approved = $service->approve($registration, $admin);
        $again = $service->approve($registration->fresh(), $admin);

        $this->assertSame($user->id, $approved->id);
        $this->assertSame($user->id, $again->id);
        $this->assertSame($originalHash, $user->fresh()->password);
        $this->assertTrue(Hash::check($oldPassword, $user->fresh()->password));
        $this->assertSame(1, UserAppAccess::where('user_id', $user->id)->where('app_code', 'karir-farmasi')->count());
    }

    public function test_non_core_admin_cannot_approve_alumni(): void
    {
        $this->postJson($this->registrationEndpoint(), $this->registrationPayload(), $this->headers())->assertCreated();

        $this->expectException(AuthorizationException::class);
        app(KarirAlumniApprovalService::class)->approve(
            CareerAlumniRegistration::firstOrFail(),
            User::factory()->create(['active' => true]),
        );
    }

    public function test_verify_accepts_core_program_source_and_never_rotates_legacy_token(): void
    {
        config(['core_karir.pharmacy_program_codes' => ['S1-FARMASI']]);
        $user = User::factory()->create(['password' => 'Valid-password-91', 'api_token' => hash('sha256', 'legacy-token'), 'active' => true]);
        $department = Department::create(['code' => 'FAR', 'name' => 'Farmasi', 'active' => true]);
        $program = StudyProgram::create(['department_id' => $department->id, 'code' => 'S1-FARMASI', 'name' => 'S1 Farmasi', 'active' => true]);
        Student::create([
            'user_id' => $user->id, 'student_number' => '20200001', 'name' => $user->name,
            'email' => 'student-core@example.test', 'study_program_id' => $program->id, 'status' => 'alumni', 'active' => true,
        ]);
        $this->giveCandidateAccess($user);
        $tokenBefore = $user->api_token;

        $response = $this->postJson($this->verifyEndpoint(), ['identifier' => $user->email, 'password' => 'Valid-password-91'], $this->headers())->assertOk();

        $response->assertJsonPath('principal.eligibility_source', 'core_program')
            ->assertJsonPath('principal.career_scope.0', 'farmasi')
            ->assertJsonPath('principal.app_code', 'karir-farmasi');
        $this->assertSame($tokenBefore, $user->fresh()->api_token);
    }

    public function test_verify_status_semantics_fail_closed(): void
    {
        $user = User::factory()->create(['password' => 'Valid-password-91', 'active' => true]);
        CareerAlumniGrant::create([
            'user_id' => $user->id, 'career_scope' => 'farmasi', 'eligibility_source' => 'alumni_admin_approval',
            'is_active' => true, 'approved_at' => now(),
        ]);

        $this->postJson($this->verifyEndpoint(), ['identifier' => $user->email, 'password' => 'wrong-password'], $this->headers())->assertUnauthorized();
        $this->postJson($this->verifyEndpoint(), ['identifier' => $user->email, 'password' => 'Valid-password-91'], $this->headers())->assertForbidden();
        $this->postJson($this->verifyEndpoint(), ['identifier' => '', 'password' => ''], $this->headers())->assertUnprocessable();

        $this->giveCandidateAccess($user);
        config(['core_karir.issuer' => null]);
        $this->postJson($this->verifyEndpoint(), ['identifier' => $user->email, 'password' => 'Valid-password-91'], $this->headers())->assertStatus(503);
    }

    public function test_wrong_app_missing_ability_and_karir_wildcard_are_rejected(): void
    {
        $headers = $this->headers();
        $headers['X-Core-App-Code'] = 'tu-farmasi';
        $this->postJson($this->verifyEndpoint(), ['identifier' => 'a', 'password' => '12345678'], $headers)->assertUnauthorized();

        $this->client->update(['abilities' => []]);
        $this->postJson($this->verifyEndpoint(), ['identifier' => 'a', 'password' => '12345678'], $this->headers())->assertForbidden();

        $this->client->update(['abilities' => ['*']]);
        $this->postJson($this->verifyEndpoint(), ['identifier' => 'a', 'password' => '12345678'], $this->headers())->assertForbidden();
    }

    public function test_directory_is_single_record_and_minimum_fields_only(): void
    {
        $user = User::factory()->create();
        $department = Department::create(['code' => 'DIR', 'name' => 'Directory', 'active' => true]);
        $program = StudyProgram::create(['department_id' => $department->id, 'code' => 'DIR-P', 'name' => 'Directory Program', 'active' => true]);

        $person = $this->getJson("/api/v1/internal/apps/karir-farmasi/directory/people/{$user->id}", $this->headers())->assertOk();
        $this->assertSame(['core_user_id', 'display_name'], array_keys($person->json('data')));
        $study = $this->getJson("/api/v1/internal/apps/karir-farmasi/directory/study-programs/{$program->id}", $this->headers())->assertOk();
        $this->assertSame(['id', 'code', 'name'], array_keys($study->json('data')));
        $this->getJson('/api/v1/internal/apps/karir-farmasi/directory/people', $this->headers())->assertNotFound();
    }

    public function test_other_application_client_cannot_call_karir_endpoints(): void
    {
        [$client, $secret] = app(CoreApiClientCredentialService::class)->createClient([
            'app_code' => 'tu-farmasi', 'name' => 'Other application', 'abilities' => ['*'],
        ]);
        $headers = ['X-Core-App-Code' => 'tu-farmasi', 'X-Core-Client-Id' => $client->client_id, 'X-Core-Client-Secret' => $secret];
        $user = User::factory()->create();

        $this->getJson("/api/v1/internal/apps/karir-farmasi/directory/people/{$user->id}", $headers)->assertForbidden();
        $this->postJson($this->registrationEndpoint(), $this->registrationPayload(), $headers)->assertForbidden();
        $this->assertDatabaseCount('career_alumni_registrations', 0);
    }

    public function test_verification_respects_access_dates_and_inactive_role_catalog(): void
    {
        $user = User::factory()->create(['password' => 'Valid-password-91', 'active' => true]);
        CareerAlumniGrant::create([
            'user_id' => $user->id, 'career_scope' => 'farmasi', 'eligibility_source' => 'alumni_admin_approval',
            'is_active' => true, 'approved_at' => now(),
        ]);
        $this->giveCandidateAccess($user);
        $access = $user->appAccesses()->firstOrFail();
        $payload = ['identifier' => $user->email, 'password' => 'Valid-password-91'];

        $access->update(['activated_at' => now()->addDay()]);
        $this->postJson($this->verifyEndpoint(), $payload, $this->headers())->assertForbidden();
        $access->update(['activated_at' => now()->subDay(), 'deactivated_at' => now()->subMinute()]);
        $this->postJson($this->verifyEndpoint(), $payload, $this->headers())->assertForbidden();
        $access->update(['activated_at' => null, 'deactivated_at' => now()->addDay()]);
        $this->postJson($this->verifyEndpoint(), $payload, $this->headers())->assertOk();
        CoreApplicationRole::where('app_code', 'karir-farmasi')->where('role_slug', 'kandidat-karir')->update(['is_active' => false]);
        $this->postJson($this->verifyEndpoint(), $payload, $this->headers())->assertForbidden();
    }

    private function coreAdmin(): User
    {
        $admin = User::factory()->create(['active' => true]);
        $role = Role::firstOrCreate(['name' => 'admin-core'], ['label' => 'Admin Core', 'active' => true]);
        $admin->roles()->syncWithoutDetaching($role);

        return $admin;
    }

    private function giveCandidateAccess(User $user): void
    {
        UserAppAccess::create([
            'user_id' => $user->id, 'app_code' => 'karir-farmasi', 'role_slug' => 'kandidat-karir',
            'is_active' => true, 'activated_at' => now(),
        ]);
    }

    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'student_number' => '20120001', 'full_name' => 'Alumni Test', 'claimed_program' => 'S1 Farmasi',
            'graduation_year' => 2016, 'personal_email' => 'alumni@example.test', 'whatsapp' => '081234567890',
            'password' => 'Initial-password-91',
        ], $overrides);
    }

    private function headers(): array
    {
        return [
            'X-Core-App-Code' => 'karir-farmasi', 'X-Core-Client-Id' => $this->client->client_id,
            'X-Core-Client-Secret' => $this->secret, 'Accept' => 'application/json',
        ];
    }

    private function registrationEndpoint(): string
    {
        return '/api/v1/internal/apps/karir-farmasi/alumni-registrations';
    }

    private function verifyEndpoint(): string
    {
        return '/api/v1/internal/apps/karir-farmasi/identity/verify';
    }
}
