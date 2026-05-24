<?php

namespace Tests\Feature;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAppAccess;
use App\Services\CoreAppLauncherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CoreAppLauncherTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_admin_can_open_app_launcher(): void
    {
        $this->actingAs($this->coreAdmin())
            ->get('/admin/app-launcher')
            ->assertOk()
            ->assertSee('Launcher Internal');
    }

    public function test_guest_is_redirected_from_app_launcher(): void
    {
        $this->get('/admin/app-launcher')
            ->assertRedirect('/admin/login');
    }

    public function test_unauthorized_user_cannot_open_app_launcher(): void
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create(['name' => 'mahasiswa', 'label' => 'Mahasiswa', 'active' => true]);
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get('/admin/app-launcher')
            ->assertForbidden();
    }

    public function test_service_only_returns_active_accesses_for_active_applications(): void
    {
        $user = $this->coreAdmin();
        $activeApp = $this->application('kp-farmasi', 'KP Farmasi', 'https://kp.example.test/admin');
        $inactiveAccessApp = $this->application('tu-farmasi', 'TU Farmasi', 'https://tu.example.test/admin');
        $inactiveApp = $this->application('safa-ubp', 'SAFA UBP', 'https://safa.example.test/admin', false);
        $noAccessApp = $this->application('dossier-dosen', 'Dossier Dosen', 'https://dossier.example.test/admin');

        $this->roleCatalog($activeApp, 'admin-kp', 'Admin KP');
        $this->roleCatalog($inactiveAccessApp, 'admin-tu', 'Admin TU');
        $this->roleCatalog($inactiveApp, 'admin-safa', 'Admin SAFA');
        $this->roleCatalog($noAccessApp, 'reviewer', 'Reviewer');

        UserAppAccess::create(['user_id' => $user->id, 'app_code' => 'kp-farmasi', 'role_slug' => 'admin-kp', 'is_active' => true]);
        UserAppAccess::create(['user_id' => $user->id, 'app_code' => 'tu-farmasi', 'role_slug' => 'admin-tu', 'is_active' => false]);
        UserAppAccess::create(['user_id' => $user->id, 'app_code' => 'safa-ubp', 'role_slug' => 'admin-safa', 'is_active' => true]);

        $apps = app(CoreAppLauncherService::class)->appsForUser($user);

        $this->assertCount(1, $apps);
        $this->assertSame('kp-farmasi', $apps[0]['app_code']);
        $this->assertSame('Admin KP', $apps[0]['roles'][0]['name']);
        $this->assertSame('https://kp.example.test/admin', $apps[0]['url']);
    }

    public function test_service_hides_current_core_application_and_disables_apps_without_url(): void
    {
        $user = $this->coreAdmin();
        $core = $this->application('core-farmasi', 'Core Farmasi', 'https://core.example.test/admin');
        $noUrl = $this->application('lab-farmasi', 'Lab Farmasi', null);

        $this->roleCatalog($core, 'admin-core', 'Admin Core');
        $this->roleCatalog($noUrl, 'laboran', 'Laboran');

        UserAppAccess::create(['user_id' => $user->id, 'app_code' => 'core-farmasi', 'role_slug' => 'admin-core', 'is_active' => true]);
        UserAppAccess::create(['user_id' => $user->id, 'app_code' => 'lab-farmasi', 'role_slug' => 'laboran', 'is_active' => true]);

        $apps = app(CoreAppLauncherService::class)->appsForUser($user);

        $this->assertCount(1, $apps);
        $this->assertSame('lab-farmasi', $apps[0]['app_code']);
        $this->assertTrue($apps[0]['is_disabled']);
        $this->assertSame('URL aplikasi belum dikonfigurasi.', $apps[0]['disabled_reason']);
    }

    public function test_launcher_page_shows_accessible_apps_and_disabled_url_state(): void
    {
        $user = $this->coreAdmin();
        $kp = $this->application('kp-farmasi', 'KP Farmasi', 'https://kp.example.test/admin');
        $lab = $this->application('lab-farmasi', 'Lab Farmasi', null);

        $this->roleCatalog($kp, 'pembimbing-dalam', 'Pembimbing Dalam');
        $this->roleCatalog($lab, 'laboran', 'Laboran');

        UserAppAccess::create(['user_id' => $user->id, 'app_code' => 'kp-farmasi', 'role_slug' => 'pembimbing-dalam', 'is_active' => true]);
        UserAppAccess::create(['user_id' => $user->id, 'app_code' => 'lab-farmasi', 'role_slug' => 'laboran', 'is_active' => true]);

        $this->actingAs($user)
            ->get('/admin/app-launcher')
            ->assertOk()
            ->assertSee('KP Farmasi')
            ->assertSee('Pembimbing Dalam')
            ->assertSee('https://kp.example.test/admin')
            ->assertSee('Lab Farmasi')
            ->assertSee('URL aplikasi belum dikonfigurasi.');
    }

    public function test_launcher_empty_state_for_user_without_app_access(): void
    {
        $this->actingAs($this->coreAdmin())
            ->get('/admin/app-launcher')
            ->assertOk()
            ->assertSee('Belum ada akses aplikasi aktif untuk akun ini.');
    }

    public function test_new_dynamic_application_role_can_appear_without_code_changes(): void
    {
        $user = $this->coreAdmin();
        $application = $this->application('dossier-dosen', 'Dossier Dosen', 'https://dossier.example.test/admin');

        $this->roleCatalog($application, 'validator', 'Validator');

        UserAppAccess::create([
            'user_id' => $user->id,
            'app_code' => 'dossier-dosen',
            'role_slug' => 'validator',
            'is_active' => true,
        ]);

        $apps = app(CoreAppLauncherService::class)->appsForUser($user);

        $this->assertSame('dossier-dosen', $apps[0]['app_code']);
        $this->assertSame('Validator', $apps[0]['roles'][0]['name']);
    }

    public function test_core_farmasi_registry_remains_not_public_visible(): void
    {
        $this->application('core-farmasi', 'Core Farmasi', 'https://core.example.test/admin');

        $this->assertFalse(CoreApplication::where('app_code', 'core-farmasi')->firstOrFail()->is_public_visible);
    }

    public function test_no_sso_token_or_public_launcher_routes_are_added(): void
    {
        $routeUris = collect(Route::getRoutes())
            ->map(fn ($route): string => $route->uri())
            ->all();

        $this->assertContains('admin/app-launcher', $routeUris);
        $this->assertNotContains('admin/sso', $routeUris);
        $this->assertNotContains('sso', $routeUris);
        $this->assertNotContains('admin/app-token', $routeUris);
        $this->assertNotContains('app-launcher', $routeUris);
    }

    private function coreAdmin(): User
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create(['name' => 'super-admin', 'label' => 'Super Admin', 'active' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    private function application(string $appCode, string $name, ?string $url, bool $active = true): CoreApplication
    {
        return CoreApplication::create([
            'app_code' => $appCode,
            'name' => $name,
            'description' => "{$name} internal app.",
            'base_url' => $url,
            'admin_url' => $url,
            'is_active' => $active,
            'is_public_visible' => false,
            'requires_login' => true,
            'is_sensitive' => $appCode === 'core-farmasi',
        ]);
    }

    private function roleCatalog(CoreApplication $application, string $roleSlug, string $roleName): CoreApplicationRole
    {
        return CoreApplicationRole::create([
            'core_application_id' => $application->id,
            'app_code' => $application->app_code,
            'role_slug' => $roleSlug,
            'role_name' => $roleName,
            'is_active' => true,
        ]);
    }
}
