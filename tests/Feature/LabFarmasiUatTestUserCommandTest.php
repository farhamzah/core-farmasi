<?php

namespace Tests\Feature;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\User;
use App\Models\UserAppAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LabFarmasiUatTestUserCommandTest extends TestCase
{
    use RefreshDatabase;

    private const ROLES = [
        'admin_lab',
        'koordinator_lab',
        'laboran',
        'teknisi',
        'dosen',
        'mahasiswa',
        'viewer',
    ];

    public function test_dry_run_does_not_create_user_or_access(): void
    {
        $this->seedLabAppRoles();

        $this->artisan('core:lab-farmasi-uat-test-user')
            ->expectsOutputToContain('Mode: DRY-RUN')
            ->expectsOutputToContain('Dry-run only.')
            ->doesntExpectOutputToContain('Farhamzah34#')
            ->assertExitCode(0);

        $this->assertSame(0, User::query()->where('email', 'farhamzah@ubpkarawang.ac.id')->count());
        $this->assertSame(0, UserAppAccess::query()->count());
    }

    public function test_apply_creates_one_user_with_all_lab_roles(): void
    {
        $this->seedLabAppRoles();

        $this->artisan('core:lab-farmasi-uat-test-user', ['--apply' => true])
            ->expectsOutputToContain('Mode: APPLY')
            ->expectsOutputToContain('Lab Farmasi UAT test user is ready.')
            ->assertExitCode(0);

        $user = User::query()->where('email', 'farhamzah@ubpkarawang.ac.id')->firstOrFail();

        foreach (self::ROLES as $role) {
            $this->assertDatabaseHas('user_app_accesses', [
                'user_id' => $user->id,
                'app_code' => 'lab-farmasi',
                'role_slug' => $role,
                'is_active' => true,
            ]);
        }

        $this->assertSame(7, UserAppAccess::query()->where('user_id', $user->id)->where('app_code', 'lab-farmasi')->count());
    }

    public function test_apply_can_set_password_from_env_without_printing_it(): void
    {
        $this->seedLabAppRoles();

        putenv('CORE_LAB_UAT_PASSWORD=Farhamzah34#');
        $_ENV['CORE_LAB_UAT_PASSWORD'] = 'Farhamzah34#';

        try {
            $this->artisan('core:lab-farmasi-uat-test-user', [
                '--apply' => true,
                '--set-password' => true,
            ])
                ->expectsOutputToContain('Password action')
                ->doesntExpectOutputToContain('Farhamzah34#')
                ->assertExitCode(0);
        } finally {
            putenv('CORE_LAB_UAT_PASSWORD');
            unset($_ENV['CORE_LAB_UAT_PASSWORD']);
        }

        $user = User::query()->where('email', 'farhamzah@ubpkarawang.ac.id')->firstOrFail();

        $this->assertTrue(Hash::check('Farhamzah34#', $user->password));
        $this->assertFalse((bool) $user->must_change_password);
    }

    public function test_apply_requires_all_canonical_lab_roles(): void
    {
        $this->seedLabAppRoles(['viewer']);

        $this->artisan('core:lab-farmasi-uat-test-user', ['--apply' => true])
            ->expectsOutputToContain('Missing active Lab Farmasi role(s): viewer')
            ->assertExitCode(1);
    }

    private function seedLabAppRoles(array $skipRoles = []): void
    {
        $application = CoreApplication::query()->create([
            'app_code' => 'lab-farmasi',
            'name' => 'Lab Farmasi UBP',
            'is_active' => true,
            'is_public_visible' => false,
            'requires_login' => true,
            'is_sensitive' => false,
        ]);

        foreach (array_diff(self::ROLES, $skipRoles) as $role) {
            CoreApplicationRole::query()->create([
                'core_application_id' => $application->id,
                'app_code' => 'lab-farmasi',
                'role_slug' => $role,
                'role_name' => $role,
                'is_active' => true,
            ]);
        }
    }
}
