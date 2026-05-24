<?php

namespace Tests\Feature;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAppAccess;
use Database\Seeders\CoreApplicationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CoreAccessAppRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_application_tables_exist_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('core_applications'));
        $this->assertTrue(Schema::hasTable('core_application_roles'));

        foreach ([
            'app_code',
            'name',
            'description',
            'base_url',
            'admin_url',
            'icon',
            'color',
            'is_active',
            'is_public_visible',
            'requires_login',
            'is_sensitive',
            'sort_order',
            'notes',
            'deleted_at',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('core_applications', $column), "Missing core_applications.{$column}");
        }

        foreach ([
            'core_application_id',
            'app_code',
            'role_slug',
            'role_name',
            'description',
            'is_active',
            'sort_order',
            'notes',
            'deleted_at',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('core_application_roles', $column), "Missing core_application_roles.{$column}");
        }
    }

    public function test_core_application_models_and_relations_work(): void
    {
        $application = CoreApplication::create([
            'app_code' => 'dossier-dosen',
            'name' => 'Dossier Dosen',
            'is_active' => true,
            'is_public_visible' => false,
            'requires_login' => true,
        ]);

        $role = CoreApplicationRole::create([
            'core_application_id' => $application->id,
            'app_code' => 'dossier-dosen',
            'role_slug' => 'reviewer',
            'role_name' => 'Reviewer',
            'is_active' => true,
        ]);

        $user = User::factory()->create(['active' => true]);
        $access = UserAppAccess::create([
            'user_id' => $user->id,
            'app_code' => 'dossier-dosen',
            'role_slug' => 'reviewer',
            'is_active' => true,
        ]);

        $this->assertTrue($application->roles()->whereKey($role->id)->exists());
        $this->assertTrue($application->userAppAccesses()->whereKey($access->id)->exists());
        $this->assertTrue($access->application->is($application));
        $this->assertTrue($access->applicationRole->is($role));
    }

    public function test_core_application_seeder_is_idempotent_and_keeps_core_private(): void
    {
        $this->seed(CoreApplicationSeeder::class);
        $this->seed(CoreApplicationSeeder::class);

        $this->assertSame(1, CoreApplication::where('app_code', 'core-farmasi')->count());
        $this->assertSame(1, CoreApplicationRole::where('app_code', 'core-farmasi')->where('role_slug', 'super-admin')->count());

        $core = CoreApplication::where('app_code', 'core-farmasi')->firstOrFail();

        $this->assertFalse($core->is_public_visible);
        $this->assertTrue($core->requires_login);
        $this->assertTrue($core->is_sensitive);

        foreach (['kp-farmasi', 'safa-ubp', 'tu-farmasi'] as $appCode) {
            $this->assertTrue(CoreApplication::where('app_code', $appCode)->exists());
        }
    }

    public function test_app_role_catalog_can_store_new_application_roles_without_global_role_changes(): void
    {
        $globalRoleCount = Role::count();

        $application = CoreApplication::create([
            'app_code' => 'dossier-dosen',
            'name' => 'Dossier Dosen',
            'is_active' => true,
        ]);

        CoreApplicationRole::create([
            'core_application_id' => $application->id,
            'app_code' => 'dossier-dosen',
            'role_slug' => 'validator',
            'role_name' => 'Validator',
            'is_active' => true,
        ]);

        $this->assertSame($globalRoleCount, Role::count());
        $this->assertTrue(CoreApplicationRole::where('app_code', 'dossier-dosen')->where('role_slug', 'validator')->exists());
    }

    public function test_user_app_access_can_assign_new_dynamic_app_role_to_user(): void
    {
        $application = CoreApplication::create([
            'app_code' => 'dossier-dosen',
            'name' => 'Dossier Dosen',
            'is_active' => true,
        ]);
        CoreApplicationRole::create([
            'core_application_id' => $application->id,
            'app_code' => 'dossier-dosen',
            'role_slug' => 'reviewer',
            'role_name' => 'Reviewer',
            'is_active' => true,
        ]);
        $user = User::factory()->create(['active' => true]);

        $access = UserAppAccess::create([
            'user_id' => $user->id,
            'app_code' => 'dossier-dosen',
            'role_slug' => 'reviewer',
            'is_active' => true,
            'activated_at' => now(),
        ]);

        $this->assertTrue($access->fresh()->application->is($application));
        $this->assertSame('Reviewer', $access->fresh()->applicationRole->role_name);
    }

    public function test_core_admin_can_open_app_registry_role_catalog_and_user_access_resources(): void
    {
        $this->seed(CoreApplicationSeeder::class);
        $user = $this->createCoreAdmin('super-admin');

        $this->actingAs($user)->get('/admin/core-applications')->assertOk();
        $this->actingAs($user)->get('/admin/core-applications/create')->assertOk();
        $this->actingAs($user)->get('/admin/core-application-roles')->assertOk();
        $this->actingAs($user)->get('/admin/core-application-roles/create')->assertOk();
        $this->actingAs($user)->get('/admin/user-app-accesses')->assertOk();
        $this->actingAs($user)->get('/admin/user-app-accesses/create')->assertOk();
    }

    public function test_guest_is_redirected_from_access_control_resources(): void
    {
        $this->get('/admin/core-applications')->assertRedirect('/admin/login');
        $this->get('/admin/core-application-roles')->assertRedirect('/admin/login');
        $this->get('/admin/user-app-accesses')->assertRedirect('/admin/login');
    }

    public function test_user_without_core_admin_role_is_forbidden_from_access_control_resources(): void
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create(['name' => 'mahasiswa', 'label' => 'Mahasiswa', 'active' => true]);
        $user->roles()->attach($role);

        $this->actingAs($user)->get('/admin/core-applications')->assertForbidden();
        $this->actingAs($user)->get('/admin/core-application-roles')->assertForbidden();
        $this->actingAs($user)->get('/admin/user-app-accesses')->assertForbidden();
    }

    public function test_no_sso_routes_are_added(): void
    {
        $routeUris = collect(Route::getRoutes())
            ->map(fn ($route): string => $route->uri())
            ->all();

        $this->assertNotContains('admin/sso', $routeUris);
        $this->assertNotContains('sso', $routeUris);
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
}
