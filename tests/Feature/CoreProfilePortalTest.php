<?php

namespace Tests\Feature;

use App\Mail\ProfilePasswordResetLinkMail;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CoreProfilePortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_profile_login(): void
    {
        $this->get('/profile')->assertRedirect('/profile/login');
    }

    public function test_guest_can_view_profile_login_form(): void
    {
        $this->get('/profile/login')
            ->assertOk()
            ->assertSee('Portal Profil Core Farmasi')
            ->assertSee('Username / Email / Nomor Identitas')
            ->assertSee('Lupa Password?')
            ->assertSee('data-password-toggle', false)
            ->assertSee('Lihat')
            ->assertDontSee('remember_token')
            ->assertDontSee('api_token')
            ->assertDontSee('password_hash');
    }

    public function test_guest_can_request_profile_password_reset_link_without_account_enumeration(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'active' => true,
            'username' => 'MHS-RESET-001',
            'email' => 'profile-reset@example.test',
            'password' => Hash::make('old-password'),
        ]);

        $this->from('/profile/forgot-password')
            ->post('/profile/forgot-password', [
                'login' => 'MHS-RESET-001',
            ])
            ->assertRedirect('/profile/forgot-password')
            ->assertSessionHas('status');

        Mail::assertSent(ProfilePasswordResetLinkMail::class, function (ProfilePasswordResetLinkMail $mail) use ($user): bool {
            return $mail->hasTo($user->email)
                && str_contains($mail->resetUrl, '/profile/reset-password/');
        });

        $this->assertDatabaseHas('user_activity_logs', [
            'user_id' => $user->id,
            'action' => 'profile.password_reset_requested',
        ]);

        $this->from('/profile/forgot-password')
            ->post('/profile/forgot-password', [
                'login' => 'unknown-account@example.test',
            ])
            ->assertRedirect('/profile/forgot-password')
            ->assertSessionHas('status');

        Mail::assertSent(ProfilePasswordResetLinkMail::class, 1);
    }

    public function test_guest_can_reset_profile_password_from_email_token(): void
    {
        $user = User::factory()->create([
            'active' => true,
            'email' => 'reset-token@example.test',
            'password' => Hash::make('old-password'),
            'must_change_password' => true,
            'password_changed_at' => null,
        ]);
        $token = PasswordBroker::broker()->createToken($user);

        $this->post('/profile/reset-password', [
            'token' => $token,
            'email' => 'reset-token@example.test',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertRedirect('/profile/edit');

        $user->refresh();

        $this->assertAuthenticatedAs($user);
        $this->assertFalse(Hash::check('old-password', $user->password));
        $this->assertTrue(Hash::check('new-secure-password', $user->password));
        $this->assertFalse($user->must_change_password);
        $this->assertNotNull($user->password_changed_at);
        $this->assertDatabaseHas('user_activity_logs', [
            'user_id' => $user->id,
            'action' => 'profile.password_reset_completed',
        ]);
    }

    public function test_profile_password_reset_rejects_invalid_token(): void
    {
        $user = User::factory()->create([
            'active' => true,
            'email' => 'invalid-token@example.test',
            'password' => Hash::make('old-password'),
        ]);

        $this->post('/profile/reset-password', [
            'token' => 'invalid-token',
            'email' => 'invalid-token@example.test',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
        $this->assertGuest();
    }

    public function test_logged_in_user_is_redirected_away_from_profile_login(): void
    {
        $user = User::factory()->create([
            'active' => true,
            'must_change_password' => false,
        ]);

        $this->actingAs($user)
            ->get('/profile/login')
            ->assertRedirect('/profile');
    }

    public function test_profile_login_accepts_username_and_redirects_incomplete_user_to_edit(): void
    {
        $user = User::factory()->create([
            'active' => true,
            'username' => '20260001',
            'email' => 'student-login@example.test',
            'password' => Hash::make('initial-password'),
            'must_change_password' => false,
        ]);

        $this->post('/profile/login', [
            'login' => '20260001',
            'password' => 'initial-password',
        ])->assertRedirect('/profile/edit');

        $this->assertAuthenticatedAs($user);
    }

    public function test_profile_login_redirects_must_change_user_to_change_password(): void
    {
        $user = User::factory()->create([
            'active' => true,
            'username' => '0012345678',
            'password' => Hash::make('initial-password'),
            'must_change_password' => true,
        ]);

        $this->post('/profile/login', [
            'login' => '0012345678',
            'password' => 'initial-password',
        ])->assertRedirect('/profile/change-password');

        $this->assertAuthenticatedAs($user);
    }

    public function test_profile_login_rejects_invalid_credentials_with_generic_error(): void
    {
        User::factory()->create([
            'active' => true,
            'username' => 'TENDIK001',
            'password' => Hash::make('correct-password'),
        ]);

        $this->from('/profile/login')
            ->post('/profile/login', [
                'login' => 'TENDIK001',
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/profile/login')
            ->assertSessionHasErrors('login');

        $this->assertGuest();
        $this->assertSame(
            'Login gagal. Periksa username dan password.',
            session('errors')->get('login')[0]
        );
    }

    public function test_authenticated_non_admin_can_access_profile_but_not_admin_panel(): void
    {
        $user = User::factory()->create(['active' => true, 'must_change_password' => false]);
        $role = Role::create(['name' => 'mahasiswa', 'label' => 'Mahasiswa', 'active' => true]);
        $user->roles()->attach($role);

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('Profil Saya')
            ->assertSee($user->name);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_guest_cannot_access_profile_change_password(): void
    {
        $this->get('/profile/change-password')->assertRedirect('/profile/login');
    }

    public function test_authenticated_user_can_view_profile_change_password(): void
    {
        $user = User::factory()->create([
            'active' => true,
            'password' => Hash::make('current-password'),
        ]);

        $this->actingAs($user)
            ->get('/profile/change-password')
            ->assertOk()
            ->assertSee('Ganti Password')
            ->assertSee('data-password-toggle', false)
            ->assertSee('Lihat')
            ->assertSee('Password ini berlaku untuk aplikasi Farmasi yang menggunakan verifikasi Core.')
            ->assertDontSee($user->password)
            ->assertDontSee('remember_token')
            ->assertDontSee('api_token');
    }

    public function test_non_admin_user_can_change_own_password_from_profile_portal(): void
    {
        $user = User::factory()->create([
            'active' => true,
            'password' => Hash::make('old-password'),
            'must_change_password' => true,
            'password_changed_at' => null,
        ]);
        $role = Role::create(['name' => 'mahasiswa', 'label' => 'Mahasiswa', 'active' => true]);
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->from('/profile/change-password')
            ->put('/profile/change-password', [
                'current_password' => 'old-password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
            ->assertRedirect('/profile/edit');

        $user->refresh();

        $this->assertFalse(Hash::check('old-password', $user->password));
        $this->assertTrue(Hash::check('new-secure-password', $user->password));
        $this->assertNotSame('new-secure-password', $user->password);
        $this->assertFalse($user->must_change_password);
        $this->assertNotNull($user->password_changed_at);
        $this->assertDatabaseHas('user_activity_logs', [
            'user_id' => $user->id,
            'action' => 'profile.password_changed',
        ]);

        $log = UserActivityLog::where('user_id', $user->id)->where('action', 'profile.password_changed')->latest('id')->first();

        $this->assertSame('profile_portal', $log->meta['source']);
        $this->assertStringNotContainsString('new-secure-password', json_encode($log->meta));
        $this->assertStringNotContainsString('old-password', json_encode($log->meta));
    }

    public function test_profile_password_change_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'active' => true,
            'password' => Hash::make('old-password'),
            'must_change_password' => true,
        ]);

        $this->actingAs($user)
            ->from('/profile/change-password')
            ->put('/profile/change-password', [
                'current_password' => 'wrong-password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertRedirect('/profile/change-password')
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_profile_password_change_requires_confirmation(): void
    {
        $user = User::factory()->create([
            'active' => true,
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($user)
            ->from('/profile/change-password')
            ->put('/profile/change-password', [
                'current_password' => 'old-password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'different-password',
            ])
            ->assertRedirect('/profile/change-password')
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_profile_password_change_cannot_change_another_users_password(): void
    {
        $user = User::factory()->create([
            'active' => true,
            'password' => Hash::make('old-password'),
        ]);
        $otherUser = User::factory()->create([
            'active' => true,
            'password' => Hash::make('other-password'),
        ]);

        $this->actingAs($user)
            ->put('/profile/change-password', [
                'user_id' => $otherUser->id,
                'current_password' => 'old-password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
            ->assertRedirect('/profile/edit');

        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
        $this->assertTrue(Hash::check('other-password', $otherUser->fresh()->password));
    }

    public function test_must_change_password_user_cannot_access_profile_or_edit_before_change(): void
    {
        $user = User::factory()->create([
            'active' => true,
            'must_change_password' => true,
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertRedirect('/profile/change-password');

        $this->actingAs($user)
            ->get('/profile/edit')
            ->assertRedirect('/profile/change-password');
    }

    public function test_profile_change_password_page_shows_must_change_warning(): void
    {
        $user = User::factory()->create([
            'active' => true,
            'must_change_password' => true,
        ]);

        $this->actingAs($user)
            ->get('/profile/change-password')
            ->assertOk()
            ->assertSee('Anda wajib mengganti password awal sebelum menggunakan layanan.')
            ->assertDontSee($user->password);
    }

    public function test_profile_page_shows_change_password_link_and_completion_warning(): void
    {
        $user = User::factory()->create([
            'active' => true,
            'must_change_password' => false,
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Ganti Password')
            ->assertSee('/profile/change-password')
            ->assertSee('Profil Anda belum lengkap.')
            ->assertSee('Profil belum lengkap')
            ->assertDontSee($user->password);
    }

    public function test_profile_edit_page_offers_navigation_and_password_change(): void
    {
        $user = User::factory()->create([
            'active' => true,
            'must_change_password' => false,
        ]);

        $this->actingAs($user)
            ->get('/profile/edit')
            ->assertOk()
            ->assertSee('Lihat Profil')
            ->assertSee('Ganti Password')
            ->assertSee('Simpan Profil')
            ->assertSee('Pilih Foto')
            ->assertSee('Password dapat diganti kapan saja');
    }

    public function test_user_without_linked_profile_can_save_safe_contact_to_core_account(): void
    {
        $user = User::factory()->create([
            'active' => true,
            'must_change_password' => false,
            'identity_type' => 'lecturer',
            'identity_number' => 'LECT-001',
            'phone' => null,
            'address' => null,
        ]);

        $this->actingAs($user)
            ->put('/profile', [
                'phone' => '081234500001',
                'address' => 'Alamat akun Core',
                'identity_number' => 'HACKED',
                'active' => false,
            ])
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('081234500001', $user->phone);
        $this->assertSame('Alamat akun Core', $user->address);
        $this->assertSame('LECT-001', $user->identity_number);
        $this->assertTrue($user->active);
        $this->assertDatabaseHas('user_activity_logs', [
            'user_id' => $user->id,
            'action' => 'profile.updated',
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('081234500001')
            ->assertSee('Alamat akun Core');
    }

    public function test_user_can_upload_profile_photo_from_profile_portal(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'active' => true,
            'must_change_password' => false,
            'profile_photo_path' => null,
        ]);

        $this->actingAs($user)
            ->put('/profile', [
                'phone' => '081234567890',
                'address' => 'Alamat Foto',
                'profile_photo' => UploadedFile::fake()->image('profile.jpg', 320, 320),
            ])
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertNotNull($user->profile_photo_path);
        Storage::disk('public')->assertExists($user->profile_photo_path);
        $this->assertDatabaseHas('user_activity_logs', [
            'user_id' => $user->id,
            'action' => 'profile.photo_updated',
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('/storage/'.$user->profile_photo_path, false)
            ->assertSee('core-profile-avatar', false);

        $this->actingAs($user)
            ->get('/profile/edit')
            ->assertOk()
            ->assertSee('/storage/'.$user->profile_photo_path, false)
            ->assertSee('core-profile-photo-preview', false);
    }

    public function test_profile_page_shows_student_summary_with_safe_fields(): void
    {
        [$user, $department, $studyProgram] = $this->createAcademicUser();

        Student::create([
            'user_id' => $user->id,
            'student_number' => 'MHS-001',
            'name' => 'Mahasiswa Uji',
            'email' => 'student@example.test',
            'phone' => '0812345678',
            'address' => 'Alamat Mahasiswa',
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
            'active' => true,
        ]);

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('Mahasiswa')
            ->assertSee('MHS-001')
            ->assertSee($studyProgram->name)
            ->assertSee('0812345678')
            ->assertSee('Alamat Mahasiswa')
            ->assertSee('Kelengkapan Profil')
            ->assertDontSee($user->password)
            ->assertDontSee((string) $user->api_token)
            ->assertDontSee('remember_token')
            ->assertDontSee('secret_hash');
    }

    public function test_profile_page_shows_lecturer_summary(): void
    {
        [$user, $department, $studyProgram] = $this->createAcademicUser();

        Lecturer::create([
            'user_id' => $user->id,
            'lecturer_number' => 'DSN-001',
            'national_id_number' => '3276010101010001',
            'nip' => '198801012020121001',
            'nidn' => '0011223344',
            'nidk' => 'NIDK001122',
            'nuptk' => '1234567890123456',
            'name' => 'Dosen Uji',
            'front_title' => 'Dr.',
            'back_title' => 'M.Farm.',
            'email' => 'lecturer@example.test',
            'department_id' => $department->id,
            'study_program_id' => $studyProgram->id,
            'phone' => '0811111111',
            'address' => 'Ruang Dosen',
            'active' => true,
        ]);

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('Dosen')
            ->assertSee('Dr. Dosen Uji, M.Farm.')
            ->assertSee('Nama Resmi Bergelar')
            ->assertSee('DSN-001')
            ->assertSee('0011223344')
            ->assertSee('198801012020121001')
            ->assertSee('NIDK001122')
            ->assertSee('1234567890123456')
            ->assertSee('32************01')
            ->assertSee('0811111111')
            ->assertSee('Ruang Dosen');
    }

    public function test_profile_page_shows_employee_summary(): void
    {
        [$user, $department, $studyProgram] = $this->createAcademicUser();

        Employee::create([
            'user_id' => $user->id,
            'employee_number' => 'EMP-001',
            'name' => 'Pegawai Uji',
            'staff_type' => 'staf_tu',
            'department_id' => $department->id,
            'study_program_id' => $studyProgram->id,
            'position_title' => 'Admin TU',
            'phone' => '0822222222',
            'email' => 'employee@example.test',
            'address' => 'Gedung Farmasi',
            'status' => 'active',
        ]);

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('Tendik / Staf / Laboran')
            ->assertSee('EMP-001')
            ->assertSee('Gedung Farmasi');
    }

    public function test_user_can_update_own_safe_contact_fields_only(): void
    {
        [$user, $department, $studyProgram] = $this->createAcademicUser([
            'identity_number' => 'ID-ORIGINAL',
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_number' => 'EMP-002',
            'name' => 'Pegawai Kontak',
            'staff_type' => 'laboran',
            'department_id' => $department->id,
            'study_program_id' => $studyProgram->id,
            'phone' => '0800000000',
            'email' => 'contact@example.test',
            'address' => 'Alamat Lama',
            'status' => 'active',
        ]);

        $role = Role::create(['name' => 'employee', 'label' => 'Employee', 'active' => true]);

        $this->actingAs($user)->put('/profile', [
            'phone' => '0899999999',
            'address' => 'Alamat Baru',
            'identity_number' => 'ID-HACKED',
            'role_id' => $role->id,
            'active' => false,
            'employee_number' => 'EMP-HACKED',
        ])->assertRedirect('/profile');

        $employee->refresh();
        $user->refresh();

        $this->assertSame('0899999999', $employee->phone);
        $this->assertSame('Alamat Baru', $employee->address);
        $this->assertSame('ID-ORIGINAL', $user->identity_number);
        $this->assertSame('EMP-002', $employee->employee_number);
        $this->assertTrue($user->active);
        $this->assertFalse($user->roles()->where('name', 'employee')->exists());
        $this->assertDatabaseHas('user_activity_logs', [
            'user_id' => $user->id,
            'action' => 'profile.updated',
        ]);

        $log = UserActivityLog::where('user_id', $user->id)->where('action', 'profile.updated')->latest('id')->first();

        $this->assertSame(['phone', 'address'], $log->meta['changed_fields']);
        $this->assertStringNotContainsString('Alamat Baru', json_encode($log->meta));
        $this->assertStringNotContainsString('0899999999', json_encode($log->meta));
    }

    public function test_students_table_has_safe_contact_fields(): void
    {
        $this->assertTrue(Schema::hasColumn('students', 'phone'));
        $this->assertTrue(Schema::hasColumn('students', 'address'));
        $this->assertTrue(Schema::hasColumn('students', 'birth_place'));
        $this->assertTrue(Schema::hasColumn('lecturers', 'address'));
        $this->assertTrue(Schema::hasColumn('lecturers', 'birth_place'));
        $this->assertTrue(Schema::hasColumn('lecturers', 'national_id_number'));
        $this->assertTrue(Schema::hasColumn('lecturers', 'nip'));
        $this->assertTrue(Schema::hasColumn('lecturers', 'nidn'));
        $this->assertTrue(Schema::hasColumn('lecturers', 'nidk'));
        $this->assertTrue(Schema::hasColumn('lecturers', 'nuptk'));
        $this->assertTrue(Schema::hasColumn('lecturers', 'front_title'));
        $this->assertTrue(Schema::hasColumn('lecturers', 'back_title'));
        $this->assertTrue(Schema::hasColumn('lecturers', 'title_updated_at'));
        $this->assertTrue(Schema::hasColumn('users', 'phone'));
        $this->assertTrue(Schema::hasColumn('users', 'address'));
        $this->assertTrue(Schema::hasColumn('users', 'alternate_email'));
        $this->assertTrue(Schema::hasColumn('users', 'profile_photo_path'));
        $this->assertTrue(Schema::hasColumn('employees', 'birth_place'));
    }

    public function test_student_can_update_phone_and_address(): void
    {
        [$user, $department, $studyProgram] = $this->createAcademicUser();

        $student = Student::create([
            'user_id' => $user->id,
            'student_number' => 'MHS-002',
            'name' => 'Mahasiswa Kontak',
            'email' => 'student-contact@example.test',
            'phone' => '0800000003',
            'address' => 'Alamat Lama Mahasiswa',
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
            'active' => true,
        ]);

        $this->actingAs($user)->put('/profile', [
            'phone' => '0819999999',
            'address' => 'Alamat Baru Mahasiswa',
            'study_program_id' => $studyProgram->id + 100,
            'student_number' => 'MHS-HACKED',
        ])->assertRedirect('/profile');

        $student->refresh();

        $this->assertSame('0819999999', $student->phone);
        $this->assertSame('Alamat Baru Mahasiswa', $student->address);
        $this->assertSame('MHS-002', $student->student_number);
        $this->assertSame($studyProgram->id, $student->study_program_id);
    }

    public function test_student_can_update_safe_profile_fields_but_not_name_or_nim(): void
    {
        [$user, $department, $studyProgram] = $this->createAcademicUser();

        $student = Student::create([
            'user_id' => $user->id,
            'student_number' => 'MHS-003',
            'name' => 'Mahasiswa Aman',
            'email' => 'student-safe-old@example.test',
            'phone' => '0800000005',
            'address' => 'Alamat Lama',
            'birth_date' => null,
            'enrolled_at' => null,
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
            'active' => true,
        ]);

        $this->actingAs($user)->put('/profile', [
            'name' => 'Nama Tidak Boleh Berubah',
            'student_number' => 'MHS-HACKED',
            'email' => 'student-safe-new@example.test',
            'phone' => '08122223333',
            'address' => 'Alamat Baru',
            'birth_place' => 'Karawang',
            'birth_date' => '2002-01-15',
            'enrolled_at' => '2024-09-01',
        ])->assertRedirect('/profile');

        $student->refresh();

        $this->assertSame('Mahasiswa Aman', $student->name);
        $this->assertSame('MHS-003', $student->student_number);
        $this->assertSame('student-safe-new@example.test', $student->email);
        $this->assertSame('08122223333', $student->phone);
        $this->assertSame('Alamat Baru', $student->address);
        $this->assertSame('Karawang', $student->birth_place);
        $this->assertSame('2002-01-15', $student->birth_date?->toDateString());
        $this->assertSame('2024-09-01', $student->enrolled_at?->toDateString());
    }

    public function test_lecturer_can_update_phone_and_address(): void
    {
        [$user, $department, $studyProgram] = $this->createAcademicUser();

        $lecturer = Lecturer::create([
            'user_id' => $user->id,
            'lecturer_number' => 'DSN-002',
            'name' => 'Dosen Kontak',
            'email' => 'lecturer-contact@example.test',
            'department_id' => $department->id,
            'study_program_id' => $studyProgram->id,
            'phone' => '0800000004',
            'address' => 'Alamat Lama Dosen',
            'active' => true,
        ]);

        $this->actingAs($user)->put('/profile', [
            'phone' => '0828888888',
            'address' => 'Alamat Baru Dosen',
            'lecturer_number' => 'DSN-HACKED',
            'department_id' => $department->id + 100,
        ])->assertRedirect('/profile');

        $lecturer->refresh();

        $this->assertSame('0828888888', $lecturer->phone);
        $this->assertSame('Alamat Baru Dosen', $lecturer->address);
        $this->assertSame('DSN-002', $lecturer->lecturer_number);
        $this->assertSame($department->id, $lecturer->department_id);
    }

    public function test_lecturer_can_update_safe_profile_fields_but_not_name_nidn_or_nidk(): void
    {
        [$user, $department, $studyProgram] = $this->createAcademicUser();

        $lecturer = Lecturer::create([
            'user_id' => $user->id,
            'lecturer_number' => 'DSN-003',
            'national_id_number' => '3215000000000001',
            'nip' => '198801012020121001',
            'nidn' => '0011223344',
            'nidk' => 'NIDK001122',
            'nuptk' => '1234567890123456',
            'name' => 'Dosen Aman',
            'email' => 'lecturer-safe-old@example.test',
            'department_id' => $department->id,
            'study_program_id' => $studyProgram->id,
            'phone' => '0800000006',
            'address' => 'Alamat Lama Dosen',
            'active' => true,
        ]);

        $this->actingAs($user)->put('/profile', [
            'name' => 'Nama Dosen Tidak Boleh Berubah',
            'lecturer_number' => 'DSN-HACKED',
            'nidn' => 'NIDN-HACKED',
            'nidk' => 'NIDK-HACKED',
            'email' => 'lecturer-safe-new@example.test',
            'phone' => '08244445555',
            'address' => 'Alamat Baru Dosen',
            'birth_place' => 'Bandung',
            'birth_date' => '1988-01-01',
            'national_id_number' => '3215000000000099',
            'nip' => '198801012020129999',
            'nuptk' => '9999999999999999',
            'notes' => 'Catatan dosen aman.',
        ])->assertRedirect('/profile');

        $lecturer->refresh();

        $this->assertSame('Dosen Aman', $lecturer->name);
        $this->assertSame('DSN-003', $lecturer->lecturer_number);
        $this->assertSame('0011223344', $lecturer->nidn);
        $this->assertSame('NIDK001122', $lecturer->nidk);
        $this->assertSame('lecturer-safe-new@example.test', $lecturer->email);
        $this->assertSame('08244445555', $lecturer->phone);
        $this->assertSame('Alamat Baru Dosen', $lecturer->address);
        $this->assertSame('Bandung', $lecturer->birth_place);
        $this->assertSame('1988-01-01', $lecturer->birth_date?->toDateString());
        $this->assertSame('3215000000000099', $lecturer->national_id_number);
        $this->assertSame('198801012020129999', $lecturer->nip);
        $this->assertSame('9999999999999999', $lecturer->nuptk);
        $this->assertSame('Catatan dosen aman.', $lecturer->notes);
    }

    public function test_lecturer_can_update_titles_from_profile_portal_without_changing_base_name(): void
    {
        [$user, $department, $studyProgram] = $this->createAcademicUser();

        $lecturer = Lecturer::create([
            'user_id' => $user->id,
            'lecturer_number' => 'DSN-004',
            'nidn' => '0011223355',
            'name' => 'Dosen Gelar',
            'email' => 'lecturer-title-old@example.test',
            'department_id' => $department->id,
            'study_program_id' => $studyProgram->id,
            'active' => true,
        ]);

        $this->actingAs($user)->put('/profile', [
            'name' => 'Nama Dasar Tidak Boleh Berubah',
            'nidn' => 'NIDN-HACKED',
            'front_title' => 'Dr.',
            'back_title' => 'M.Farm.',
            'email' => 'lecturer-title-new@example.test',
        ])->assertRedirect('/profile');

        $lecturer->refresh();

        $this->assertSame('Dosen Gelar', $lecturer->name);
        $this->assertSame('0011223355', $lecturer->nidn);
        $this->assertSame('Dr.', $lecturer->front_title);
        $this->assertSame('M.Farm.', $lecturer->back_title);
        $this->assertSame('lecturer-title-new@example.test', $lecturer->email);
        $this->assertSame('Dr. Dosen Gelar, M.Farm.', $lecturer->display_name_with_title);
        $this->assertNotNull($lecturer->title_updated_at);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Dr. Dosen Gelar, M.Farm.')
            ->assertSee('Gelar dosen tersedia');
    }

    public function test_lecturer_profile_completion_uses_lecturer_specific_items_and_form_fields(): void
    {
        $user = User::factory()->create(['active' => true]);
        $department = Department::create(['code' => 'D-FAR', 'name' => 'Departemen Farmasi', 'active' => true]);

        Lecturer::create([
            'user_id' => $user->id,
            'lecturer_number' => '0430037804',
            'name' => 'Dosen Lengkap',
            'email' => 'dosen-lengkap@example.test',
            'nidn' => '0430037804',
            'department_id' => $department->id,
            'active' => true,
        ]);

        $this->actingAs($user)
            ->get('/profile/edit')
            ->assertOk()
            ->assertSee('Profil Dosen')
            ->assertSee('NIK / No. KTP')
            ->assertSee('Gelar Depan')
            ->assertSee('Gelar Belakang')
            ->assertSee('NUPTK')
            ->assertSee('36%')
            ->assertSee('5/14')
            ->assertDontSee('Jenis Tendik / Staf');

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('NIK / No. KTP tersedia')
            ->assertSee('Gelar dosen tersedia')
            ->assertSee('Foto profil tersedia')
            ->assertSee('Tempat lahir tersedia')
            ->assertSee('NUPTK tersedia jika ada')
            ->assertSee('Alamat dosen tersedia');
    }

    public function test_employee_can_update_safe_profile_fields_but_not_name_or_employee_number(): void
    {
        [$user, $department, $studyProgram] = $this->createAcademicUser();

        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_number' => 'EMP-005',
            'national_id_number' => '3215000000000002',
            'name' => 'Tendik Aman',
            'staff_type' => 'staf_tu',
            'department_id' => $department->id,
            'study_program_id' => $studyProgram->id,
            'position_title' => 'Staf Lama',
            'phone' => '0800000007',
            'email' => 'employee-safe-old@example.test',
            'address' => 'Alamat Lama Tendik',
            'status' => 'active',
        ]);

        $this->actingAs($user)->put('/profile', [
            'name' => 'Nama Tendik Tidak Boleh Berubah',
            'employee_number' => 'EMP-HACKED',
            'email' => 'employee-safe-new@example.test',
            'phone' => '08355556666',
            'address' => 'Alamat Baru Tendik',
            'birth_place' => 'Bekasi',
            'birth_date' => '1990-05-10',
            'gender' => 'female',
            'national_id_number' => '3215000000000098',
            'staff_type' => 'laboran',
            'position_title' => 'Laboran',
            'notes' => 'Catatan tendik aman.',
        ])->assertRedirect('/profile');

        $employee->refresh();

        $this->assertSame('Tendik Aman', $employee->name);
        $this->assertSame('EMP-005', $employee->employee_number);
        $this->assertSame('employee-safe-new@example.test', $employee->email);
        $this->assertSame('08355556666', $employee->phone);
        $this->assertSame('Alamat Baru Tendik', $employee->address);
        $this->assertSame('Bekasi', $employee->birth_place);
        $this->assertSame('1990-05-10', $employee->birth_date?->toDateString());
        $this->assertSame('female', $employee->gender);
        $this->assertSame('3215000000000098', $employee->national_id_number);
        $this->assertSame('laboran', $employee->staff_type);
        $this->assertSame('Laboran', $employee->position_title);
        $this->assertSame('Catatan tendik aman.', $employee->notes);
    }

    public function test_employee_profile_form_shows_employee_specific_fields(): void
    {
        $user = User::factory()->create(['active' => true]);

        Employee::create([
            'user_id' => $user->id,
            'employee_number' => 'EMP-EDIT-001',
            'name' => 'Tendik Edit',
            'email' => 'tendik-edit@example.test',
            'staff_type' => 'staf_tu',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get('/profile/edit')
            ->assertOk()
            ->assertSee('Profil Tendik / Staf / Laboran')
            ->assertSee('Jenis Tendik / Staf')
            ->assertSee('Jabatan / Posisi')
            ->assertDontSee('Profil Dosen');
    }

    public function test_profile_update_does_not_change_another_users_profile(): void
    {
        [$user, $department, $studyProgram] = $this->createAcademicUser();
        [$otherUser] = $this->createAcademicUser();

        Employee::create([
            'user_id' => $user->id,
            'employee_number' => 'EMP-003',
            'name' => 'Pemilik Profil',
            'staff_type' => 'staf_tu',
            'department_id' => $department->id,
            'study_program_id' => $studyProgram->id,
            'phone' => '0800000001',
            'email' => 'owner@example.test',
            'address' => 'Alamat Pemilik',
            'status' => 'active',
        ]);

        $otherEmployee = Employee::create([
            'user_id' => $otherUser->id,
            'employee_number' => 'EMP-004',
            'name' => 'Profil Lain',
            'staff_type' => 'staf_tu',
            'phone' => '0800000002',
            'email' => 'other@example.test',
            'address' => 'Alamat Lain',
            'status' => 'active',
        ]);

        $this->actingAs($user)->put('/profile', [
            'phone' => '0877777777',
            'address' => 'Alamat Update',
        ])->assertRedirect('/profile');

        $otherEmployee->refresh();

        $this->assertSame('0800000002', $otherEmployee->phone);
        $this->assertSame('Alamat Lain', $otherEmployee->address);
    }

    /**
     * @return array{0: User, 1: Department, 2: StudyProgram}
     */
    private function createAcademicUser(array $attributes = []): array
    {
        $user = User::factory()->create(array_merge([
            'active' => true,
            'username' => 'user-'.uniqid(),
            'identity_type' => 'internal',
            'identity_number' => 'ID-'.uniqid(),
            'api_token' => hash('sha256', uniqid()),
            'must_change_password' => false,
        ], $attributes));

        $department = Department::create([
            'code' => 'FF-'.uniqid(),
            'name' => 'Fakultas Farmasi',
            'active' => true,
        ]);

        $studyProgram = StudyProgram::create([
            'department_id' => $department->id,
            'code' => 'S1-FAR-'.uniqid(),
            'name' => 'S1 Farmasi',
            'active' => true,
        ]);

        return [$user, $department, $studyProgram];
    }
}
