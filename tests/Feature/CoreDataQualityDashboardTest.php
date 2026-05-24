<?php

namespace Tests\Feature;

use App\Models\CoreApplication;
use App\Models\CoreImportBatch;
use App\Models\CoreImportRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeadershipAssignment;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserAppAccess;
use App\Services\CoreDataQualityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreDataQualityDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_admin_can_open_data_quality_dashboard(): void
    {
        $this->actingAs($this->coreAdmin())
            ->get('/admin/data-quality')
            ->assertOk()
            ->assertSee('Data Quality Dashboard');
    }

    public function test_guest_is_redirected_from_data_quality_dashboard(): void
    {
        $this->get('/admin/data-quality')
            ->assertRedirect('/admin/login');
    }

    public function test_unauthorized_user_cannot_open_data_quality_dashboard(): void
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create(['name' => 'mahasiswa', 'label' => 'Mahasiswa', 'active' => true]);
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get('/admin/data-quality')
            ->assertForbidden();
    }

    public function test_data_quality_service_counts_identity_and_profile_issues(): void
    {
        $department = Department::create(['code' => 'FAR', 'name' => 'Farmasi', 'active' => true]);
        $studyProgram = StudyProgram::create([
            'department_id' => $department->id,
            'code' => 'S1-FAR',
            'name' => 'S1 Farmasi',
            'active' => true,
        ]);
        $userWithoutRole = User::factory()->create([
            'active' => true,
            'username' => null,
            'identity_number' => null,
        ]);
        $inactiveUser = User::factory()->create(['active' => false]);
        UserAppAccess::create([
            'user_id' => $inactiveUser->id,
            'app_code' => 'unknown-app',
            'role_slug' => 'unknown-role',
            'is_active' => true,
        ]);
        Student::create([
            'student_number' => '230001',
            'name' => 'Student Missing User',
            'email' => 'student-missing-user@example.test',
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
            'active' => true,
        ]);
        Lecturer::create([
            'lecturer_number' => 'L001',
            'name' => 'Lecturer Missing Birth Date',
            'email' => 'lecturer-missing-birth@example.test',
            'department_id' => $department->id,
            'active' => true,
        ]);
        Employee::create([
            'employee_number' => 'E001',
            'name' => 'Employee Missing User',
            'staff_type' => 'tendik',
            'status' => 'active',
        ]);

        $summary = app(CoreDataQualityService::class)->summary();

        $this->assertGreaterThanOrEqual(1, $summary['identity']['metrics']['users_without_roles']);
        $this->assertGreaterThanOrEqual(1, $summary['identity']['metrics']['users_missing_username']);
        $this->assertGreaterThanOrEqual(1, $summary['identity']['metrics']['users_missing_identity_number']);
        $this->assertSame(1, $summary['identity']['metrics']['inactive_users_with_active_app_access']);
        $this->assertSame(1, $summary['profiles']['metrics']['students_without_user']);
        $this->assertSame(1, $summary['profiles']['metrics']['lecturers_without_birth_date']);
        $this->assertSame(1, $summary['profiles']['metrics']['employees_without_user']);
        $this->assertSame(1, $summary['app_access']['metrics']['app_accesses_with_unknown_app_code']);
        $this->assertSame(1, $summary['app_access']['metrics']['app_accesses_with_unknown_role_slug']);
    }

    public function test_data_quality_service_counts_core_public_warning_and_leadership_issues(): void
    {
        $department = Department::create(['code' => 'FAR', 'name' => 'Farmasi', 'active' => true]);
        $lecturerA = Lecturer::create([
            'lecturer_number' => 'D001',
            'name' => 'Dekan A',
            'email' => 'dekan-a@example.test',
            'department_id' => $department->id,
            'birth_date' => '1980-01-01',
            'active' => true,
        ]);
        $lecturerB = Lecturer::create([
            'lecturer_number' => 'D002',
            'name' => 'Dekan B',
            'email' => 'dekan-b@example.test',
            'department_id' => $department->id,
            'birth_date' => '1981-01-01',
            'active' => true,
        ]);
        CoreApplication::create([
            'app_code' => 'core-farmasi',
            'name' => 'Core Farmasi',
            'is_active' => true,
            'is_public_visible' => true,
            'requires_login' => true,
            'is_sensitive' => true,
        ]);
        LeadershipAssignment::create([
            'position_type' => 'dekan',
            'unit_type' => 'faculty',
            'person_type' => 'lecturer',
            'person_id' => $lecturerA->id,
            'start_date' => now()->subDay(),
            'is_active' => true,
        ]);
        LeadershipAssignment::create([
            'position_type' => 'dekan',
            'unit_type' => 'faculty',
            'person_type' => 'lecturer',
            'person_id' => $lecturerB->id,
            'start_date' => now()->subDay(),
            'is_active' => true,
        ]);
        LeadershipAssignment::create([
            'position_type' => 'kaprodi',
            'unit_type' => 'study_program',
            'person_type' => 'lecturer',
            'person_id' => 999999,
            'start_date' => now()->subYear(),
            'end_date' => now()->subDay(),
            'is_active' => true,
        ]);

        $summary = app(CoreDataQualityService::class)->summary();

        $this->assertSame(1, $summary['app_access']['metrics']['core_public_visible_warning']);
        $this->assertSame(1, $summary['app_access']['metrics']['public_visible_sensitive_apps']);
        $this->assertSame(1, $summary['leadership']['metrics']['current_dean_exists']);
        $this->assertSame(1, $summary['leadership']['metrics']['multiple_current_deans']);
        $this->assertSame(1, $summary['leadership']['metrics']['leadership_assignments_expired_but_active']);
        $this->assertSame(1, $summary['leadership']['metrics']['leadership_assignments_without_valid_person']);
    }

    public function test_data_quality_service_counts_import_issues(): void
    {
        CoreImportBatch::create([
            'source' => 'students',
            'mode' => 'validation',
            'status' => 'failed',
        ]);
        CoreImportBatch::create([
            'source' => 'employees',
            'mode' => 'validation',
            'status' => 'partially_failed',
            'rollback_status' => 'manual_review',
        ]);
        $batch = CoreImportBatch::create([
            'source' => 'lecturers',
            'mode' => 'validation',
            'status' => 'executed',
        ]);
        CoreImportRecord::create([
            'core_import_batch_id' => $batch->id,
            'source_table' => 'lecturers',
            'target_table' => 'lecturers',
            'action' => 'updated',
            'rollback_status' => 'manual_review',
        ]);

        $summary = app(CoreDataQualityService::class)->summary();

        $this->assertSame(3, $summary['imports']['metrics']['import_batches_total']);
        $this->assertSame(1, $summary['imports']['metrics']['import_batches_failed']);
        $this->assertSame(1, $summary['imports']['metrics']['import_batches_partially_failed']);
        $this->assertSame(1, $summary['imports']['metrics']['import_batches_manual_review']);
        $this->assertSame(1, $summary['imports']['metrics']['rollback_manual_review_count']);
        $this->assertCount(3, $summary['imports']['examples']['recent_import_batches']);
    }

    public function test_data_quality_dashboard_does_not_mutate_master_data_or_expose_password_hashes(): void
    {
        $user = $this->coreAdmin();
        $counts = [
            'users' => User::count(),
            'students' => Student::count(),
            'lecturers' => Lecturer::count(),
            'employees' => Employee::count(),
            'app_accesses' => UserAppAccess::count(),
        ];

        $summary = app(CoreDataQualityService::class)->summary();
        $this->actingAs($user)->get('/admin/data-quality')->assertOk();

        $this->assertSame($counts['users'], User::count());
        $this->assertSame($counts['students'], Student::count());
        $this->assertSame($counts['lecturers'], Lecturer::count());
        $this->assertSame($counts['employees'], Employee::count());
        $this->assertSame($counts['app_accesses'], UserAppAccess::count());

        $encoded = json_encode($summary);
        $this->assertStringNotContainsString('secret', $encoded);
        $this->assertStringNotContainsString($user->password, $encoded);
    }

    private function coreAdmin(): User
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create(['name' => 'super-admin', 'label' => 'Super Admin', 'active' => true]);
        $user->roles()->attach($role);

        return $user;
    }
}
