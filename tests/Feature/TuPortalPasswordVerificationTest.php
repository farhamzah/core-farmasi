<?php

namespace Tests\Feature;

use App\Models\CoreApiClient;
use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserAppAccess;
use App\Services\TuFarmasi\TuConnectionReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TuPortalPasswordVerificationTest extends TestCase
{
    use RefreshDatabase;

    private string $clientId = 'tu_portal_test_client';

    private string $clientSecret = 'local-test-client-secret';

    private string $password = 'ValidPass123!';

    public function test_endpoint_requires_app_client_auth(): void
    {
        $this->postJson($this->endpoint(), [
            'login' => 'user@example.test',
            'password' => $this->password,
        ])->assertUnauthorized();
    }

    public function test_invalid_client_is_rejected(): void
    {
        $this->makeApplication();

        $this->postJson($this->endpoint(), [
            'login' => 'user@example.test',
            'password' => $this->password,
        ], [
            'X-Core-App-Code' => 'tu-farmasi',
            'X-Core-Client-Id' => 'invalid-client',
            'X-Core-Client-Secret' => 'invalid-secret',
            'Accept' => 'application/json',
        ])->assertUnauthorized();
    }

    public function test_missing_ability_is_rejected(): void
    {
        $this->prepareClient(['read:users']);

        $this->postJson($this->endpoint(), [
            'login' => 'user@example.test',
            'password' => $this->password,
        ], $this->headers())->assertForbidden();
    }

    public function test_valid_user_password_and_active_access_returns_safe_identity(): void
    {
        $this->prepareClient();
        $user = $this->makeAccessibleLecturerUser();

        $this->postJson($this->endpoint(), [
            'login' => $user->email,
            'password' => $this->password,
            'context' => ['source' => 'feature-test'],
        ], $this->headers())
            ->assertOk()
            ->assertJsonPath('authenticated', true)
            ->assertJsonPath('has_access', true)
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.profile_type', 'lecturer')
            ->assertJsonPath('user.lecturer.nidn', $user->lecturer->lecturer_number)
            ->assertJsonPath('app_access.app_code', 'tu-farmasi')
            ->assertJsonPath('app_access.roles.0', 'dosen');
    }

    public function test_wrong_password_returns_generic_failure(): void
    {
        $this->prepareClient();
        $user = $this->makeAccessibleLecturerUser();

        $this->postJson($this->endpoint(), [
            'login' => $user->email,
            'password' => 'WrongPass123!',
        ], $this->headers())
            ->assertOk()
            ->assertExactJson($this->genericFailure());
    }

    public function test_valid_password_without_app_access_returns_generic_failure(): void
    {
        $this->prepareClient();
        $user = $this->makeUser();

        $this->postJson($this->endpoint(), [
            'login' => $user->email,
            'password' => $this->password,
        ], $this->headers())
            ->assertOk()
            ->assertExactJson($this->genericFailure());
    }

    public function test_inactive_app_access_returns_generic_failure(): void
    {
        $this->prepareClient();
        $user = $this->makeUser();
        UserAppAccess::create([
            'user_id' => $user->id,
            'app_code' => 'tu-farmasi',
            'role_slug' => 'dosen',
            'is_active' => false,
        ]);

        $this->postJson($this->endpoint(), [
            'login' => $user->email,
            'password' => $this->password,
        ], $this->headers())
            ->assertOk()
            ->assertExactJson($this->genericFailure());
    }

    public function test_inactive_user_returns_generic_failure(): void
    {
        $this->prepareClient();
        $user = $this->makeAccessibleLecturerUser(['active' => false]);

        $this->postJson($this->endpoint(), [
            'login' => $user->email,
            'password' => $this->password,
        ], $this->headers())
            ->assertOk()
            ->assertExactJson($this->genericFailure());
    }

    public function test_login_identifier_supports_username_and_identity_number(): void
    {
        $this->prepareClient();
        $user = $this->makeAccessibleLecturerUser([
            'username' => 'portal.username',
            'identity_number' => 'CORE-ID-123',
        ]);

        $this->postJson($this->endpoint(), [
            'login' => 'portal.username',
            'password' => $this->password,
        ], $this->headers())
            ->assertOk()
            ->assertJsonPath('authenticated', true);

        $this->postJson($this->endpoint(), [
            'login' => 'CORE-ID-123',
            'password' => $this->password,
        ], $this->headers())
            ->assertOk()
            ->assertJsonPath('authenticated', true)
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_login_identifier_supports_student_number(): void
    {
        $this->prepareClient();
        $user = $this->makeAccessibleStudentUser();

        $this->postJson($this->endpoint(), [
            'login' => $user->student->student_number,
            'password' => $this->password,
        ], $this->headers())
            ->assertOk()
            ->assertJsonPath('authenticated', true)
            ->assertJsonPath('user.profile_type', 'student')
            ->assertJsonPath('user.student.nim', $user->student->student_number);
    }

    public function test_login_identifier_supports_lecturer_number(): void
    {
        $this->prepareClient();
        $user = $this->makeAccessibleLecturerUser();

        $this->postJson($this->endpoint(), [
            'login' => $user->lecturer->lecturer_number,
            'password' => $this->password,
        ], $this->headers())
            ->assertOk()
            ->assertJsonPath('authenticated', true)
            ->assertJsonPath('user.profile_type', 'lecturer');
    }

    public function test_login_identifier_supports_employee_number(): void
    {
        $this->prepareClient();
        $user = $this->makeAccessibleEmployeeUser();

        $this->postJson($this->endpoint(), [
            'login' => $user->employee->employee_number,
            'password' => $this->password,
        ], $this->headers())
            ->assertOk()
            ->assertJsonPath('authenticated', true)
            ->assertJsonPath('user.profile_type', 'employee')
            ->assertJsonPath('user.employee.employee_number', $user->employee->employee_number);
    }

    public function test_response_does_not_contain_password_hash_token_or_secret(): void
    {
        $this->prepareClient();
        $user = $this->makeAccessibleLecturerUser();

        $content = $this->postJson($this->endpoint(), [
            'login' => $user->email,
            'password' => $this->password,
        ], $this->headers())
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('password', strtolower($content));
        $this->assertStringNotContainsString('secret', strtolower($content));
        $this->assertStringNotContainsString('token', strtolower($content));
        $this->assertStringNotContainsString('remember_token', strtolower($content));
        $this->assertStringNotContainsString('api_token', strtolower($content));
    }

    public function test_endpoint_does_not_create_token_change_password_or_create_user(): void
    {
        $this->prepareClient();
        $user = $this->makeAccessibleLecturerUser();
        $originalPassword = $user->password;
        $originalUserCount = User::count();

        $this->postJson($this->endpoint(), [
            'login' => $user->email,
            'password' => $this->password,
        ], $this->headers())->assertOk();

        $user->refresh();

        $this->assertSame($originalUserCount, User::count());
        $this->assertSame($originalPassword, $user->password);
        $this->assertNull($user->api_token);
    }

    public function test_readiness_command_shows_portal_verify_endpoint(): void
    {
        $this->prepareClient();

        $this->artisan('core:tu-connection-readiness')
            ->assertExitCode(0)
            ->expectsOutputToContain('Portal verify endpoint available')
            ->expectsOutputToContain('ready_for_staging_config')
            ->doesntExpectOutputToContain('secret_hash')
            ->doesntExpectOutputToContain('client_secret')
            ->doesntExpectOutputToContain('password');
    }

    private function prepareClient(?array $abilities = null): CoreApiClient
    {
        $this->makeApplication();
        $this->makeRoles();

        $application = CoreApplication::where('app_code', 'tu-farmasi')->first();

        return CoreApiClient::create([
            'core_application_id' => $application?->id,
            'app_code' => 'tu-farmasi',
            'name' => 'TU Portal Test Client',
            'client_id' => $this->clientId,
            'secret_hash' => Hash::make($this->clientSecret),
            'abilities' => $abilities ?? app(TuConnectionReadinessService::class)->requiredAbilities(),
            'is_active' => true,
            'last_rotated_at' => now(),
        ]);
    }

    private function makeApplication(): CoreApplication
    {
        return CoreApplication::firstOrCreate(
            ['app_code' => 'tu-farmasi'],
            [
                'name' => 'TU Farmasi',
                'is_active' => true,
                'is_public_visible' => false,
                'requires_login' => true,
                'is_sensitive' => false,
                'sort_order' => 40,
            ],
        );
    }

    private function makeRoles(): void
    {
        foreach (app(TuConnectionReadinessService::class)->requiredRoleSlugs() as $roleSlug) {
            $this->makeRole($roleSlug);
        }
    }

    private function makeRole(string $roleSlug): CoreApplicationRole
    {
        $application = CoreApplication::where('app_code', 'tu-farmasi')->first();

        return CoreApplicationRole::firstOrCreate(
            ['app_code' => 'tu-farmasi', 'role_slug' => $roleSlug],
            [
                'core_application_id' => $application?->id,
                'role_name' => str($roleSlug)->replace('-', ' ')->title()->toString(),
                'is_active' => true,
                'sort_order' => 10,
            ],
        );
    }

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create([
            'name' => $overrides['name'] ?? 'Portal Test User',
            'email' => $overrides['email'] ?? fake()->unique()->safeEmail(),
            'username' => $overrides['username'] ?? fake()->unique()->userName(),
            'identity_type' => $overrides['identity_type'] ?? 'lecturer',
            'identity_number' => $overrides['identity_number'] ?? fake()->unique()->numerify('ID-######'),
            'password' => $this->password,
            'active' => $overrides['active'] ?? true,
            'api_token' => null,
        ]);
    }

    private function makeAccessibleLecturerUser(array $overrides = []): User
    {
        $user = $this->makeUser(['identity_type' => 'lecturer', ...$overrides]);
        $department = $this->makeDepartment();

        Lecturer::create([
            'user_id' => $user->id,
            'lecturer_number' => $overrides['lecturer_number'] ?? fake()->unique()->numerify('NIDN######'),
            'name' => $user->name,
            'email' => 'lecturer-'.$user->id.'@example.test',
            'department_id' => $department->id,
            'active' => true,
        ]);

        $this->giveAccess($user, 'dosen');

        return $user->fresh(['lecturer', 'appAccesses']);
    }

    private function makeAccessibleStudentUser(): User
    {
        $user = $this->makeUser(['identity_type' => 'student']);
        $studyProgram = $this->makeStudyProgram();

        Student::create([
            'user_id' => $user->id,
            'student_number' => fake()->unique()->numerify('23########'),
            'name' => $user->name,
            'email' => 'student-'.$user->id.'@example.test',
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
            'active' => true,
        ]);

        $this->giveAccess($user, 'mahasiswa');

        return $user->fresh(['student', 'appAccesses']);
    }

    private function makeAccessibleEmployeeUser(): User
    {
        $user = $this->makeUser(['identity_type' => 'employee']);
        $department = $this->makeDepartment();

        Employee::create([
            'user_id' => $user->id,
            'employee_number' => fake()->unique()->numerify('EMP######'),
            'name' => $user->name,
            'staff_type' => 'staf-tu',
            'department_id' => $department->id,
            'position_title' => 'Staf TU',
            'email' => 'employee-'.$user->id.'@example.test',
            'status' => 'active',
        ]);

        $this->giveAccess($user, 'staf-tu');

        return $user->fresh(['employee', 'appAccesses']);
    }

    private function giveAccess(User $user, string $roleSlug): UserAppAccess
    {
        return UserAppAccess::create([
            'user_id' => $user->id,
            'app_code' => 'tu-farmasi',
            'role_slug' => $roleSlug,
            'is_active' => true,
            'activated_at' => now(),
        ]);
    }

    private function makeDepartment(): Department
    {
        return Department::firstOrCreate(
            ['code' => fake()->unique()->bothify('DPT-###')],
            ['name' => 'Departemen Farmasi', 'active' => true],
        );
    }

    private function makeStudyProgram(): StudyProgram
    {
        $department = $this->makeDepartment();

        return StudyProgram::create([
            'department_id' => $department->id,
            'code' => fake()->unique()->bothify('PRG-###'),
            'name' => 'Program Studi Farmasi',
            'active' => true,
        ]);
    }

    private function headers(): array
    {
        return [
            'X-Core-App-Code' => 'tu-farmasi',
            'X-Core-Client-Id' => $this->clientId,
            'X-Core-Client-Secret' => $this->clientSecret,
            'Accept' => 'application/json',
        ];
    }

    private function endpoint(): string
    {
        return '/api/v1/internal/apps/tu-farmasi/portal-auth/verify';
    }

    private function genericFailure(): array
    {
        return [
            'authenticated' => false,
            'has_access' => false,
            'reason' => 'invalid_credentials_or_access',
        ];
    }
}
