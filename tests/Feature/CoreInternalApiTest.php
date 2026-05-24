<?php

namespace Tests\Feature;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\CoreApiClient;
use App\Models\CoreApiRequestLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeadershipAssignment;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserAppAccess;
use App\Services\CoreApiClientCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CoreInternalApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_internal_endpoint_requires_valid_token(): void
    {
        $target = User::factory()->create(['active' => true]);

        $this->getJson("/api/v1/internal/apps/kp-farmasi/users/{$target->id}/access")
            ->assertUnauthorized();

        $this->withHeaders([
            'X-Core-Client-Id' => 'bad-client',
            'X-Core-Client-Secret' => 'bad-secret',
            'X-Core-App-Code' => 'kp-farmasi',
        ])
            ->getJson("/api/v1/internal/apps/kp-farmasi/users/{$target->id}/access")
            ->assertUnauthorized();
    }

    public function test_app_access_check_returns_active_roles_only_for_active_application(): void
    {
        $target = User::factory()->create(['active' => true]);
        $app = $this->application('kp-farmasi', true);
        $inactiveApp = $this->application('tu-farmasi', false);

        CoreApplicationRole::create([
            'core_application_id' => $app->id,
            'app_code' => 'kp-farmasi',
            'role_slug' => 'pembimbing-dalam',
            'role_name' => 'Pembimbing Dalam',
            'is_active' => true,
        ]);
        UserAppAccess::create([
            'user_id' => $target->id,
            'app_code' => 'kp-farmasi',
            'role_slug' => 'pembimbing-dalam',
            'is_active' => true,
        ]);
        UserAppAccess::create([
            'user_id' => $target->id,
            'app_code' => 'kp-farmasi',
            'role_slug' => 'penguji',
            'is_active' => false,
        ]);
        UserAppAccess::create([
            'user_id' => $target->id,
            'app_code' => 'tu-farmasi',
            'role_slug' => 'admin-tu',
            'is_active' => true,
        ]);

        $this->assertFalse($inactiveApp->is_active);

        [$client, $secret] = $this->apiClient('kp-farmasi', ['read:app-access']);

        $this->withHeaders($this->clientHeaders($client, $secret))
            ->getJson("/api/v1/internal/apps/kp-farmasi/users/{$target->id}/access")
            ->assertOk()
            ->assertJson([
                'has_access' => true,
                'app_code' => 'kp-farmasi',
                'user_id' => $target->id,
                'roles' => [
                    ['slug' => 'pembimbing-dalam', 'name' => 'Pembimbing Dalam'],
                ],
            ]);

        $this->withHeaders($this->clientHeaders($client, $secret))
            ->getJson("/api/v1/internal/apps/tu-farmasi/users/{$target->id}/access")
            ->assertOk()
            ->assertJson([
                'has_access' => false,
                'app_code' => 'tu-farmasi',
                'user_id' => $target->id,
                'roles' => [],
            ]);
    }

    public function test_internal_endpoint_rejects_client_without_required_ability(): void
    {
        $target = User::factory()->create(['active' => true]);
        $this->application('kp-farmasi', true);
        [$client, $secret] = $this->apiClient('kp-farmasi', ['read:leadership']);

        $this->withHeaders($this->clientHeaders($client, $secret))
            ->getJson("/api/v1/internal/apps/kp-farmasi/users/{$target->id}/access")
            ->assertForbidden();
    }

    public function test_safe_user_and_profile_responses_do_not_expose_password_token_or_birth_date(): void
    {
        $admin = $this->coreAdmin();
        $department = Department::create(['code' => 'FAR', 'name' => 'Farmasi', 'active' => true]);
        $studyProgram = StudyProgram::create([
            'department_id' => $department->id,
            'code' => 'S1-FAR',
            'name' => 'S1 Farmasi',
            'active' => true,
        ]);
        $student = Student::create([
            'student_number' => '230001',
            'name' => 'Mahasiswa API',
            'email' => 'student-api@example.test',
            'birth_date' => '2001-08-07',
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
            'active' => true,
        ]);
        $lecturer = Lecturer::create([
            'lecturer_number' => 'L001',
            'name' => 'Dosen API',
            'email' => 'lecturer-api@example.test',
            'birth_date' => '1980-01-01',
            'department_id' => $department->id,
            'active' => true,
        ]);
        $employee = Employee::create([
            'employee_number' => 'E001',
            'name' => 'Staff API',
            'staff_type' => 'tendik',
            'email' => 'employee-api@example.test',
            'birth_date' => '1990-02-02',
            'status' => 'active',
        ]);

        $token = $admin->generateApiToken();

        foreach ([
            "/api/v1/users/{$admin->id}",
            "/api/v1/students/{$student->id}",
            "/api/v1/lecturers/{$lecturer->id}",
            "/api/v1/employees/{$employee->id}",
        ] as $uri) {
            $content = $this->withToken($token)
                ->getJson($uri)
                ->assertOk()
                ->content();

            $this->assertStringNotContainsString('password', $content);
            $this->assertStringNotContainsString('api_token', $content);
            $this->assertStringNotContainsString('remember_token', $content);
            $this->assertStringNotContainsString('birth_date', $content);
            $this->assertStringNotContainsString($admin->api_token, $content);
        }
    }

    public function test_current_leadership_endpoint_returns_safe_current_position(): void
    {
        $admin = $this->coreAdmin();
        $department = Department::create(['code' => 'FAR', 'name' => 'Farmasi', 'active' => true]);
        $lecturer = Lecturer::create([
            'lecturer_number' => 'D001',
            'name' => 'Dekan API',
            'email' => 'dekan-api@example.test',
            'birth_date' => '1970-01-01',
            'department_id' => $department->id,
            'active' => true,
        ]);
        LeadershipAssignment::create([
            'position_type' => 'dekan',
            'position_title' => 'Dekan Fakultas Farmasi',
            'unit_type' => 'faculty',
            'person_type' => 'lecturer',
            'person_id' => $lecturer->id,
            'start_date' => now()->subYear(),
            'is_active' => true,
        ]);

        $this->application('kp-farmasi', true);
        [$client, $secret] = $this->apiClient('kp-farmasi', ['read:leadership']);

        $response = $this->withHeaders($this->clientHeaders($client, $secret))
            ->getJson('/api/v1/internal/leadership/current?position_type=dekan&unit_type=faculty')
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('leadership.person_name', 'Dekan API');

        $content = $response->content();

        $this->assertStringNotContainsString('birth_date', $content);
        $this->assertStringNotContainsString('password', $content);
        $this->assertStringNotContainsString('api_token', $content);
    }

    public function test_app_client_user_directory_lists_safe_users_and_requires_ability(): void
    {
        $this->application('kp-farmasi', true);
        $target = User::factory()->create([
            'name' => 'Directory User',
            'email' => 'directory-user@example.test',
            'username' => 'directory-user',
            'identity_type' => 'employee',
            'identity_number' => 'ID-DIR-1',
            'active' => true,
        ]);
        $role = Role::create(['name' => 'admin-core', 'label' => 'Admin Core', 'active' => true]);
        $target->roles()->attach($role);

        [$allowedClient, $allowedSecret] = $this->apiClient('kp-farmasi', ['read:users']);
        [$deniedClient, $deniedSecret] = $this->apiClient('kp-farmasi', ['read:students']);

        $response = $this->withHeaders($this->clientHeaders($allowedClient, $allowedSecret))
            ->getJson('/api/v1/internal/directory/users?q=directory&limit=250')
            ->assertOk()
            ->assertJsonPath('meta.limit', 100)
            ->assertJsonPath('data.0.username', 'directory-user');

        $content = $response->content();

        $this->assertStringNotContainsString('password', $content);
        $this->assertStringNotContainsString('api_token', $content);
        $this->assertStringNotContainsString('remember_token', $content);
        $this->assertStringNotContainsString('secret', $content);
        $this->assertStringNotContainsString('birth_date', $content);

        $this->withHeaders($this->clientHeaders($deniedClient, $deniedSecret))
            ->getJson('/api/v1/internal/directory/users')
            ->assertForbidden()
            ->assertJson(['message' => 'Forbidden']);
    }

    public function test_app_client_profile_directories_return_safe_profile_data(): void
    {
        $this->application('kp-farmasi', true);
        $department = Department::create(['code' => 'FAR', 'name' => 'Farmasi', 'active' => true]);
        $studyProgram = StudyProgram::create([
            'department_id' => $department->id,
            'code' => 'S1-FAR',
            'name' => 'S1 Farmasi',
            'active' => true,
        ]);
        $student = Student::create([
            'student_number' => '240001',
            'name' => 'Mahasiswa Directory',
            'email' => 'student-directory@example.test',
            'birth_date' => '2002-01-01',
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
            'active' => true,
        ]);
        $lecturer = Lecturer::create([
            'lecturer_number' => 'L-DIR-1',
            'name' => 'Dosen Directory',
            'email' => 'lecturer-directory@example.test',
            'birth_date' => '1980-01-01',
            'department_id' => $department->id,
            'study_program_id' => $studyProgram->id,
            'active' => true,
        ]);
        $employee = Employee::create([
            'employee_number' => 'E-DIR-1',
            'national_id_number' => 'NIK-DIR-1',
            'name' => 'Pegawai Directory',
            'staff_type' => 'tendik',
            'department_id' => $department->id,
            'study_program_id' => $studyProgram->id,
            'position_title' => 'Admin',
            'email' => 'employee-directory@example.test',
            'birth_date' => '1990-01-01',
            'status' => 'active',
        ]);

        foreach ([
            ['read:students', "/api/v1/internal/directory/students?nim={$student->student_number}", 'data.0.nim', '240001'],
            ['read:lecturers', "/api/v1/internal/directory/lecturers?nidn={$lecturer->lecturer_number}", 'data.0.lecturer_number', 'L-DIR-1'],
            ['read:employees', "/api/v1/internal/directory/employees?employee_number={$employee->employee_number}", 'data.0.employee_number', 'E-DIR-1'],
            ['read:study-programs', '/api/v1/internal/directory/study-programs?q=S1', 'data.0.code', 'S1-FAR'],
            ['read:departments', '/api/v1/internal/directory/departments?q=Farmasi', 'data.0.code', 'FAR'],
        ] as [$ability, $uri, $jsonPath, $expected]) {
            [$client, $secret] = $this->apiClient('kp-farmasi', [$ability]);

            $content = $this->withHeaders($this->clientHeaders($client, $secret))
                ->getJson($uri)
                ->assertOk()
                ->assertJsonPath($jsonPath, $expected)
                ->content();

            $this->assertStringNotContainsString('birth_date', $content);
            $this->assertStringNotContainsString('password', $content);
            $this->assertStringNotContainsString('api_token', $content);
            $this->assertStringNotContainsString('secret', $content);
        }

        [$client, $secret] = $this->apiClient('kp-farmasi', ['read:students']);

        $this->withHeaders($this->clientHeaders($client, $secret))
            ->getJson("/api/v1/internal/directory/students/{$student->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $student->id);
    }

    public function test_directory_requests_are_audited_and_invalid_client_is_rejected(): void
    {
        $this->application('kp-farmasi', true);
        User::factory()->create(['active' => true]);
        [$client, $secret] = $this->apiClient('kp-farmasi', ['read:users']);

        $this->withHeaders($this->clientHeaders($client, $secret))
            ->getJson('/api/v1/internal/directory/users')
            ->assertOk();

        $this->assertDatabaseHas('core_api_request_logs', [
            'client_id' => $client->client_id,
            'app_code' => 'kp-farmasi',
            'path' => '/api/v1/internal/directory/users',
            'status_code' => 200,
            'ability' => 'read:users',
        ]);

        $this->withHeaders([
            'X-Core-Client-Id' => $client->client_id,
            'X-Core-Client-Secret' => 'wrong-secret',
            'X-Core-App-Code' => 'kp-farmasi',
        ])
            ->getJson('/api/v1/internal/directory/users')
            ->assertUnauthorized();

        $this->assertStringNotContainsString($secret, CoreApiRequestLog::where('path', '/api/v1/internal/directory/users')->get()->toJson());
    }

    public function test_api_routes_have_throttle_middleware_and_no_sso_routes(): void
    {
        $routes = collect(Route::getRoutes());
        $internalAccessRoute = $routes->first(fn ($route) => $route->uri() === 'api/v1/internal/apps/{app_code}/users/{user}/access');

        $this->assertNotNull($internalAccessRoute);
        $this->assertContains('auth.core-api-client:read:app-access', $internalAccessRoute->middleware());
        $this->assertContains('throttle:60,1', $internalAccessRoute->middleware());

        $studentDirectoryRoute = $routes->first(fn ($route) => $route->uri() === 'api/v1/internal/directory/students');

        $this->assertNotNull($studentDirectoryRoute);
        $this->assertContains('auth.core-api-client:read:students', $studentDirectoryRoute->middleware());
        $this->assertContains('throttle:60,1', $studentDirectoryRoute->middleware());

        $routeUris = $routes->map(fn ($route): string => $route->uri())->all();

        $this->assertNotContains('api/v1/sso', $routeUris);
        $this->assertNotContains('api/v1/internal/sso', $routeUris);
    }

    private function coreAdmin(): User
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create(['name' => 'super-admin', 'label' => 'Super Admin', 'active' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    private function application(string $appCode, bool $active): CoreApplication
    {
        return CoreApplication::create([
            'app_code' => $appCode,
            'name' => str($appCode)->headline()->toString(),
            'is_active' => $active,
            'is_public_visible' => false,
            'requires_login' => true,
            'is_sensitive' => false,
        ]);
    }

    private function apiClient(string $appCode, array $abilities): array
    {
        $service = app(CoreApiClientCredentialService::class);
        $secret = $service->generatePlainSecret();

        $client = CoreApiClient::create([
            'app_code' => $appCode,
            'name' => "{$appCode} client",
            'client_id' => $service->generateClientId($appCode),
            'secret_hash' => $service->hashSecret($secret),
            'abilities' => $abilities,
            'is_active' => true,
        ]);

        return [$client, $secret];
    }

    private function clientHeaders(CoreApiClient $client, string $secret): array
    {
        return [
            'X-Core-Client-Id' => $client->client_id,
            'X-Core-Client-Secret' => $secret,
            'X-Core-App-Code' => $client->app_code,
        ];
    }
}
