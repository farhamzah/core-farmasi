<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployeeResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_employees_table_exists_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('employees'));

        foreach ([
            'user_id',
            'employee_number',
            'national_id_number',
            'name',
            'staff_type',
            'department_id',
            'study_program_id',
            'position_title',
            'phone',
            'email',
            'birth_date',
            'gender',
            'address',
            'status',
            'notes',
            'deleted_at',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('employees', $column), "Missing employees.{$column}");
        }
    }

    public function test_super_admin_can_open_employee_resource_index(): void
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create(['name' => 'super-admin', 'label' => 'Super Admin', 'active' => true]);

        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get('/admin/employees')
            ->assertOk();
    }

    public function test_super_admin_can_open_employee_create_and_edit_pages(): void
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create(['name' => 'super-admin', 'label' => 'Super Admin', 'active' => true]);
        $employee = Employee::create([
            'employee_number' => 'EMP-EDIT-001',
            'name' => 'Editable Staff',
            'staff_type' => 'tendik',
            'status' => 'active',
        ]);

        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get('/admin/employees/create')
            ->assertOk();

        $this->actingAs($user)
            ->get("/admin/employees/{$employee->id}/edit")
            ->assertOk();
    }

    public function test_admin_core_can_open_employee_resource_index(): void
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create(['name' => 'admin-core', 'label' => 'Admin Core', 'active' => true]);

        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get('/admin/employees')
            ->assertOk();
    }

    public function test_user_without_core_admin_role_cannot_open_employee_resource_index(): void
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create(['name' => 'mahasiswa', 'label' => 'Mahasiswa', 'active' => true]);

        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get('/admin/employees')
            ->assertForbidden();
    }

    public function test_employee_relationships_work(): void
    {
        $user = User::factory()->create(['active' => true]);
        $department = Department::create([
            'code' => 'FF',
            'name' => 'Fakultas Farmasi',
            'active' => true,
        ]);
        $studyProgram = StudyProgram::create([
            'department_id' => $department->id,
            'code' => 'S1-FARMASI',
            'name' => 'S1 Farmasi',
            'active' => true,
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_number' => 'EMP-001',
            'national_id_number' => '3276000000000001',
            'name' => 'Staff Farmasi',
            'staff_type' => 'staf_tu',
            'department_id' => $department->id,
            'study_program_id' => $studyProgram->id,
            'position_title' => 'Staf TU',
            'email' => 'staff@example.test',
            'status' => 'active',
        ]);

        $this->assertTrue($employee->user->is($user));
        $this->assertTrue($employee->department->is($department));
        $this->assertTrue($employee->studyProgram->is($studyProgram));
        $this->assertTrue($user->employee->is($employee));
        $this->assertTrue($department->employees()->whereKey($employee->id)->exists());
        $this->assertTrue($studyProgram->employees()->whereKey($employee->id)->exists());
    }
}
