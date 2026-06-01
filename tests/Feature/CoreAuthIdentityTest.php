<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\CoreInitialPasswordService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CoreAuthIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_table_has_identity_auth_columns(): void
    {
        foreach ([
            'username',
            'identity_type',
            'identity_number',
            'must_change_password',
            'password_changed_at',
            'last_password_reset_at',
            'password_reset_by',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('users', $column), "{$column} column is missing.");
        }
    }

    public function test_user_identity_fields_are_fillable_and_casted(): void
    {
        $user = User::create([
            'name' => 'Core Identity User',
            'email' => 'identity@example.test',
            'username' => 'identity-user',
            'identity_type' => 'employee',
            'identity_number' => 'EMP-001',
            'password' => 'secret-password',
            'active' => true,
            'must_change_password' => 1,
            'password_changed_at' => now(),
            'last_password_reset_at' => now(),
        ]);

        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->hasIdentity());
        $this->assertSame('identity-user', $user->display_identity);
        $this->assertNotNull($user->password_changed_at);
        $this->assertNotNull($user->last_password_reset_at);
    }

    public function test_username_must_be_unique_when_filled(): void
    {
        User::factory()->create(['username' => 'duplicate-user']);

        $this->expectException(QueryException::class);

        User::factory()->create(['username' => 'duplicate-user']);
    }

    public function test_user_password_state_helpers_work(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => null,
        ]);

        $user->markMustChangePassword();
        $this->assertTrue($user->fresh()->must_change_password);

        $user->clearMustChangePassword();
        $this->assertFalse($user->fresh()->must_change_password);

        $user->markPasswordChanged();
        $user->refresh();

        $this->assertFalse($user->must_change_password);
        $this->assertNotNull($user->password_changed_at);
    }

    public function test_initial_password_strategy_defaults_to_name(): void
    {
        $this->assertSame('name', config('core_identity.initial_password_strategy'));
    }

    public function test_initial_password_service_hashes_name_strategy_password(): void
    {
        $service = app(CoreInitialPasswordService::class);
        $operator = User::factory()->create();
        $user = User::factory()->create([
            'name' => 'Nama Sementara',
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);

        $temporaryPassword = $service->generateForUser($user, '2001-08-07');

        $this->assertSame('Nama Sementara', $temporaryPassword);

        $service->setInitialPassword($user, '2001-08-07', $operator);
        $user->refresh();

        $this->assertNotSame($temporaryPassword, $user->password);
        $this->assertTrue(Hash::check($temporaryPassword, $user->password));
        $this->assertTrue($user->must_change_password);
        $this->assertNull($user->password_changed_at);
        $this->assertNotNull($user->last_password_reset_at);
        $this->assertSame($operator->id, $user->password_reset_by);
    }

    public function test_initial_password_service_can_still_use_birth_date_strategy_when_configured(): void
    {
        config(['core_identity.initial_password_strategy' => 'birth_date']);

        $service = app(CoreInitialPasswordService::class);
        $user = User::factory()->create(['name' => 'Nama User']);

        $temporaryPassword = $service->generateForUser($user, '2001-08-07');

        $this->assertSame('07/08/2001', $temporaryPassword);
        $this->assertTrue(Hash::check('07/08/2001', $service->hashForUser($user, '2001-08-07')));
    }

    public function test_super_admin_can_open_users_resource_after_identity_fields_added(): void
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create(['name' => 'super-admin', 'label' => 'Super Admin', 'active' => true]);

        $user->roles()->attach($role);

        $this->actingAs($user)->get('/admin/users')->assertOk();
    }

    public function test_non_core_user_still_cannot_open_users_resource(): void
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create(['name' => 'mahasiswa', 'label' => 'Mahasiswa', 'active' => true]);

        $user->roles()->attach($role);

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
    }
}
