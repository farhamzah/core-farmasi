<?php

namespace Tests\Feature;

use App\Models\AccountRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAppAccess;
use App\Services\CoreAccountRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CoreAccountRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_requests_table_exists_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('account_requests'));

        foreach ([
            'request_type',
            'name',
            'email',
            'phone',
            'identity_number',
            'student_number',
            'lecturer_number',
            'employee_number',
            'study_program_id',
            'department_id',
            'requested_role',
            'requested_app_code',
            'status',
            'notes',
            'admin_notes',
            'reviewed_by',
            'reviewed_at',
            'approved_user_id',
            'submitted_ip',
            'submitted_user_agent',
            'deleted_at',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('account_requests', $column), "Missing account_requests.{$column}");
        }
    }

    public function test_public_account_request_is_disabled_by_default(): void
    {
        $this->get('/account-request')
            ->assertOk()
            ->assertSee('Registrasi akun tidak dibuka secara mandiri.')
            ->assertSee('Akun dibuat oleh Admin Core.')
            ->assertDontSee('name="password"', false)
            ->assertDontSee('<form', false)
            ->assertDontSee('secret_hash')
            ->assertDontSee('api_token');
    }

    public function test_register_alias_does_not_show_active_registration_form(): void
    {
        $this->get('/register')->assertRedirect('/account-request');

        $this->followingRedirects()
            ->get('/register')
            ->assertOk()
            ->assertSee('Registrasi akun tidak dibuka secara mandiri.')
            ->assertDontSee('<form', false);
    }

    public function test_guest_post_is_rejected_when_account_request_disabled(): void
    {
        $this->post('/account-request', [
            'request_type' => AccountRequest::TYPE_STUDENT,
            'name' => 'Calon Mahasiswa',
            'email' => 'calon.mahasiswa@example.test',
        ])->assertForbidden();

        $this->assertSame(0, AccountRequest::count());
        $this->assertSame(0, User::count());
        $this->assertSame(0, UserAppAccess::count());
    }

    public function test_config_default_disables_public_account_request(): void
    {
        $this->assertFalse(config('core_account.public_account_request_enabled'));
    }

    public function test_guest_can_view_account_request_form_when_enabled(): void
    {
        config(['core_account.public_account_request_enabled' => true]);

        $this->get('/account-request')
            ->assertOk()
            ->assertSee('Permohonan Akun')
            ->assertSee('Permohonan akun akan diverifikasi Admin Core')
            ->assertDontSee('name="password"', false)
            ->assertDontSee('secret_hash')
            ->assertDontSee('api_token');
    }

    public function test_guest_can_submit_valid_request_without_creating_user_or_app_access_when_enabled(): void
    {
        config(['core_account.public_account_request_enabled' => true]);

        $this->post('/account-request', [
            'request_type' => AccountRequest::TYPE_STUDENT,
            'name' => 'Calon Mahasiswa',
            'email' => 'calon.mahasiswa@example.test',
            'phone' => '081234567890',
            'student_number' => 'REQ-001',
            'requested_app_code' => 'kp-farmasi',
            'requested_role' => 'mahasiswa',
            'notes' => 'Permohonan akun mahasiswa.',
        ])->assertRedirect('/account-request/success');

        $this->assertDatabaseHas('account_requests', [
            'request_type' => AccountRequest::TYPE_STUDENT,
            'name' => 'Calon Mahasiswa',
            'email' => 'calon.mahasiswa@example.test',
            'student_number' => 'REQ-001',
            'requested_app_code' => 'kp-farmasi',
            'requested_role' => 'mahasiswa',
            'status' => AccountRequest::STATUS_PENDING,
        ]);

        $this->assertSame(0, User::count());
        $this->assertSame(0, UserAppAccess::count());
    }

    public function test_submit_does_not_require_or_store_password_field(): void
    {
        config(['core_account.public_account_request_enabled' => true]);

        $this->post('/account-request', [
            'request_type' => AccountRequest::TYPE_LECTURER,
            'name' => 'Calon Dosen',
            'email' => 'calon.dosen@example.test',
            'lecturer_number' => 'LEC-REQ-001',
            'password' => 'ignored-password',
        ])->assertRedirect('/account-request/success');

        $request = AccountRequest::firstOrFail();

        $this->assertSame(AccountRequest::STATUS_PENDING, $request->status);
        $this->assertArrayNotHasKey('password', $request->getAttributes());
    }

    public function test_invalid_email_is_rejected(): void
    {
        config(['core_account.public_account_request_enabled' => true]);

        $this->from('/account-request')
            ->post('/account-request', [
                'request_type' => AccountRequest::TYPE_EMPLOYEE,
                'name' => 'Calon Tendik',
                'email' => 'not-an-email',
            ])
            ->assertRedirect('/account-request')
            ->assertSessionHasErrors('email');

        $this->assertSame(0, AccountRequest::count());
    }

    public function test_admin_can_access_account_request_resource(): void
    {
        $admin = $this->createCoreAdmin('super-admin');

        AccountRequest::create([
            'request_type' => AccountRequest::TYPE_STUDENT,
            'name' => 'Calon Mahasiswa',
            'email' => 'calon.mahasiswa@example.test',
            'status' => AccountRequest::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->get('/admin/account-requests')
            ->assertOk()
            ->assertSee('Calon Mahasiswa')
            ->assertDontSee('secret_hash')
            ->assertDontSee('name="password"', false);
    }

    public function test_non_admin_cannot_access_admin_account_requests(): void
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create(['name' => 'mahasiswa', 'label' => 'Mahasiswa', 'active' => true]);
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get('/admin/account-requests')
            ->assertForbidden();
    }

    public function test_admin_can_reject_request_without_creating_user_or_access(): void
    {
        $admin = $this->createCoreAdmin('admin-core');
        $request = AccountRequest::create([
            'request_type' => AccountRequest::TYPE_STUDENT,
            'name' => 'Request Ditolak',
            'email' => 'reject@example.test',
            'status' => AccountRequest::STATUS_PENDING,
        ]);

        app(CoreAccountRequestService::class)->reject($request, $admin, 'Data tidak cocok.');

        $request->refresh();

        $this->assertTrue($request->isRejected());
        $this->assertTrue($request->reviewedBy->is($admin));
        $this->assertSame('Data tidak cocok.', $request->admin_notes);
        $this->assertSame(1, User::count());
        $this->assertSame(0, UserAppAccess::count());
    }

    public function test_approve_skeleton_does_not_create_user_or_app_access(): void
    {
        $admin = $this->createCoreAdmin('super-admin');
        $request = AccountRequest::create([
            'request_type' => AccountRequest::TYPE_EMPLOYEE,
            'name' => 'Request Approved',
            'email' => 'approve@example.test',
            'employee_number' => 'EMP-REQ-001',
            'status' => AccountRequest::STATUS_IN_REVIEW,
        ]);

        app(CoreAccountRequestService::class)->approveSkeleton($request, $admin, 'Approved for next step.');

        $request->refresh();

        $this->assertTrue($request->isApproved());
        $this->assertNull($request->approved_user_id);
        $this->assertSame(1, User::count());
        $this->assertSame(0, UserAppAccess::count());
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
