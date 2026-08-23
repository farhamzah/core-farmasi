<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_master_data_reports(): void
    {
        $this->get('/admin/reports/students')
            ->assertRedirect('/admin/login');
    }

    public function test_core_admin_can_open_students_report_page(): void
    {
        $admin = $this->createCoreAdmin('admin-core');

        $this->actingAs($admin)
            ->get('/admin/reports/students')
            ->assertOk()
            ->assertSee('Laporan Mahasiswa')
            ->assertSee('Preview data')
            ->assertSee('Download Excel')
            ->assertSee('Download PDF');
    }

    public function test_non_admin_cannot_access_master_data_reports(): void
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create([
            'name' => 'mahasiswa',
            'label' => 'Mahasiswa',
            'active' => true,
        ]);

        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get('/admin/reports/students')
            ->assertForbidden();
    }

    public function test_students_report_can_filter_by_nim_prefix_and_study_program(): void
    {
        $admin = $this->createCoreAdmin('super-admin');

        $faculty = Faculty::create([
            'code' => 'FF',
            'name' => 'Fakultas Farmasi',
            'active' => true,
        ]);

        $department = Department::create([
            'faculty_id' => $faculty->id,
            'code' => 'TSF',
            'name' => 'Teknologi Sediaan Farmasi',
            'active' => true,
        ]);

        $pharmacy = StudyProgram::create([
            'faculty_id' => $faculty->id,
            'department_id' => $department->id,
            'code' => 'S1F',
            'name' => 'S1 Farmasi',
            'active' => true,
        ]);

        $otherProgram = StudyProgram::create([
            'faculty_id' => $faculty->id,
            'department_id' => $department->id,
            'code' => 'APT',
            'name' => 'Profesi Apoteker',
            'active' => true,
        ]);

        Student::create([
            'student_number' => '23A001',
            'student_class' => 'FM23A',
            'name' => 'Mahasiswa Cocok',
            'email' => 'mahasiswa.cocok@example.test',
            'study_program_id' => $pharmacy->id,
            'status' => 'active',
            'active' => true,
        ]);

        Student::create([
            'student_number' => '24B001',
            'student_class' => 'FM24B',
            'name' => 'Mahasiswa Lain',
            'email' => 'mahasiswa.lain@example.test',
            'study_program_id' => $otherProgram->id,
            'status' => 'active',
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin/reports/students?nim_prefix=23&study_program_id=' . $pharmacy->id)
            ->assertOk()
            ->assertSee('Mahasiswa Cocok')
            ->assertDontSee('Mahasiswa Lain');
    }

    public function test_students_report_excel_download_works(): void
    {
        $admin = $this->createCoreAdmin('admin-core');

        $this->actingAs($admin)
            ->get('/admin/reports/students/excel')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_students_report_pdf_download_works(): void
    {
        $admin = $this->createCoreAdmin('admin-core');

        $this->actingAs($admin)
            ->get('/admin/reports/students/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_student_list_page_shows_report_action_for_core_admin(): void
    {
        $admin = $this->createCoreAdmin('super-admin');

        $this->actingAs($admin)
            ->get('/admin/students')
            ->assertOk()
            ->assertSee('/admin/reports/students', false)
            ->assertSee('Laporan');
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
}
