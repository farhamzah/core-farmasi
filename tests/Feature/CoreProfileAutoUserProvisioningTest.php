<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\CoreProfileUserProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CoreProfileAutoUserProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_master_data_auto_creates_user_with_nim_and_first_name_identifier_password(): void
    {
        $student = Student::create([
            'student_number' => '221011402637',
            'name' => 'Andi nurjanah',
            'email' => 'andi@example.test',
            'study_program_id' => $this->studyProgram()->id,
            'status' => 'active',
            'active' => true,
        ]);

        $user = $student->fresh()->user;

        $this->assertNotNull($user);
        $this->assertSame('221011402637', $user->username);
        $this->assertSame('student', $user->identity_type);
        $this->assertSame('221011402637', $user->identity_number);
        $this->assertSame('andi@example.test', $user->email);
        $this->assertTrue($user->active);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('Andi2637!', $user->password));
        $this->assertFalse(Hash::check('Andi nurjanah', $user->password));
        $this->assertFalse($user->roles()->whereIn('name', ['super-admin', 'admin-core'])->exists());
        $this->assertSame(0, $user->appAccesses()->count());
    }

    public function test_four_word_name_uses_first_word_only_for_initial_password(): void
    {
        $student = Student::create([
            'student_number' => '221011409999',
            'name' => 'Muhammad Rizky Aditya Pratama',
            'email' => 'rizky@example.test',
            'study_program_id' => $this->studyProgram()->id,
            'status' => 'active',
            'active' => true,
        ]);

        $this->assertTrue(Hash::check('Muhammad9999!', $student->fresh()->user->password));
    }

    public function test_lecturer_and_employee_master_data_auto_create_users(): void
    {
        $department = Department::create(['code' => 'FAR', 'name' => 'Farmasi', 'active' => true]);
        $studyProgram = $this->studyProgram($department);

        $lecturer = Lecturer::create([
            'lecturer_number' => '0012345678',
            'name' => 'Dosen Contoh',
            'email' => 'dosen-contoh@example.test',
            'department_id' => $department->id,
            'study_program_id' => $studyProgram->id,
            'active' => true,
        ]);

        $employee = Employee::create([
            'employee_number' => 'TENDIK001',
            'name' => 'Tendik Contoh',
            'staff_type' => 'tendik',
            'email' => 'tendik-contoh@example.test',
            'department_id' => $department->id,
            'status' => 'active',
        ]);

        $this->assertSame('0012345678', $lecturer->fresh()->user->username);
        $this->assertSame('lecturer', $lecturer->user->identity_type);
        $this->assertTrue(Hash::check('Dosen5678!', $lecturer->user->password));

        $this->assertSame('TENDIK001', $employee->fresh()->user->username);
        $this->assertSame('employee', $employee->user->identity_type);
        $this->assertTrue(Hash::check('TendikK001!', $employee->user->password));
    }

    public function test_master_profile_links_existing_user_instead_of_creating_duplicate(): void
    {
        $existing = User::factory()->create([
            'name' => 'Existing Student',
            'email' => 'existing-student@example.test',
            'username' => '230001',
            'identity_type' => 'student',
            'identity_number' => '230001',
            'active' => true,
        ]);

        $student = Student::create([
            'student_number' => '230001',
            'name' => 'Existing Student',
            'email' => 'existing-student@example.test',
            'study_program_id' => $this->studyProgram()->id,
            'status' => 'active',
            'active' => true,
        ]);

        $this->assertSame($existing->id, $student->fresh()->user_id);
        $this->assertSame(1, User::where('username', '230001')->count());
    }

    public function test_master_profile_without_email_is_not_auto_provisioned(): void
    {
        $employee = Employee::create([
            'employee_number' => 'EMP-NOEMAIL',
            'name' => 'Employee No Email',
            'staff_type' => 'tendik',
            'status' => 'active',
        ]);

        $this->assertNull($employee->fresh()->user_id);
        $this->assertFalse(User::where('username', 'EMP-NOEMAIL')->exists());
    }

    public function test_password_generator_uses_first_word_and_last_four_identifier_characters(): void
    {
        $service = app(CoreProfileUserProvisioningService::class);

        $this->assertSame('Andi2637!', $service->generateInitialPassword('Andi nurjanah', '221011402637'));
        $this->assertSame('Siti0001!', $service->generateInitialPassword('Siti Aminah Putri Farmasi', 'MHS-0001'));
        $this->assertSame('TendikK001!', $service->generateInitialPassword('Tendik Contoh', 'TENDIK001'));
    }

    public function test_provision_master_users_command_is_dry_run_by_default_and_can_apply_specific_identifier(): void
    {
        config(['core_identity.auto_user.enabled' => false]);

        $student = Student::create([
            'student_number' => '221011402637',
            'name' => 'Andi nurjanah',
            'email' => 'andi-command@example.test',
            'study_program_id' => $this->studyProgram()->id,
            'status' => 'active',
            'active' => true,
        ]);

        config(['core_identity.auto_user.enabled' => true]);

        Artisan::call('core:provision-master-users', [
            '--only' => 'students',
            '--identifier' => '221011402637',
            '--show-passwords' => true,
        ]);

        $this->assertNull($student->fresh()->user_id);
        $this->assertStringContainsString('Andi2637!', Artisan::output());

        Artisan::call('core:provision-master-users', [
            '--apply' => true,
            '--only' => 'students',
            '--identifier' => '221011402637',
        ]);

        $user = $student->fresh()->user;

        $this->assertNotNull($user);
        $this->assertSame('221011402637', $user->username);
        $this->assertTrue(Hash::check('Andi2637!', $user->password));
        $this->assertStringNotContainsString('Andi2637!', Artisan::output());
    }

    private function studyProgram(?Department $department = null): StudyProgram
    {
        $department ??= Department::create(['code' => 'FF-'.uniqid(), 'name' => 'Fakultas Farmasi', 'active' => true]);

        return StudyProgram::create([
            'department_id' => $department->id,
            'code' => 'S1-FAR-'.uniqid(),
            'name' => 'S1 Farmasi',
            'active' => true,
        ]);
    }
}
