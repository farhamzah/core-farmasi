<?php

namespace Tests\Feature;

use App\Filament\Pages\BulkUserAppAccess;
use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserAppAccess;
use App\Services\BulkUserAppAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BulkUserAppAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_admin_can_open_bulk_user_app_access_page(): void
    {
        $admin = $this->createCoreAdmin();

        $this->actingAs($admin)
            ->get('/admin/bulk-user-app-access')
            ->assertOk()
            ->assertSee('Pemberian Akses Kolektif');
    }

    public function test_bulk_user_app_access_page_is_admin_only(): void
    {
        $this->get('/admin/bulk-user-app-access')->assertRedirect('/admin/login');

        $user = User::factory()->create(['active' => true]);
        $role = Role::create([
            'name' => 'mahasiswa',
            'label' => 'Mahasiswa',
            'active' => true,
        ]);
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get('/admin/bulk-user-app-access')
            ->assertForbidden();
    }

    public function test_bulk_preview_can_target_students_by_nim_prefix(): void
    {
        $this->createApplicationRole('kp-farmasi', 'mahasiswa');
        $studyProgram = $this->createStudyProgram();
        $matching = $this->createStudentUser('2411110001', $studyProgram->id);
        $this->createStudentUser('2311110001', $studyProgram->id);
        $this->createStudentUser('2411110002', $studyProgram->id, active: false);

        $preview = app(BulkUserAppAccessService::class)->preview([
            'app_code' => 'kp-farmasi',
            'role_slug' => 'mahasiswa',
            'target_scope' => 'student_nim_prefix',
            'target_value' => '24',
        ]);

        $this->assertSame([], $preview['blockers']);
        $this->assertSame(1, $preview['counts']['matched_users']);
        $this->assertSame(1, $preview['counts']['planned_insert']);
        $this->assertSame($matching->id, $preview['samples'][0]['user_id']);
    }

    public function test_bulk_preview_can_target_lecturers_by_nidn_prefix(): void
    {
        $this->createApplicationRole('kp-farmasi', 'pembimbing-dalam');
        $matching = $this->createLecturerUser('0430037804');
        $this->createLecturerUser('0520019901');

        $preview = app(BulkUserAppAccessService::class)->preview([
            'app_code' => 'kp-farmasi',
            'role_slug' => 'pembimbing-dalam',
            'target_scope' => 'lecturer_nidn_prefix',
            'target_value' => '0430',
        ]);

        $this->assertSame([], $preview['blockers']);
        $this->assertSame(1, $preview['counts']['matched_users']);
        $this->assertSame(1, $preview['counts']['planned_insert']);
        $this->assertSame($matching->id, $preview['samples'][0]['user_id']);
    }

    public function test_bulk_preview_can_target_employees_by_staff_type(): void
    {
        $this->createApplicationRole('tu-farmasi', 'laboran');
        $matching = $this->createEmployeeUser('laboran');
        $this->createEmployeeUser('tendik');

        $preview = app(BulkUserAppAccessService::class)->preview([
            'app_code' => 'tu-farmasi',
            'role_slug' => 'laboran',
            'target_scope' => 'employee_staff_type',
            'target_value' => 'laboran',
        ]);

        $this->assertSame([], $preview['blockers']);
        $this->assertSame(1, $preview['counts']['matched_users']);
        $this->assertSame(1, $preview['counts']['planned_insert']);
        $this->assertSame($matching->id, $preview['samples'][0]['user_id']);
    }

    public function test_bulk_apply_is_idempotent_and_skips_existing_access(): void
    {
        $this->createApplicationRole('kp-farmasi', 'mahasiswa');
        $first = User::factory()->create(['active' => true, 'identity_type' => 'student']);
        $second = User::factory()->create(['active' => true, 'identity_type' => 'student']);

        UserAppAccess::create([
            'user_id' => $first->id,
            'app_code' => 'kp-farmasi',
            'role_slug' => 'mahasiswa',
            'is_active' => true,
            'activated_at' => now(),
        ]);

        $service = app(BulkUserAppAccessService::class);

        $firstApply = $service->apply([
            'app_code' => 'kp-farmasi',
            'role_slug' => 'mahasiswa',
            'target_scope' => 'identity_type',
            'target_value' => 'student',
        ]);

        $this->assertTrue($firstApply['applied']);
        $this->assertSame(1, $firstApply['created']);
        $this->assertSame(0, $firstApply['reactivated']);
        $this->assertSame(2, UserAppAccess::where('app_code', 'kp-farmasi')->where('role_slug', 'mahasiswa')->count());
        $this->assertTrue(UserAppAccess::where('user_id', $second->id)->where('app_code', 'kp-farmasi')->where('role_slug', 'mahasiswa')->exists());

        $secondApply = $service->apply([
            'app_code' => 'kp-farmasi',
            'role_slug' => 'mahasiswa',
            'target_scope' => 'identity_type',
            'target_value' => 'student',
        ]);

        $this->assertFalse($secondApply['applied']);
        $this->assertSame(2, UserAppAccess::where('app_code', 'kp-farmasi')->where('role_slug', 'mahasiswa')->count());
    }

    public function test_bulk_apply_can_reactivate_existing_inactive_access(): void
    {
        $this->createApplicationRole('ta-farmasi', 'admin-ta');
        $user = User::factory()->create(['active' => true, 'identity_type' => 'lecturer']);

        $access = UserAppAccess::create([
            'user_id' => $user->id,
            'app_code' => 'ta-farmasi',
            'role_slug' => 'admin-ta',
            'is_active' => false,
            'activated_at' => now()->subMonth(),
            'deactivated_at' => now()->subDay(),
        ]);

        $result = app(BulkUserAppAccessService::class)->apply([
            'app_code' => 'ta-farmasi',
            'role_slug' => 'admin-ta',
            'target_scope' => 'identity_type',
            'target_value' => 'lecturer',
            'reactivate_existing' => true,
        ]);

        $this->assertTrue($result['applied']);
        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['reactivated']);
        $this->assertTrue($access->fresh()->is_active);
        $this->assertNull($access->fresh()->deactivated_at);
    }

    public function test_bulk_page_livewire_can_preview_without_writing_access(): void
    {
        $this->createApplicationRole('kp-farmasi', 'mahasiswa');
        User::factory()->create(['active' => true, 'identity_type' => 'student']);
        $admin = $this->createCoreAdmin();

        $this->actingAs($admin);

        Livewire::test(BulkUserAppAccess::class)
            ->set('appCode', 'kp-farmasi')
            ->set('roleSlug', 'mahasiswa')
            ->set('targetScope', 'identity_type')
            ->set('targetValue', 'student')
            ->call('preview')
            ->assertSet('previewResult.counts.planned_insert', 1);

        $this->assertSame(0, UserAppAccess::count());
    }

    private function createApplicationRole(string $appCode, string $roleSlug): CoreApplicationRole
    {
        $application = CoreApplication::create([
            'app_code' => $appCode,
            'name' => str($appCode)->replace('-', ' ')->title()->toString(),
            'is_active' => true,
            'requires_login' => true,
        ]);

        return CoreApplicationRole::create([
            'core_application_id' => $application->id,
            'app_code' => $appCode,
            'role_slug' => $roleSlug,
            'role_name' => str($roleSlug)->replace('-', ' ')->title()->toString(),
            'is_active' => true,
        ]);
    }

    private function createStudyProgram(): StudyProgram
    {
        $department = Department::create([
            'code' => 'FF',
            'name' => 'Fakultas Farmasi',
            'active' => true,
        ]);

        return StudyProgram::create([
            'department_id' => $department->id,
            'code' => 'S1-FAR',
            'name' => 'Farmasi S1',
            'active' => true,
        ]);
    }

    private function createStudentUser(string $nim, int $studyProgramId, bool $active = true): User
    {
        $user = User::factory()->create([
            'active' => $active,
            'identity_type' => 'student',
            'identity_number' => $nim,
        ]);

        Student::create([
            'user_id' => $user->id,
            'student_number' => $nim,
            'name' => $user->name,
            'email' => $user->email,
            'study_program_id' => $studyProgramId,
            'status' => 'active',
            'active' => $active,
        ]);

        return $user;
    }

    private function createLecturerUser(string $nidn, bool $active = true): User
    {
        $department = Department::create([
            'code' => 'DEP'.$nidn,
            'name' => 'Departemen '.$nidn,
            'active' => true,
        ]);

        $user = User::factory()->create([
            'active' => $active,
            'identity_type' => 'lecturer',
            'identity_number' => $nidn,
        ]);

        Lecturer::create([
            'user_id' => $user->id,
            'lecturer_number' => $nidn,
            'nidn' => $nidn,
            'name' => $user->name,
            'email' => $user->email,
            'department_id' => $department->id,
            'active' => $active,
        ]);

        return $user;
    }

    private function createEmployeeUser(string $staffType, bool $active = true): User
    {
        $user = User::factory()->create([
            'active' => $active,
            'identity_type' => 'employee',
            'identity_number' => fake()->unique()->numerify('EMP####'),
        ]);

        Employee::create([
            'user_id' => $user->id,
            'employee_number' => $user->identity_number,
            'name' => $user->name,
            'email' => $user->email,
            'staff_type' => $staffType,
            'status' => $active ? 'active' : 'inactive',
        ]);

        return $user;
    }

    private function createCoreAdmin(): User
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create([
            'name' => 'admin-core',
            'label' => 'Admin Core',
            'active' => true,
        ]);

        $user->roles()->attach($role);

        return $user;
    }
}
