<?php

namespace Tests\Feature;

use App\Models\CoreApiClient;
use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Faculty;
use App\Models\LeadershipAssignment;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserAppAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CoreManualCrudResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_admin_can_access_manual_create_pages(): void
    {
        $admin = $this->createCoreAdmin('super-admin');

        foreach ($this->manualCrudResources() as $resource) {
            $this->actingAs($admin)
                ->get($resource['create'])
                ->assertOk()
                ->assertDontSee('secret_hash')
                ->assertDontSee('api_token')
                ->assertDontSee('remember_token');
        }
    }

    public function test_manual_crud_indexes_show_create_action_links_for_core_admin(): void
    {
        $admin = $this->createCoreAdmin('admin-core');
        $this->createEditableRecords();

        foreach ($this->manualCrudResources() as $resource) {
            $this->actingAs($admin)
                ->get($resource['index'])
                ->assertOk()
                ->assertSee($resource['create'], false);
        }
    }

    public function test_core_admin_can_access_manual_edit_pages(): void
    {
        $admin = $this->createCoreAdmin('super-admin');
        $records = $this->createEditableRecords();

        foreach ($this->manualCrudResources() as $resource) {
            $this->actingAs($admin)
                ->get(str_replace('{id}', (string) $records[$resource['key']], $resource['edit']))
                ->assertOk()
                ->assertDontSee('secret_hash')
                ->assertDontSee('api_token')
                ->assertDontSee('remember_token');
        }
    }

    public function test_logs_remain_read_only_without_create_routes(): void
    {
        $routeUris = collect(Route::getRoutes())
            ->map(fn ($route): string => $route->uri())
            ->all();

        $this->assertContains('admin/user-activity-logs', $routeUris);
        $this->assertContains('admin/core-api-request-logs', $routeUris);
        $this->assertNotContains('admin/user-activity-logs/create', $routeUris);
        $this->assertNotContains('admin/core-api-request-logs/create', $routeUris);
    }

    public function test_non_admin_and_guest_cannot_access_manual_create_pages(): void
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create(['name' => 'mahasiswa', 'label' => 'Mahasiswa', 'active' => true]);
        $user->roles()->attach($role);

        foreach ($this->manualCrudResources() as $resource) {
            $this->actingAs($user)
                ->get($resource['create'])
                ->assertForbidden();
        }

        auth()->logout();

        $this->get('/admin/users/create')->assertRedirect('/admin/login');
    }

    /**
     * @return array<int, array{key: string, index: string, create: string, edit: string}>
     */
    private function manualCrudResources(): array
    {
        return [
            ['key' => 'user', 'index' => '/admin/users', 'create' => '/admin/users/create', 'edit' => '/admin/users/{id}/edit'],
            ['key' => 'role', 'index' => '/admin/roles', 'create' => '/admin/roles/create', 'edit' => '/admin/roles/{id}/edit'],
            ['key' => 'student', 'index' => '/admin/students', 'create' => '/admin/students/create', 'edit' => '/admin/students/{id}/edit'],
            ['key' => 'lecturer', 'index' => '/admin/lecturers', 'create' => '/admin/lecturers/create', 'edit' => '/admin/lecturers/{id}/edit'],
            ['key' => 'employee', 'index' => '/admin/employees', 'create' => '/admin/employees/create', 'edit' => '/admin/employees/{id}/edit'],
            ['key' => 'faculty', 'index' => '/admin/faculties', 'create' => '/admin/faculties/create', 'edit' => '/admin/faculties/{id}/edit'],
            ['key' => 'department', 'index' => '/admin/departments', 'create' => '/admin/departments/create', 'edit' => '/admin/departments/{id}/edit'],
            ['key' => 'study_program', 'index' => '/admin/study-programs', 'create' => '/admin/study-programs/create', 'edit' => '/admin/study-programs/{id}/edit'],
            ['key' => 'core_application', 'index' => '/admin/core-applications', 'create' => '/admin/core-applications/create', 'edit' => '/admin/core-applications/{id}/edit'],
            ['key' => 'core_application_role', 'index' => '/admin/core-application-roles', 'create' => '/admin/core-application-roles/create', 'edit' => '/admin/core-application-roles/{id}/edit'],
            ['key' => 'user_app_access', 'index' => '/admin/user-app-accesses', 'create' => '/admin/user-app-accesses/create', 'edit' => '/admin/user-app-accesses/{id}/edit'],
            ['key' => 'leadership_assignment', 'index' => '/admin/leadership-assignments', 'create' => '/admin/leadership-assignments/create', 'edit' => '/admin/leadership-assignments/{id}/edit'],
            ['key' => 'core_api_client', 'index' => '/admin/core-api-clients', 'create' => '/admin/core-api-clients/create', 'edit' => '/admin/core-api-clients/{id}/edit'],
        ];
    }

    private function createCoreAdmin(string $roleName): User
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['label' => str($roleName)->headline()->toString(), 'active' => true],
        );

        $user->roles()->attach($role);

        return $user;
    }

    /**
     * @return array<string, int>
     */
    private function createEditableRecords(): array
    {
        $faculty = Faculty::create([
            'code' => 'CRUD-FAC',
            'name' => 'CRUD Faculty',
            'active' => true,
        ]);

        $department = Department::create([
            'faculty_id' => $faculty->id,
            'code' => 'CRUD-DEP',
            'name' => 'CRUD Department',
            'active' => true,
        ]);

        $studyProgram = StudyProgram::create([
            'faculty_id' => $faculty->id,
            'department_id' => $department->id,
            'code' => 'CRUD-SP',
            'name' => 'CRUD Study Program',
            'active' => true,
        ]);

        $user = User::factory()->create(['active' => true]);
        $role = Role::create(['name' => 'crud-role', 'label' => 'CRUD Role', 'active' => true]);

        $student = Student::create([
            'user_id' => $user->id,
            'student_number' => 'CRUD-STU-001',
            'name' => 'CRUD Student',
            'email' => 'crud.student@example.test',
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
            'active' => true,
        ]);

        $lecturer = Lecturer::create([
            'user_id' => $user->id,
            'lecturer_number' => 'CRUD-LEC-001',
            'name' => 'CRUD Lecturer',
            'email' => 'crud.lecturer@example.test',
            'department_id' => $department->id,
            'study_program_id' => $studyProgram->id,
            'active' => true,
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_number' => 'CRUD-EMP-001',
            'name' => 'CRUD Employee',
            'staff_type' => 'staf_tu',
            'department_id' => $department->id,
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
        ]);

        $application = CoreApplication::create([
            'app_code' => 'crud-app',
            'name' => 'CRUD App',
            'is_active' => true,
            'is_public_visible' => false,
            'requires_login' => true,
        ]);

        $applicationRole = CoreApplicationRole::create([
            'core_application_id' => $application->id,
            'app_code' => 'crud-app',
            'role_slug' => 'crud-role',
            'role_name' => 'CRUD Role',
            'is_active' => true,
        ]);

        $userAppAccess = UserAppAccess::create([
            'user_id' => $user->id,
            'app_code' => 'crud-app',
            'role_slug' => 'crud-role',
            'is_active' => true,
        ]);

        $leadershipAssignment = LeadershipAssignment::create([
            'position_type' => 'kaprodi',
            'position_title' => 'Kaprodi CRUD',
            'unit_type' => 'study_program',
            'unit_id' => $studyProgram->id,
            'person_type' => 'lecturer',
            'person_id' => $lecturer->id,
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $apiClient = CoreApiClient::create([
            'core_application_id' => $application->id,
            'app_code' => 'crud-app',
            'name' => 'CRUD API Client',
            'client_id' => 'crud-client',
            'secret_hash' => 'not-rendered-secret-hash',
            'abilities' => ['read:directory'],
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        return [
            'user' => $user->id,
            'role' => $role->id,
            'student' => $student->id,
            'lecturer' => $lecturer->id,
            'employee' => $employee->id,
            'faculty' => $faculty->id,
            'department' => $department->id,
            'study_program' => $studyProgram->id,
            'core_application' => $application->id,
            'core_application_role' => $applicationRole->id,
            'user_app_access' => $userAppAccess->id,
            'leadership_assignment' => $leadershipAssignment->id,
            'core_api_client' => $apiClient->id,
        ];
    }
}
