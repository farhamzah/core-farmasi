<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_super_admin_can_access_admin(): void
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create(['name' => 'super-admin', 'label' => 'Super Admin', 'active' => true]);

        $user->roles()->attach($role);

        $this->actingAs($user)->get('/admin')->assertOk();
    }

    public function test_super_admin_can_open_core_resource_indexes(): void
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create(['name' => 'super-admin', 'label' => 'Super Admin', 'active' => true]);

        $user->roles()->attach($role);

        foreach ([
            '/admin/users',
            '/admin/students',
            '/admin/lecturers',
            '/admin/employees',
            '/admin/roles',
            '/admin/departments',
            '/admin/study-programs',
            '/admin/user-app-accesses',
            '/admin/user-activity-logs',
        ] as $path) {
            $this->actingAs($user)->get($path)->assertOk();
        }
    }

    public function test_user_without_core_admin_role_cannot_access_admin(): void
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create(['name' => 'mahasiswa', 'label' => 'Mahasiswa', 'active' => true]);

        $user->roles()->attach($role);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }
}
