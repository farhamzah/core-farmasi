<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\CoreApplication;
use App\Models\Department;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Database\Seeders\CoreApplicationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumniMasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_alumni_master_data_can_link_core_user_student_and_program(): void
    {
        $user = User::factory()->create(['identity_type' => 'alumni']);
        $department = Department::create(['code' => 'FAR', 'name' => 'Farmasi', 'active' => true]);
        $program = StudyProgram::create([
            'department_id' => $department->id,
            'code' => 'S1-FARMASI',
            'name' => 'S1 Farmasi',
            'active' => true,
        ]);
        $student = Student::create([
            'user_id' => $user->id,
            'student_number' => '20200099',
            'name' => 'Alumni Farmasi',
            'email' => 'alumni.farmasi@example.test',
            'study_program_id' => $program->id,
            'status' => 'graduated',
            'active' => false,
        ]);

        $alumni = Alumni::create([
            'user_id' => $user->id,
            'student_id' => $student->id,
            'study_program_id' => $program->id,
            'student_number' => $student->student_number,
            'name' => $student->name,
            'personal_email' => $student->email,
            'program_name_snapshot' => $program->name,
            'graduation_year' => 2024,
            'status' => 'verified',
            'source' => 'student_conversion',
            'active' => true,
        ]);

        $this->assertSame($user->id, $alumni->user->id);
        $this->assertSame($student->id, $alumni->student->id);
        $this->assertSame('S1 Farmasi', $alumni->studyProgram->name);
        $this->assertSame($alumni->id, $user->alumni->id);
        $this->assertSame($alumni->id, $student->alumni->id);
    }

    public function test_registry_uses_alumni_farmasi_public_name_and_subdomain_without_changing_contract_code(): void
    {
        $this->seed(CoreApplicationSeeder::class);

        $application = CoreApplication::query()->where('app_code', 'karir-farmasi')->firstOrFail();

        $this->assertSame('Alumni Farmasi', $application->name);
        $this->assertSame('https://alumni.safaubp.com', $application->base_url);
        $this->assertSame('https://alumni.safaubp.com', $application->admin_url);
    }
}
