<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreProfileUserLinkReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reconcile_command_dry_run_reports_wrong_profile_link_without_writing(): void
    {
        [$wrongUser, $rightUser, $department] = $this->usersAndDepartment();

        $lecturer = Lecturer::create([
            'user_id' => $wrongUser->id,
            'lecturer_number' => '0430037804',
            'name' => 'Farhamzah',
            'email' => $rightUser->email,
            'department_id' => $department->id,
            'active' => true,
        ]);

        $this->artisan('core:reconcile-profile-user-links', [
            '--only' => 'lecturers',
            '--email' => $rightUser->email,
            '--backfill-identifiers' => true,
        ])
            ->expectsOutputToContain('profile_email_matches_different_user')
            ->expectsOutputToContain('relink')
            ->assertSuccessful();

        $lecturer->refresh();

        $this->assertSame($wrongUser->id, $lecturer->user_id);
        $this->assertNull($lecturer->nidn);
    }

    public function test_reconcile_command_apply_relinks_to_canonical_email_user_and_backfills_nidn(): void
    {
        [$wrongUser, $rightUser, $department] = $this->usersAndDepartment();

        $lecturer = Lecturer::create([
            'user_id' => $wrongUser->id,
            'lecturer_number' => '0430037804',
            'name' => 'Farhamzah',
            'email' => $rightUser->email,
            'department_id' => $department->id,
            'active' => true,
        ]);

        $this->artisan('core:reconcile-profile-user-links', [
            '--apply' => true,
            '--only' => 'lecturers',
            '--email' => $rightUser->email,
            '--backfill-identifiers' => true,
        ])
            ->expectsOutputToContain('profile_email_matches_different_user')
            ->assertSuccessful();

        $lecturer->refresh();

        $this->assertSame($rightUser->id, $lecturer->user_id);
        $this->assertSame('0430037804', $lecturer->nidn);
        $this->assertDatabaseHas('user_activity_logs', [
            'user_id' => $rightUser->id,
            'action' => 'profile.user_link_reconciled',
        ]);
    }

    public function test_reconcile_blocks_when_target_user_already_has_same_profile_type(): void
    {
        [$wrongUser, $rightUser, $department, $studyProgram] = $this->usersAndDepartment();

        Lecturer::create([
            'user_id' => $rightUser->id,
            'lecturer_number' => '1111111111',
            'name' => 'Existing Lecturer',
            'email' => 'existing-lecturer@example.test',
            'department_id' => $department->id,
            'study_program_id' => $studyProgram->id,
            'active' => true,
        ]);

        Lecturer::create([
            'user_id' => $wrongUser->id,
            'lecturer_number' => '2222222222',
            'name' => 'Conflicting Lecturer',
            'email' => $rightUser->email,
            'department_id' => $department->id,
            'study_program_id' => $studyProgram->id,
            'active' => true,
        ]);

        $this->artisan('core:reconcile-profile-user-links', [
            '--only' => 'lecturers',
            '--email' => $rightUser->email,
        ])
            ->expectsOutputToContain('target_user_already_has_profile')
            ->assertFailed();
    }

    public function test_reconcile_can_link_unlinked_student_to_matching_user(): void
    {
        $user = User::factory()->create([
            'email' => 'student-link@example.test',
            'username' => '230001',
            'identity_type' => 'student',
            'identity_number' => '230001',
            'active' => true,
        ]);

        $department = Department::create([
            'code' => 'FAR',
            'name' => 'Farmasi',
            'active' => true,
        ]);

        $studyProgram = StudyProgram::create([
            'department_id' => $department->id,
            'code' => 'S1-FAR',
            'name' => 'S1 Farmasi',
            'active' => true,
        ]);

        $student = Student::create([
            'student_number' => '230001',
            'name' => 'Student Link',
            'email' => $user->email,
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
            'active' => true,
        ]);

        $this->artisan('core:reconcile-profile-user-links', [
            '--apply' => true,
            '--only' => 'students',
            '--email' => $user->email,
        ])->assertSuccessful();

        $this->assertSame($user->id, $student->fresh()->user_id);
    }

    /**
     * @return array{0: User, 1: User, 2: Department, 3: StudyProgram}
     */
    private function usersAndDepartment(): array
    {
        $wrongUser = User::factory()->create([
            'name' => 'Admin SI-KP',
            'email' => 'admin@sikp.test',
            'active' => true,
        ]);

        $rightUser = User::factory()->create([
            'name' => 'Farhamzah',
            'username' => 'farhamzah',
            'email' => 'farhamzah@ubpkarawang.ac.id',
            'identity_type' => 'lecturer',
            'active' => true,
        ]);

        $department = Department::create([
            'code' => 'FAR',
            'name' => 'Farmasi',
            'active' => true,
        ]);

        $studyProgram = StudyProgram::create([
            'department_id' => $department->id,
            'code' => 'S1-FAR',
            'name' => 'S1 Farmasi',
            'active' => true,
        ]);

        return [$wrongUser, $rightUser, $department, $studyProgram];
    }
}
