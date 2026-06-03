<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Faculty;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\StudyProgram;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CoreAcademicStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_academic_structure_tables_support_faculty_programs_and_departments(): void
    {
        $this->assertTrue(Schema::hasTable('faculties'));
        $this->assertTrue(Schema::hasColumn('departments', 'faculty_id'));
        $this->assertTrue(Schema::hasColumn('study_programs', 'faculty_id'));
    }

    public function test_database_seeder_aligns_farmasi_faculty_programs_and_departments(): void
    {
        $this->seed(DatabaseSeeder::class);

        $faculty = Faculty::where('code', 'FF')->firstOrFail();

        $this->assertSame('Farmasi', $faculty->name);
        $this->assertEqualsCanonicalizing(
            [
                'Farmakologi dan Farmasi Klinik',
                'Farmakokimia',
                'Teknologi Sediaan Farmasi',
                'Biologi Farmasi',
            ],
            Department::where('faculty_id', $faculty->id)->pluck('name')->all(),
        );
        $this->assertEqualsCanonicalizing(
            ['Farmasi S1', 'Profesi Apoteker'],
            StudyProgram::where('faculty_id', $faculty->id)->pluck('name')->all(),
        );
    }

    public function test_academic_master_data_cannot_be_deleted_while_referenced(): void
    {
        $faculty = Faculty::create([
            'code' => 'FF',
            'name' => 'Farmasi',
            'active' => true,
        ]);

        $department = Department::create([
            'faculty_id' => $faculty->id,
            'code' => 'FFK',
            'name' => 'Farmakologi dan Farmasi Klinik',
            'active' => true,
        ]);

        $studyProgram = StudyProgram::create([
            'faculty_id' => $faculty->id,
            'department_id' => $department->id,
            'code' => 'S1-FARMASI',
            'name' => 'Farmasi S1',
            'active' => true,
        ]);

        Student::create([
            'student_number' => '221011402637',
            'name' => 'Andi Nurjanah',
            'email' => 'andi@example.test',
            'study_program_id' => $studyProgram->id,
            'active' => true,
        ]);

        Lecturer::create([
            'lecturer_number' => '0430037804',
            'name' => 'Farhamzah',
            'email' => 'farhamzah@example.test',
            'department_id' => $department->id,
            'study_program_id' => $studyProgram->id,
            'active' => true,
        ]);

        Employee::create([
            'employee_number' => 'EMP-001',
            'name' => 'Tendik Farmasi',
            'staff_type' => 'tendik',
            'department_id' => $department->id,
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
        ]);

        $this->assertFalse($faculty->canBeDeletedSafely());
        $this->assertFalse($department->canBeDeletedSafely());
        $this->assertFalse($studyProgram->canBeDeletedSafely());

        foreach ([$faculty, $department, $studyProgram] as $record) {
            try {
                $record->delete();
                $this->fail($record::class.' should not be deletable while referenced.');
            } catch (ValidationException $exception) {
                $this->assertStringContainsString('Nonaktifkan data jika sudah tidak dipakai.', $exception->getMessage());
            }
        }

        $this->assertDatabaseHas('faculties', ['id' => $faculty->id]);
        $this->assertDatabaseHas('departments', ['id' => $department->id]);
        $this->assertDatabaseHas('study_programs', ['id' => $studyProgram->id]);
        $this->assertDatabaseHas('students', ['student_number' => '221011402637']);
        $this->assertDatabaseHas('lecturers', ['lecturer_number' => '0430037804']);
        $this->assertDatabaseHas('employees', ['employee_number' => 'EMP-001']);
    }
}
