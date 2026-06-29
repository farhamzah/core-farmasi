<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeadershipAssignment;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\CoreLeadershipResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LeadershipAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_leadership_assignments_table_exists_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('leadership_assignments'));

        foreach ([
            'position_type',
            'position_title',
            'unit_type',
            'unit_id',
            'person_type',
            'person_id',
            'title_prefix',
            'title_suffix',
            'official_name_snapshot',
            'decree_number',
            'start_date',
            'end_date',
            'is_active',
            'notes',
            'deleted_at',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('leadership_assignments', $column), "Missing leadership_assignments.{$column}");
        }
    }

    public function test_leadership_assignment_model_casts_and_person_resolution_work(): void
    {
        $lecturer = $this->createLecturer('Dekan');
        $lecturer->forceFill([
            'front_title' => 'Dr.',
            'back_title' => 'M.Farm.',
        ])->save();

        $assignment = LeadershipAssignment::create([
            'position_type' => 'dekan',
            'unit_type' => 'faculty',
            'person_type' => 'lecturer',
            'person_id' => $lecturer->id,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        $this->assertTrue($assignment->is_active);
        $this->assertSame(now()->subMonth()->toDateString(), $assignment->start_date->toDateString());
        $this->assertSame(now()->addMonth()->toDateString(), $assignment->end_date->toDateString());
        $this->assertTrue($assignment->person->is($lecturer));
        $this->assertSame('Dr. Dekan, M.Farm.', $assignment->person_display_name);
    }

    public function test_official_name_snapshot_overrides_current_profile_title_for_historical_documents(): void
    {
        $lecturer = $this->createLecturer('Dekan Historis');
        $lecturer->forceFill([
            'front_title' => 'Prof.',
            'back_title' => 'Ph.D.',
        ])->save();

        $assignment = LeadershipAssignment::create([
            'position_type' => 'dekan',
            'unit_type' => 'faculty',
            'person_type' => 'lecturer',
            'person_id' => $lecturer->id,
            'official_name_snapshot' => 'Dr. Dekan Historis, M.Si.',
            'start_date' => now()->subMonth(),
            'is_active' => true,
        ]);

        $this->assertSame('Dr. Dekan Historis, M.Si.', $assignment->person_display_name);
    }

    public function test_resolver_returns_current_active_dean(): void
    {
        $lecturer = $this->createLecturer('Dekan Aktif');

        $assignment = LeadershipAssignment::create([
            'position_type' => 'dekan',
            'position_title' => 'Dekan Fakultas Farmasi',
            'unit_type' => 'faculty',
            'person_type' => 'lecturer',
            'person_id' => $lecturer->id,
            'start_date' => now()->subDay(),
            'is_active' => true,
        ]);

        $resolved = app(CoreLeadershipResolver::class)->getCurrentDean();

        $this->assertTrue($resolved->is($assignment));
    }

    public function test_resolver_returns_current_head_of_study_program(): void
    {
        $department = $this->createDepartment();
        $studyProgram = StudyProgram::create([
            'department_id' => $department->id,
            'code' => 'S1-FARMASI',
            'name' => 'S1 Farmasi',
            'active' => true,
        ]);
        $lecturer = $this->createLecturer('Kaprodi Aktif', $department->id, $studyProgram->id);

        $assignment = LeadershipAssignment::create([
            'position_type' => 'kaprodi',
            'position_title' => 'Kaprodi S1 Farmasi',
            'unit_type' => 'study_program',
            'unit_id' => $studyProgram->id,
            'person_type' => 'lecturer',
            'person_id' => $lecturer->id,
            'start_date' => now()->subDay(),
            'is_active' => true,
        ]);

        $resolved = app(CoreLeadershipResolver::class)->getCurrentHeadOfStudyProgram($studyProgram->id);

        $this->assertTrue($resolved->is($assignment));
        $this->assertSame('S1 Farmasi', $resolved->unit_label);
    }

    public function test_resolver_ignores_inactive_and_expired_assignments(): void
    {
        $inactiveLecturer = $this->createLecturer('Dekan Nonaktif');
        $expiredLecturer = $this->createLecturer('Dekan Expired');
        $activeLecturer = $this->createLecturer('Dekan Berlaku');

        LeadershipAssignment::create([
            'position_type' => 'dekan',
            'unit_type' => 'faculty',
            'person_type' => 'lecturer',
            'person_id' => $inactiveLecturer->id,
            'start_date' => now()->subYear(),
            'is_active' => false,
        ]);
        LeadershipAssignment::create([
            'position_type' => 'dekan',
            'unit_type' => 'faculty',
            'person_type' => 'lecturer',
            'person_id' => $expiredLecturer->id,
            'start_date' => now()->subYear(),
            'end_date' => now()->subDay(),
            'is_active' => true,
        ]);
        $active = LeadershipAssignment::create([
            'position_type' => 'dekan',
            'unit_type' => 'faculty',
            'person_type' => 'lecturer',
            'person_id' => $activeLecturer->id,
            'start_date' => now()->subDay(),
            'is_active' => true,
        ]);

        $resolved = app(CoreLeadershipResolver::class)->getCurrentDean();

        $this->assertTrue($resolved->is($active));
    }

    public function test_resolver_prefers_latest_start_date_when_multiple_current_assignments_exist(): void
    {
        $oldLecturer = $this->createLecturer('Dekan Lama');
        $newLecturer = $this->createLecturer('Dekan Baru');

        LeadershipAssignment::create([
            'position_type' => 'dekan',
            'unit_type' => 'faculty',
            'person_type' => 'lecturer',
            'person_id' => $oldLecturer->id,
            'start_date' => now()->subMonths(3),
            'is_active' => true,
        ]);
        $latest = LeadershipAssignment::create([
            'position_type' => 'dekan',
            'unit_type' => 'faculty',
            'person_type' => 'lecturer',
            'person_id' => $newLecturer->id,
            'start_date' => now()->subMonth(),
            'is_active' => true,
        ]);

        $resolved = app(CoreLeadershipResolver::class)->getCurrentDean();

        $this->assertTrue($resolved->is($latest));
    }

    public function test_super_admin_can_open_leadership_assignment_resource_pages(): void
    {
        $user = $this->createCoreAdmin('super-admin');
        $assignment = LeadershipAssignment::create([
            'position_type' => 'dekan',
            'unit_type' => 'faculty',
            'person_type' => 'lecturer',
            'person_id' => $this->createLecturer('Editable Dekan')->id,
            'start_date' => now()->subDay(),
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/admin/leadership-assignments')
            ->assertOk();

        $this->actingAs($user)
            ->get('/admin/leadership-assignments/create')
            ->assertOk()
            ->assertSee('Pilih Unit')
            ->assertSee('Pilih Pejabat')
            ->assertSee('Arsip SK Khusus')
            ->assertDontSee('ID Pejabat')
            ->assertDontSee('Isi ID Dosen');

        $this->actingAs($user)
            ->get("/admin/leadership-assignments/{$assignment->id}/edit")
            ->assertOk();
    }

    public function test_guest_is_redirected_from_leadership_assignment_resource(): void
    {
        $this->get('/admin/leadership-assignments')
            ->assertRedirect('/admin/login');
    }

    public function test_user_without_core_admin_role_cannot_open_leadership_assignment_resource(): void
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create(['name' => 'mahasiswa', 'label' => 'Mahasiswa', 'active' => true]);
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get('/admin/leadership-assignments')
            ->assertForbidden();
    }

    public function test_resource_access_does_not_mutate_master_data_counts(): void
    {
        $user = $this->createCoreAdmin('super-admin');

        $users = User::count();
        $lecturers = Lecturer::count();
        $employees = Employee::count();

        $this->actingAs($user)
            ->get('/admin/leadership-assignments')
            ->assertOk();

        $this->assertSame($users, User::count());
        $this->assertSame($lecturers, Lecturer::count());
        $this->assertSame($employees, Employee::count());
    }

    private function createCoreAdmin(string $roleName): User
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create([
            'name' => $roleName,
            'label' => str($roleName)->headline()->toString(),
            'active' => true,
        ]);

        $user->roles()->attach($role);

        return $user;
    }

    private function createDepartment(): Department
    {
        return Department::create([
            'code' => 'FF-'.strtoupper(str()->random(6)),
            'name' => 'Fakultas Farmasi',
            'active' => true,
        ]);
    }

    private function createLecturer(string $name, ?int $departmentId = null, ?int $studyProgramId = null): Lecturer
    {
        $departmentId ??= $this->createDepartment()->id;

        return Lecturer::create([
            'lecturer_number' => 'L-'.str()->random(8),
            'name' => $name,
            'email' => str($name)->slug().'-'.str()->random(6).'@example.test',
            'department_id' => $departmentId,
            'study_program_id' => $studyProgramId,
            'active' => true,
        ]);
    }
}
