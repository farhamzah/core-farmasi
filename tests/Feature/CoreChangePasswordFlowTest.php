<?php

namespace Tests\Feature;

use App\Filament\Pages\ChangePassword;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class CoreChangePasswordFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_open_change_password_page(): void
    {
        $user = $this->coreAdmin();

        $this->actingAs($user)
            ->get('/admin/change-password')
            ->assertOk();
    }

    public function test_guest_is_redirected_to_login_from_change_password_page(): void
    {
        $this->get('/admin/change-password')
            ->assertRedirect('/admin/login');
    }

    public function test_unauthorized_user_cannot_open_change_password_page(): void
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create(['name' => 'mahasiswa', 'label' => 'Mahasiswa', 'active' => true]);

        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get('/admin/change-password')
            ->assertForbidden();
    }

    public function test_password_can_be_changed_with_current_password(): void
    {
        $user = $this->coreAdmin([
            'password' => Hash::make('old-password'),
            'must_change_password' => true,
            'password_changed_at' => null,
        ]);

        $this->actingAs($user);

        Livewire::test(ChangePassword::class)
            ->fillForm([
                'current_password' => 'old-password',
                'new_password' => 'new-secure-password',
                'new_password_confirmation' => 'new-secure-password',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertTrue(Hash::check('new-secure-password', $user->password));
        $this->assertNotSame('new-secure-password', $user->password);
        $this->assertFalse($user->must_change_password);
        $this->assertNotNull($user->password_changed_at);
        $this->assertDatabaseHas('user_activity_logs', [
            'user_id' => $user->id,
            'action' => 'user.password_changed',
        ]);
    }

    public function test_password_change_fails_when_current_password_is_wrong(): void
    {
        $user = $this->coreAdmin([
            'password' => Hash::make('old-password'),
            'must_change_password' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(ChangePassword::class)
            ->fillForm([
                'current_password' => 'wrong-password',
                'new_password' => 'new-secure-password',
                'new_password_confirmation' => 'new-secure-password',
            ])
            ->call('save')
            ->assertHasFormErrors(['current_password']);

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_password_change_requires_confirmation(): void
    {
        $user = $this->coreAdmin([
            'password' => Hash::make('old-password'),
            'must_change_password' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(ChangePassword::class)
            ->fillForm([
                'current_password' => 'old-password',
                'new_password' => 'new-secure-password',
                'new_password_confirmation' => 'different-password',
            ])
            ->call('save')
            ->assertHasFormErrors(['new_password']);
    }

    public function test_must_change_password_user_is_redirected_to_change_password_page(): void
    {
        $user = $this->coreAdmin(['must_change_password' => true]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertRedirect('/admin/change-password');
    }

    public function test_user_without_must_change_password_is_not_redirected(): void
    {
        $user = $this->coreAdmin(['must_change_password' => false]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();
    }

    public function test_initial_password_reset_action_fails_without_birth_date(): void
    {
        config(['core_identity.initial_password_strategy' => 'birth_date']);

        $operator = $this->coreAdmin();
        $target = User::factory()->create([
            'password' => Hash::make('existing-password'),
            'must_change_password' => false,
        ]);

        $this->actingAs($operator);

        Livewire::test(ListUsers::class)
            ->callTableAction('setInitialPassword', $target);

        $target->refresh();

        $this->assertTrue(Hash::check('existing-password', $target->password));
        $this->assertFalse($target->must_change_password);
        $this->assertNull($target->last_password_reset_at);
    }

    public function test_initial_password_reset_action_uses_employee_birth_date(): void
    {
        config(['core_identity.initial_password_strategy' => 'birth_date']);

        $operator = $this->coreAdmin();
        $target = User::factory()->create([
            'password' => Hash::make('existing-password'),
            'must_change_password' => false,
        ]);

        Employee::factory()->create([
            'user_id' => $target->id,
            'birth_date' => '2001-08-07',
        ]);

        $this->actingAs($operator);

        Livewire::test(ListUsers::class)
            ->callTableAction('setInitialPassword', $target);

        $target->refresh();

        $this->assertTrue(Hash::check('07/08/2001', $target->password));
        $this->assertNotSame('07/08/2001', $target->password);
        $this->assertTrue($target->must_change_password);
        $this->assertNotNull($target->last_password_reset_at);
        $this->assertSame($operator->id, $target->password_reset_by);
        $this->assertDatabaseHas('user_activity_logs', [
            'user_id' => $operator->id,
            'action' => 'user.initial_password_reset',
        ]);

        $log = UserActivityLog::where('action', 'user.initial_password_reset')->latest('id')->first();

        $this->assertSame('birth_date_based', $log->meta['method']);
        $this->assertStringNotContainsString('07/08/2001', json_encode($log->meta));
    }

    public function test_initial_password_reset_action_uses_student_birth_date(): void
    {
        config(['core_identity.initial_password_strategy' => 'birth_date']);

        $operator = $this->coreAdmin();
        $target = User::factory()->create([
            'password' => Hash::make('existing-password'),
            'must_change_password' => false,
        ]);

        $studyProgram = $this->studyProgram();

        Student::create([
            'user_id' => $target->id,
            'student_number' => 'MHS-001',
            'name' => 'Student User',
            'email' => 'student-user@example.test',
            'birth_date' => '2002-09-08',
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
            'active' => true,
        ]);

        $this->actingAs($operator);

        Livewire::test(ListUsers::class)
            ->callTableAction('setInitialPassword', $target);

        $target->refresh();

        $this->assertTrue(Hash::check('08/09/2002', $target->password));
        $this->assertNotSame('08/09/2002', $target->password);
        $this->assertTrue($target->must_change_password);
        $this->assertNotNull($target->last_password_reset_at);
        $this->assertSame($operator->id, $target->password_reset_by);
    }

    public function test_initial_password_reset_action_uses_lecturer_birth_date(): void
    {
        config(['core_identity.initial_password_strategy' => 'birth_date']);

        $operator = $this->coreAdmin();
        $target = User::factory()->create([
            'password' => Hash::make('existing-password'),
            'must_change_password' => false,
        ]);

        $department = Department::create([
            'code' => 'FAR',
            'name' => 'Farmasi',
            'active' => true,
        ]);

        Lecturer::create([
            'user_id' => $target->id,
            'lecturer_number' => 'DSN-001',
            'name' => 'Lecturer User',
            'email' => 'lecturer-user@example.test',
            'birth_date' => '1988-10-09',
            'department_id' => $department->id,
            'active' => true,
        ]);

        $this->actingAs($operator);

        Livewire::test(ListUsers::class)
            ->callTableAction('setInitialPassword', $target);

        $target->refresh();

        $this->assertTrue(Hash::check('09/10/1988', $target->password));
        $this->assertNotSame('09/10/1988', $target->password);
        $this->assertTrue($target->must_change_password);
        $this->assertNotNull($target->last_password_reset_at);
        $this->assertSame($operator->id, $target->password_reset_by);
    }

    private function coreAdmin(array $attributes = []): User
    {
        $user = User::factory()->create([
            'active' => $attributes['active'] ?? true,
            ...$attributes,
        ]);

        $role = Role::firstOrCreate(
            ['name' => 'super-admin'],
            ['label' => 'Super Admin', 'active' => true],
        );

        $user->roles()->attach($role);

        return $user;
    }

    private function studyProgram(): StudyProgram
    {
        $department = Department::create([
            'code' => 'FF',
            'name' => 'Fakultas Farmasi',
            'active' => true,
        ]);

        return StudyProgram::create([
            'department_id' => $department->id,
            'code' => 'S1-FAR',
            'name' => 'S1 Farmasi',
            'active' => true,
        ]);
    }
}
