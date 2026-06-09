<?php

namespace Tests\Feature;

use App\Models\AccountRequest;
use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserAppAccess;
use App\Services\CoreAccountRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
            'address',
            'birth_date',
            'gender',
            'identity_number',
            'student_number',
            'lecturer_number',
            'nip',
            'nidn',
            'nidk',
            'nuptk',
            'employee_number',
            'staff_type',
            'position_title',
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
            ->assertSee('Pilih Jenis Akun')
            ->assertSee('Mahasiswa')
            ->assertSee('Dosen')
            ->assertSee('Tendik / Staf')
            ->assertSee('Pembimbing Luar')
            ->assertDontSee('NIK / No. KTP')
            ->assertDontSee('Tanggal Lahir')
            ->assertDontSee('Alamat')
            ->assertDontSee('name="password"', false)
            ->assertDontSee('secret_hash')
            ->assertDontSee('api_token');
    }

    public function test_guest_can_submit_valid_request_without_creating_user_or_app_access_when_enabled(): void
    {
        config(['core_account.public_account_request_enabled' => true]);
        CoreApplication::create([
            'app_code' => 'kp-farmasi',
            'name' => 'KP Farmasi',
            'is_active' => true,
        ]);

        $this->post('/account-request', [
            'request_type' => AccountRequest::TYPE_STUDENT,
            'name' => 'Calon Mahasiswa',
            'email' => 'calon.mahasiswa@example.test',
            'phone' => '081234567890',
            'student_number' => 'REQ-001',
            'study_program_id' => $this->createStudyProgram()->id,
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

    public function test_guest_can_submit_minimal_student_request_without_study_program(): void
    {
        config(['core_account.public_account_request_enabled' => true]);

        $this->post('/account-request', [
            'request_type' => AccountRequest::TYPE_STUDENT,
            'name' => 'Mahasiswa Minimal',
            'email' => 'mahasiswa.minimal@example.test',
            'student_number' => 'MIN-001',
        ])->assertRedirect('/account-request/success');

        $this->assertDatabaseHas('account_requests', [
            'request_type' => AccountRequest::TYPE_STUDENT,
            'email' => 'mahasiswa.minimal@example.test',
            'student_number' => 'MIN-001',
            'study_program_id' => null,
            'status' => AccountRequest::STATUS_PENDING,
        ]);

        $this->assertSame(0, User::count());
        $this->assertSame(0, Student::count());
        $this->assertSame(0, UserAppAccess::count());
    }

    public function test_guest_cannot_submit_duplicate_active_account_request_email(): void
    {
        config(['core_account.public_account_request_enabled' => true]);

        AccountRequest::create([
            'request_type' => AccountRequest::TYPE_LECTURER,
            'name' => 'Existing Request',
            'email' => 'ermi.abriyani@ubpkarawang.ac.id',
            'lecturer_number' => '416200028/0405108202',
            'status' => AccountRequest::STATUS_PENDING,
        ]);

        $this->from('/account-request')
            ->post('/account-request', [
                'request_type' => AccountRequest::TYPE_LECTURER,
                'name' => 'Ermi Abriyani',
                'email' => 'ERMI.ABRIYANI@ubpkarawang.ac.id',
                'lecturer_number' => 'NEW-LECTURER-NUMBER',
            ])
            ->assertRedirect('/account-request')
            ->assertSessionHasErrors([
                'email' => 'Email sudah pernah terdaftar atau sedang menunggu review. Gunakan email lain atau hubungi Admin Core.',
            ]);

        $this->assertSame(1, AccountRequest::count());
    }

    public function test_guest_cannot_submit_account_request_for_existing_user_email(): void
    {
        config(['core_account.public_account_request_enabled' => true]);

        User::factory()->create([
            'email' => 'existing.user@example.test',
            'active' => true,
        ]);

        $this->from('/account-request')
            ->post('/account-request', [
                'request_type' => AccountRequest::TYPE_FIELD_SUPERVISOR,
                'name' => 'Existing User',
                'email' => 'existing.user@example.test',
                'phone' => '081234567890',
            ])
            ->assertRedirect('/account-request')
            ->assertSessionHasErrors('email');

        $this->assertSame(0, AccountRequest::count());
    }

    public function test_guest_cannot_submit_duplicate_student_number(): void
    {
        config(['core_account.public_account_request_enabled' => true]);

        Student::create([
            'student_number' => '221011402637',
            'name' => 'Existing Student',
            'email' => 'existing.student@example.test',
            'study_program_id' => $this->createStudyProgram()->id,
        ]);

        $this->from('/account-request')
            ->post('/account-request', [
                'request_type' => AccountRequest::TYPE_STUDENT,
                'name' => 'Duplicate Student',
                'email' => 'duplicate.student@example.test',
                'student_number' => '221011402637',
            ])
            ->assertRedirect('/account-request')
            ->assertSessionHasErrors([
                'student_number' => 'NIM sudah pernah terdaftar atau sedang menunggu review.',
            ]);

        $this->assertSame(0, AccountRequest::count());
    }

    public function test_guest_cannot_submit_duplicate_pending_student_number(): void
    {
        config(['core_account.public_account_request_enabled' => true]);

        AccountRequest::create([
            'request_type' => AccountRequest::TYPE_STUDENT,
            'name' => 'Mahasiswa Pending',
            'email' => 'pending.student@example.test',
            'student_number' => '221011402637',
            'status' => AccountRequest::STATUS_PENDING,
        ]);

        $this->from('/account-request')
            ->post('/account-request', [
                'request_type' => AccountRequest::TYPE_STUDENT,
                'name' => 'Duplicate Pending NIM',
                'email' => 'duplicate.pending.student@example.test',
                'student_number' => '221011402637',
            ])
            ->assertRedirect('/account-request')
            ->assertSessionHasErrors([
                'student_number' => 'NIM sudah pernah terdaftar atau sedang menunggu review.',
            ]);

        $this->assertSame(1, AccountRequest::count());
    }

    public function test_guest_can_resubmit_after_approved_student_was_soft_deleted_and_admin_can_restore_profile(): void
    {
        config(['core_account.public_account_request_enabled' => true]);

        $admin = $this->createCoreAdmin('admin-core');
        $studyProgram = $this->createStudyProgram();
        Role::create(['name' => 'mahasiswa', 'label' => 'Mahasiswa', 'active' => true]);
        $app = CoreApplication::create([
            'app_code' => 'kp-farmasi',
            'name' => 'KP Farmasi',
            'is_active' => true,
        ]);
        CoreApplicationRole::create([
            'core_application_id' => $app->id,
            'app_code' => 'kp-farmasi',
            'role_slug' => 'mahasiswa',
            'role_name' => 'Mahasiswa',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Adi Hidayat Lama',
            'email' => 'adi.hidayat@example.test',
            'username' => '221011402637',
            'identity_type' => 'student',
            'identity_number' => '221011402637',
            'active' => true,
        ]);
        $student = Student::create([
            'user_id' => $user->id,
            'student_number' => '221011402637',
            'name' => 'Adi Hidayat Lama',
            'email' => 'adi.hidayat@example.test',
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
            'active' => true,
        ]);
        $access = UserAppAccess::create([
            'user_id' => $user->id,
            'app_code' => 'kp-farmasi',
            'role_slug' => 'mahasiswa',
            'permissions' => [],
            'is_active' => true,
            'activated_at' => now(),
        ]);
        AccountRequest::create([
            'request_type' => AccountRequest::TYPE_STUDENT,
            'name' => 'Adi Hidayat Lama',
            'email' => 'adi.hidayat@example.test',
            'student_number' => '221011402637',
            'status' => AccountRequest::STATUS_APPROVED,
            'approved_user_id' => $user->id,
        ]);

        $access->delete();
        $student->delete();
        $user->delete();

        $this->from('/account-request')
            ->post('/account-request', [
                'request_type' => AccountRequest::TYPE_STUDENT,
                'name' => 'Adi Hidayat',
                'email' => 'adi.hidayat@example.test',
                'student_number' => '221011402637',
                'study_program_id' => $studyProgram->id,
                'requested_app_code' => 'kp-farmasi',
                'requested_role' => 'mahasiswa',
            ])
            ->assertRedirect('/account-request/success');

        $newRequest = AccountRequest::query()->where('status', AccountRequest::STATUS_PENDING)->firstOrFail();

        app(CoreAccountRequestService::class)->approveAndProvision($newRequest, $admin, 'Restore data lama yang sempat dihapus.', true);

        $newRequest->refresh();
        $restoredUser = User::withTrashed()->findOrFail($user->id);
        $restoredStudent = Student::withTrashed()->findOrFail($student->id);

        $this->assertFalse($restoredUser->trashed());
        $this->assertFalse($restoredStudent->trashed());
        $this->assertSame($restoredUser->id, $newRequest->approved_user_id);
        $this->assertSame($restoredUser->id, $restoredStudent->user_id);
        $this->assertSame('Adi Hidayat', $restoredStudent->name);
        $this->assertDatabaseHas('user_app_accesses', [
            'user_id' => $restoredUser->id,
            'app_code' => 'kp-farmasi',
            'role_slug' => 'mahasiswa',
            'is_active' => true,
        ]);
    }

    public function test_guest_can_submit_field_supervisor_request(): void
    {
        config(['core_account.public_account_request_enabled' => true]);

        $this->post('/account-request', [
            'request_type' => AccountRequest::TYPE_FIELD_SUPERVISOR,
            'name' => 'Pembimbing Luar',
            'email' => 'pembimbing.luar@example.test',
            'phone' => '081234567890',
            'position_title' => 'RS Mitra Farmasi',
            'requested_role' => 'pembimbing-lapangan',
        ])->assertRedirect('/account-request/success');

        $this->assertDatabaseHas('account_requests', [
            'request_type' => AccountRequest::TYPE_FIELD_SUPERVISOR,
            'name' => 'Pembimbing Luar',
            'email' => 'pembimbing.luar@example.test',
            'phone' => '081234567890',
            'position_title' => 'RS Mitra Farmasi',
            'requested_role' => 'pembimbing-lapangan',
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
            'department_id' => $this->createDepartment()->id,
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

    public function test_approve_student_request_creates_profile_user_and_global_role_without_app_access(): void
    {
        $admin = $this->createCoreAdmin('admin-core');
        $studyProgram = $this->createStudyProgram();
        Role::create(['name' => 'mahasiswa', 'label' => 'Mahasiswa', 'active' => true]);

        $request = AccountRequest::create([
            'request_type' => AccountRequest::TYPE_STUDENT,
            'name' => 'Andi Nurjanah',
            'email' => 'andi.request@example.test',
            'phone' => '081234567890',
            'address' => 'Karawang',
            'student_number' => '221011402637',
            'study_program_id' => $studyProgram->id,
            'requested_app_code' => 'ta-farmasi',
            'requested_role' => 'mahasiswa',
            'status' => AccountRequest::STATUS_PENDING,
        ]);

        app(CoreAccountRequestService::class)->approveAndProvision($request, $admin, 'Data valid.');

        $request->refresh();
        $user = User::where('email', 'andi.request@example.test')->firstOrFail();
        $student = Student::where('student_number', '221011402637')->firstOrFail();

        $this->assertTrue($request->isApproved());
        $this->assertSame($user->id, $request->approved_user_id);
        $this->assertSame($user->id, $student->user_id);
        $this->assertSame('221011402637', $user->username);
        $this->assertSame('student', $user->identity_type);
        $this->assertSame('221011402637', $user->identity_number);
        $this->assertTrue(Hash::check('Andi2637!', $user->password));
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->roles()->where('name', 'mahasiswa')->exists());
        $this->assertSame(0, UserAppAccess::where('user_id', $user->id)->count());
    }

    public function test_approve_student_request_can_create_requested_app_access_when_confirmed(): void
    {
        $admin = $this->createCoreAdmin('admin-core');
        $studyProgram = $this->createStudyProgram();
        Role::create(['name' => 'mahasiswa', 'label' => 'Mahasiswa', 'active' => true]);
        $app = CoreApplication::create([
            'app_code' => 'kp-farmasi',
            'name' => 'KP Farmasi',
            'is_active' => true,
        ]);
        CoreApplicationRole::create([
            'core_application_id' => $app->id,
            'app_code' => 'kp-farmasi',
            'role_slug' => 'mahasiswa',
            'role_name' => 'Mahasiswa',
            'is_active' => true,
        ]);

        $request = AccountRequest::create([
            'request_type' => AccountRequest::TYPE_STUDENT,
            'name' => 'Adi Hidayat',
            'email' => 'adi.request@example.test',
            'student_number' => '1234567890',
            'study_program_id' => $studyProgram->id,
            'requested_app_code' => 'kp-farmasi',
            'requested_role' => 'mahasiswa',
            'status' => AccountRequest::STATUS_APPROVED,
        ]);

        app(CoreAccountRequestService::class)->approveAndProvision($request, $admin, 'Data valid.', true);

        $user = User::where('email', 'adi.request@example.test')->firstOrFail();
        $student = Student::where('student_number', '1234567890')->firstOrFail();

        $this->assertSame($user->id, $student->user_id);
        $this->assertDatabaseHas('user_app_accesses', [
            'user_id' => $user->id,
            'app_code' => 'kp-farmasi',
            'role_slug' => 'mahasiswa',
            'is_active' => true,
        ]);
    }

    public function test_approve_lecturer_request_maps_official_numbers(): void
    {
        $admin = $this->createCoreAdmin('super-admin');
        $department = $this->createDepartment();
        $studyProgram = $this->createStudyProgram($department);
        Role::create(['name' => 'dosen', 'label' => 'Dosen', 'active' => true]);

        $request = AccountRequest::create([
            'request_type' => AccountRequest::TYPE_LECTURER,
            'name' => 'Farhamzah',
            'email' => 'lecturer.request@example.test',
            'identity_number' => '3215033003780003',
            'lecturer_number' => '0430037804',
            'nip' => '197803302005011001',
            'nidn' => '0430037804',
            'nidk' => 'NIDK-001',
            'nuptk' => 'NUPTK-001',
            'department_id' => $department->id,
            'study_program_id' => $studyProgram->id,
            'status' => AccountRequest::STATUS_IN_REVIEW,
        ]);

        app(CoreAccountRequestService::class)->approveAndProvision($request, $admin);

        $user = User::where('email', 'lecturer.request@example.test')->firstOrFail();
        $lecturer = Lecturer::where('lecturer_number', '0430037804')->firstOrFail();

        $this->assertSame($user->id, $lecturer->user_id);
        $this->assertSame('3215033003780003', $lecturer->national_id_number);
        $this->assertSame('197803302005011001', $lecturer->nip);
        $this->assertSame('0430037804', $lecturer->nidn);
        $this->assertSame('NIDK-001', $lecturer->nidk);
        $this->assertSame('NUPTK-001', $lecturer->nuptk);
        $this->assertTrue($user->roles()->where('name', 'dosen')->exists());
    }

    public function test_approve_employee_request_creates_employee_and_user(): void
    {
        $admin = $this->createCoreAdmin('admin-core');
        $department = $this->createDepartment();
        Role::create(['name' => 'tata-usaha', 'label' => 'Tata Usaha', 'active' => true]);

        $request = AccountRequest::create([
            'request_type' => AccountRequest::TYPE_EMPLOYEE,
            'name' => 'Tendik Farmasi',
            'email' => 'employee.request@example.test',
            'identity_number' => '3215000000000001',
            'employee_number' => 'EMP-REQ-001',
            'staff_type' => 'tata-usaha',
            'position_title' => 'Staf Akademik',
            'department_id' => $department->id,
            'status' => AccountRequest::STATUS_PENDING,
        ]);

        app(CoreAccountRequestService::class)->approveAndProvision($request, $admin);

        $user = User::where('email', 'employee.request@example.test')->firstOrFail();
        $employee = Employee::where('employee_number', 'EMP-REQ-001')->firstOrFail();

        $this->assertSame($user->id, $employee->user_id);
        $this->assertSame('tata-usaha', $employee->staff_type);
        $this->assertSame('Staf Akademik', $employee->position_title);
        $this->assertTrue($user->roles()->where('name', 'tata-usaha')->exists());
        $this->assertSame(0, UserAppAccess::where('user_id', $user->id)->count());
    }

    public function test_approve_field_supervisor_request_creates_core_user_only(): void
    {
        $admin = $this->createCoreAdmin('admin-core');
        Role::create(['name' => 'pembimbing-lapangan', 'label' => 'Pembimbing Lapangan', 'active' => true]);

        $request = AccountRequest::create([
            'request_type' => AccountRequest::TYPE_FIELD_SUPERVISOR,
            'name' => 'Pembimbing Luar',
            'email' => 'pembimbing.luar@example.test',
            'phone' => '081234567890',
            'position_title' => 'RS Mitra Farmasi',
            'requested_role' => 'pembimbing-lapangan',
            'status' => AccountRequest::STATUS_PENDING,
        ]);

        app(CoreAccountRequestService::class)->approveAndProvision($request, $admin);

        $request->refresh();
        $user = User::where('email', 'pembimbing.luar@example.test')->firstOrFail();

        $this->assertTrue($request->isApproved());
        $this->assertSame($user->id, $request->approved_user_id);
        $this->assertSame('field_supervisor', $user->identity_type);
        $this->assertSame('pembimbing.luar@example.test', $user->username);
        $this->assertTrue(Hash::check('Pembimbingtest!', $user->password));
        $this->assertTrue($user->roles()->where('name', 'pembimbing-lapangan')->exists());
        $this->assertSame(0, Student::count());
        $this->assertSame(0, Lecturer::count());
        $this->assertSame(0, Employee::count());
        $this->assertSame(0, UserAppAccess::where('user_id', $user->id)->count());
    }

    public function test_approve_blocks_duplicate_identifier_with_different_email(): void
    {
        $admin = $this->createCoreAdmin('super-admin');
        $studyProgram = $this->createStudyProgram();

        Student::create([
            'student_number' => 'DUP-001',
            'name' => 'Existing Student',
            'email' => 'existing.student@example.test',
            'study_program_id' => $studyProgram->id,
        ]);

        $request = AccountRequest::create([
            'request_type' => AccountRequest::TYPE_STUDENT,
            'name' => 'Duplicate Student',
            'email' => 'duplicate.student@example.test',
            'student_number' => 'DUP-001',
            'study_program_id' => $studyProgram->id,
            'status' => AccountRequest::STATUS_PENDING,
        ]);

        $this->expectException(ValidationException::class);

        app(CoreAccountRequestService::class)->approveAndProvision($request, $admin);
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

    private function createDepartment(array $attributes = []): Department
    {
        return Department::create([
            'code' => $attributes['code'] ?? 'DEP-'.Str::random(8),
            'name' => $attributes['name'] ?? 'Departemen Farmasi',
            'active' => $attributes['active'] ?? true,
        ]);
    }

    private function createStudyProgram(?Department $department = null, array $attributes = []): StudyProgram
    {
        $department ??= $this->createDepartment();

        return StudyProgram::create([
            'department_id' => $department->id,
            'code' => $attributes['code'] ?? 'PRODI-'.Str::random(8),
            'name' => $attributes['name'] ?? 'S1 Farmasi',
            'active' => $attributes['active'] ?? true,
        ]);
    }
}
