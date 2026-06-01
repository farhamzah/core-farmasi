<?php

namespace Tests\Feature;

use App\Filament\Pages\CoreImportCenter;
use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\CoreImportBatch;
use App\Models\CoreImportRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeadershipAssignment;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserAppAccess;
use App\Services\CoreImportTemplateService;
use App\Services\CoreImportValidationService;
use App\Services\CoreImportRollbackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CoreImportCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_import_center(): void
    {
        $user = $this->coreAdmin();

        $this->actingAs($user)
            ->get('/admin/import-center')
            ->assertOk();
    }

    public function test_guest_is_redirected_to_login_from_import_center(): void
    {
        $this->get('/admin/import-center')
            ->assertRedirect('/admin/login');
    }

    public function test_unauthorized_user_cannot_open_import_center(): void
    {
        $user = User::factory()->create(['active' => true]);
        $role = Role::create(['name' => 'mahasiswa', 'label' => 'Mahasiswa', 'active' => true]);

        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get('/admin/import-center')
            ->assertForbidden();
    }

    public function test_import_registry_exposes_required_types(): void
    {
        $types = app(CoreImportTemplateService::class)->enabledTypes();

        $this->assertSame([
            'users',
            'students',
            'lecturers',
            'employees',
            'departments',
            'study_programs',
            'roles',
            'user_role_assignments',
            'user_app_accesses',
        ], array_keys($types));
    }

    public function test_templates_do_not_have_password_columns(): void
    {
        $templates = app(CoreImportTemplateService::class);

        foreach (array_keys($templates->enabledTypes()) as $type) {
            $this->assertTrue($templates->assertNoPasswordColumns($type), "{$type} template has a password column.");
        }
    }

    public function test_birth_date_columns_exist_for_relevant_templates(): void
    {
        $templates = app(CoreImportTemplateService::class);

        foreach (['users', 'students', 'lecturers', 'employees'] as $type) {
            $this->assertContains('birth_date', $templates->headings($type));
        }
    }

    public function test_authorized_admin_can_download_core_templates(): void
    {
        $this->actingAs($this->coreAdmin());

        $templates = app(CoreImportTemplateService::class);

        foreach (array_keys($templates->enabledTypes()) as $type) {
            Livewire::test(CoreImportCenter::class)
                ->call('downloadTemplate', $type)
                ->assertFileDownloaded($templates->filename($type));
        }
    }

    public function test_template_download_does_not_modify_master_data(): void
    {
        $this->actingAs($this->coreAdmin());

        $beforeStudents = Student::count();

        Livewire::test(CoreImportCenter::class)
            ->call('downloadTemplate', 'students')
            ->assertFileDownloaded('core-students-template.xlsx');

        $this->assertSame($beforeStudents, Student::count());
    }

    public function test_upload_valid_users_csv_generates_preview_and_batch(): void
    {
        Storage::fake('local');

        $this->actingAs($this->coreAdmin());

        Livewire::test(CoreImportCenter::class)
            ->set('importType', 'users')
            ->set('importFile', $this->csv('users.csv', "name,username,identity_type,identity_number,email,birth_date\nAdmin,ADM001,admin,ADM001,admin@example.test,01/01/1990\n"))
            ->call('uploadAndPreview')
            ->assertHasNoErrors()
            ->assertSet('previewResult.status', 'preview_ready')
            ->assertSet('previewResult.is_valid_for_preview', true);

        $batch = CoreImportBatch::latest('id')->first();

        $this->assertSame('users', $batch->source);
        $this->assertSame('validation', $batch->mode);
        $this->assertSame('decision_ready', $batch->status);
        $this->assertSame(1, $batch->summary['validation']['valid_rows']);
        $this->assertTrue(Storage::disk('local')->exists($batch->options['stored_path']));
        $this->assertStringStartsWith('core-imports/pending/', $batch->options['stored_path']);
        $this->assertFalse(Storage::disk('public')->exists($batch->options['stored_path']));
    }

    public function test_upload_valid_students_csv_generates_preview(): void
    {
        Storage::fake('local');

        $this->actingAs($this->coreAdmin());

        Livewire::test(CoreImportCenter::class)
            ->set('importType', 'students')
            ->set('importFile', $this->csv('students.csv', "nim,name,study_program_code,birth_date\n230001,Nama Mahasiswa,S1-FAR,07/08/2001\n"))
            ->call('uploadAndPreview')
            ->assertHasNoErrors()
            ->assertSet('previewResult.status', 'preview_ready')
            ->assertSet('previewResult.is_valid_for_preview', true);
    }

    public function test_upload_valid_employees_csv_generates_preview(): void
    {
        Storage::fake('local');

        $this->actingAs($this->coreAdmin());

        Livewire::test(CoreImportCenter::class)
            ->set('importType', 'employees')
            ->set('importFile', $this->csv('employees.csv', "name,staff_type,birth_date\nNama Staff,tendik,11/12/1991\n"))
            ->call('uploadAndPreview')
            ->assertHasNoErrors()
            ->assertSet('previewResult.status', 'preview_ready')
            ->assertSet('previewResult.is_valid_for_preview', true);
    }

    public function test_upload_with_missing_required_columns_reports_error(): void
    {
        Storage::fake('local');

        $this->actingAs($this->coreAdmin());

        Livewire::test(CoreImportCenter::class)
            ->set('importType', 'students')
            ->set('importFile', $this->csv('students-missing.csv', "nim,email\n230001,student@example.test\n"))
            ->call('uploadAndPreview')
            ->assertHasNoErrors()
            ->assertSet('previewResult.status', 'invalid_heading')
            ->assertSet('previewResult.missing_required_columns', ['name', 'study_program_code']);
    }

    public function test_upload_with_password_column_is_rejected_for_preview(): void
    {
        Storage::fake('local');

        $this->actingAs($this->coreAdmin());

        Livewire::test(CoreImportCenter::class)
            ->set('importType', 'users')
            ->set('importFile', $this->csv('users-password.csv', "name,username,identity_type,identity_number,password\nAdmin,ADM001,admin,ADM001,secret\n"))
            ->call('uploadAndPreview')
            ->assertHasNoErrors()
            ->assertSet('previewResult.status', 'invalid_heading')
            ->assertSet('previewResult.password_columns', ['password']);

        $batch = CoreImportBatch::latest('id')->first();

        $this->assertStringNotContainsString('secret', json_encode($batch->summary));
    }

    public function test_upload_rejects_non_spreadsheet_file(): void
    {
        Storage::fake('local');

        $this->actingAs($this->coreAdmin());

        Livewire::test(CoreImportCenter::class)
            ->set('importType', 'students')
            ->set('importFile', UploadedFile::fake()->createWithContent('bad.txt', 'not a spreadsheet'))
            ->call('uploadAndPreview')
            ->assertHasErrors(['importFile']);
    }

    public function test_upload_preview_does_not_modify_master_data(): void
    {
        Storage::fake('local');

        $this->actingAs($this->coreAdmin());

        $counts = [
            'users' => User::count(),
            'students' => Student::count(),
            'lecturers' => Lecturer::count(),
            'employees' => Employee::count(),
        ];

        Livewire::test(CoreImportCenter::class)
            ->set('importType', 'students')
            ->set('importFile', $this->csv('students.csv', "nim,name,study_program_code,birth_date\n230001,Nama Mahasiswa,S1-FAR,07/08/2001\n"))
            ->call('uploadAndPreview')
            ->assertHasNoErrors();

        $this->assertSame($counts['users'], User::count());
        $this->assertSame($counts['students'], Student::count());
        $this->assertSame($counts['lecturers'], Lecturer::count());
        $this->assertSame($counts['employees'], Employee::count());
    }

    public function test_student_row_validation_detects_valid_missing_and_conflict_rows(): void
    {
        $studyProgram = $this->studyProgram();
        Student::create([
            'student_number' => '230001',
            'name' => 'Existing Student',
            'email' => 'existing-student@example.test',
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
            'active' => true,
        ]);

        $result = $this->validateCsv('students', "nim,name,study_program_code,email,birth_date\n230002,Valid Student,{$studyProgram->code},valid-student@example.test,07/08/2001\n,Missing Nim,{$studyProgram->code},missing@example.test,07/08/2001\n230001,Conflict Student,{$studyProgram->code},conflict@example.test,07/08/2001\n230003,Unknown Program,NOPE,unknown@example.test,07/08/2001\n");

        $this->assertSame(4, $result['total_rows_checked']);
        $this->assertSame('create_new', $result['rows'][0]['suggested_action']);
        $this->assertSame('invalid', $result['rows'][1]['suggested_action']);
        $this->assertContains('nim wajib diisi.', $result['rows'][1]['errors']);
        $this->assertSame('needs_admin_decision', $result['rows'][2]['suggested_action']);
        $this->assertContains('NIM sudah ada di students.', $result['rows'][2]['conflicts']);
        $this->assertSame('invalid', $result['rows'][3]['suggested_action']);
        $this->assertContains('study_program_code tidak ditemukan.', $result['rows'][3]['errors']);
    }

    public function test_lecturer_row_validation_detects_conflicts(): void
    {
        $department = Department::create(['code' => 'FAR', 'name' => 'Farmasi', 'active' => true]);
        Lecturer::create([
            'lecturer_number' => '0011223344',
            'name' => 'Existing Lecturer',
            'email' => 'existing-lecturer@example.test',
            'department_id' => $department->id,
            'active' => true,
        ]);

        $result = $this->validateCsv('lecturers', "name,nidn,email,department_code,birth_date\nValid Lecturer,0099887766,valid-lecturer@example.test,FAR,09/10/1988\n,Missing Name,missing-name@example.test,FAR,09/10/1988\nConflict Lecturer,0011223344,conflict-lecturer@example.test,FAR,09/10/1988\nUnknown Dept,0077665544,unknown-dept@example.test,NOPE,09/10/1988\n");

        $this->assertSame('create_new', $result['rows'][0]['suggested_action']);
        $this->assertSame('invalid', $result['rows'][1]['suggested_action']);
        $this->assertContains('name wajib diisi.', $result['rows'][1]['errors']);
        $this->assertSame('needs_admin_decision', $result['rows'][2]['suggested_action']);
        $this->assertContains('NIDN/NIP sudah ada di lecturers.', $result['rows'][2]['conflicts']);
        $this->assertSame('invalid', $result['rows'][3]['suggested_action']);
        $this->assertContains('department_code tidak ditemukan.', $result['rows'][3]['errors']);
    }

    public function test_employee_row_validation_detects_conflicts_and_invalid_staff_type(): void
    {
        Employee::factory()->create([
            'employee_number' => 'EMP001',
            'national_id_number' => '3276000000000001',
        ]);

        $result = $this->validateCsv('employees', "name,staff_type,employee_number,national_id_number,email,birth_date\nValid Staff,tendik,EMP002,3276000000000002,valid-staff@example.test,11/12/1991\nBad Staff,bad_type,EMP003,3276000000000003,bad-staff@example.test,11/12/1991\nConflict Staff,tendik,EMP001,3276000000000004,conflict-staff@example.test,11/12/1991\nConflict Nik,tendik,EMP004,3276000000000001,conflict-nik@example.test,11/12/1991\n");

        $this->assertSame('create_new', $result['rows'][0]['suggested_action']);
        $this->assertSame('invalid', $result['rows'][1]['suggested_action']);
        $this->assertContains('staff_type harus salah satu: tendik, admin, staf_tu, laboran, other.', $result['rows'][1]['errors']);
        $this->assertSame('needs_admin_decision', $result['rows'][2]['suggested_action']);
        $this->assertContains('employee_number sudah ada di employees.', $result['rows'][2]['conflicts']);
        $this->assertSame('needs_admin_decision', $result['rows'][3]['suggested_action']);
        $this->assertContains('national_id_number sudah ada di employees.', $result['rows'][3]['conflicts']);
    }

    public function test_validation_ignores_password_and_app_role_columns_without_mutating_access(): void
    {
        $studyProgram = $this->studyProgram();
        $beforeAccess = UserAppAccess::count();
        $beforeUsers = User::count();

        $result = $this->validateCsv('students', "nim,name,study_program_code,password,app_code,role_slug,app_role\n230010,Student With Role,{$studyProgram->code},secret,kp-farmasi,pembimbing,reviewer\n");

        $this->assertSame('create_new', $result['rows'][0]['suggested_action']);
        $this->assertArrayNotHasKey('password', $result['rows'][0]['normalized_data']);
        $this->assertArrayNotHasKey('app_code', $result['rows'][0]['normalized_data']);
        $this->assertContains('Kolom password tidak diproses.', $result['rows'][0]['warnings']);
        $this->assertContains('Kolom app role/app access tidak diproses pada profile import.', $result['rows'][0]['warnings']);
        $this->assertStringNotContainsString('secret', json_encode($result));
        $this->assertSame($beforeAccess, UserAppAccess::count());
        $this->assertSame($beforeUsers, User::count());
    }

    public function test_users_row_validation_detects_conflicts_and_prohibited_columns_without_mutation(): void
    {
        User::factory()->create([
            'name' => 'Existing User',
            'username' => 'EXIST001',
            'email' => 'existing-user@example.test',
            'identity_type' => 'admin',
            'identity_number' => 'ID-EXIST-001',
        ]);

        $beforeUsers = User::count();

        $result = $this->validateCsv('users', "name,username,identity_type,identity_number,email,birth_date\nNew User,NEW001,admin,ID-NEW-001,new-user@example.test,01/01/1990\nExisting Username,EXIST001,admin,ID-NEW-002,other@example.test,01/01/1990\nMissing Required,,admin,,missing@example.test,01/01/1990\n");

        $this->assertSame('create_new', $result['rows'][0]['suggested_action']);
        $this->assertSame('needs_admin_decision', $result['rows'][1]['suggested_action']);
        $this->assertContains('username sudah ada di users.', $result['rows'][1]['conflicts']);
        $this->assertSame('invalid', $result['rows'][2]['suggested_action']);
        $this->assertContains('username wajib diisi.', $result['rows'][2]['errors']);

        $sensitiveResult = $this->validateCsv('users', "name,username,identity_type,identity_number,email,birth_date,password,api_token\nSensitive User,SENS001,admin,ID-SENS-001,sensitive@example.test,01/01/1990,secret,token-value\n");

        $this->assertSame('invalid', $sensitiveResult['rows'][0]['suggested_action']);
        $this->assertContains('Kolom password tidak diperbolehkan.', $sensitiveResult['rows'][0]['errors']);
        $this->assertContains('Kolom api_token tidak diperbolehkan.', $sensitiveResult['rows'][0]['errors']);
        $this->assertArrayNotHasKey('password', $sensitiveResult['rows'][0]['normalized_data']);
        $this->assertArrayNotHasKey('api_token', $sensitiveResult['rows'][0]['normalized_data']);
        $this->assertStringNotContainsString('secret', json_encode($sensitiveResult));
        $this->assertStringNotContainsString('token-value', json_encode($sensitiveResult));
        $this->assertSame($beforeUsers, User::count());
    }

    public function test_user_role_assignment_validation_uses_global_roles_without_assigning(): void
    {
        $user = User::factory()->create(['username' => 'ROLE001', 'active' => true]);
        $role = Role::create(['name' => 'admin-core', 'label' => 'Admin Core', 'active' => true]);
        $assignedRole = Role::create(['name' => 'existing-role', 'label' => 'Existing Role', 'active' => true]);
        $user->roles()->attach($assignedRole);

        $beforeAssignments = $user->roles()->count();

        $result = $this->validateCsv('user_role_assignments', "username,role_slug,action,app_code\nROLE001,admin-core,assign,core-farmasi\nUNKNOWN,admin-core,assign,\nROLE001,missing-role,assign,\nROLE001,existing-role,assign,\n");

        $this->assertSame('assign', $result['rows'][0]['suggested_action']);
        $this->assertContains('app_code tidak diproses di import role global. Gunakan user_app_accesses untuk app role.', $result['rows'][0]['warnings']);
        $this->assertArrayNotHasKey('app_code', $result['rows'][0]['normalized_data']);
        $this->assertSame('invalid', $result['rows'][1]['suggested_action']);
        $this->assertContains('username tidak ditemukan di users.', $result['rows'][1]['errors']);
        $this->assertSame('invalid', $result['rows'][2]['suggested_action']);
        $this->assertContains('role_slug tidak ditemukan di global roles aktif.', $result['rows'][2]['errors']);
        $this->assertSame('skip', $result['rows'][3]['suggested_action']);
        $this->assertContains('User sudah memiliki role global ini.', $result['rows'][3]['conflicts']);
        $this->assertSame($beforeAssignments, $user->fresh()->roles()->count());
    }

    public function test_user_app_access_validation_uses_dynamic_app_role_catalog_without_mutation(): void
    {
        $user = User::factory()->create(['username' => 'APP001', 'active' => true]);
        $application = CoreApplication::create([
            'app_code' => 'dossier-dosen',
            'name' => 'Dossier Dosen',
            'is_active' => true,
            'is_public_visible' => false,
            'requires_login' => true,
        ]);
        CoreApplicationRole::create([
            'core_application_id' => $application->id,
            'app_code' => 'dossier-dosen',
            'role_slug' => 'reviewer',
            'role_name' => 'Reviewer',
            'is_active' => true,
        ]);
        CoreApplicationRole::create([
            'core_application_id' => $application->id,
            'app_code' => 'dossier-dosen',
            'role_slug' => 'inactive-role',
            'role_name' => 'Inactive Role',
            'is_active' => false,
        ]);
        $inactiveApplication = CoreApplication::create([
            'app_code' => 'inactive-app',
            'name' => 'Inactive App',
            'is_active' => false,
            'is_public_visible' => false,
            'requires_login' => true,
        ]);
        CoreApplicationRole::create([
            'core_application_id' => $inactiveApplication->id,
            'app_code' => 'inactive-app',
            'role_slug' => 'viewer',
            'role_name' => 'Viewer',
            'is_active' => true,
        ]);
        UserAppAccess::create([
            'user_id' => $user->id,
            'app_code' => 'dossier-dosen',
            'role_slug' => 'reviewer',
            'is_active' => true,
        ]);

        $beforeAccesses = UserAppAccess::count();

        $result = $this->validateCsv('user_app_accesses', "username,app_code,role_slug,action\nAPP001,dossier-dosen,reviewer,assign\nAPP001,dossier-dosen,reviewer,deactivate\nAPP001,unknown-app,reviewer,assign\nAPP001,inactive-app,viewer,assign\nAPP001,dossier-dosen,missing-role,assign\nAPP001,dossier-dosen,inactive-role,assign\n");

        $this->assertSame('skip', $result['rows'][0]['suggested_action']);
        $this->assertContains('User sudah memiliki app access aktif ini.', $result['rows'][0]['conflicts']);
        $this->assertSame('deactivate', $result['rows'][1]['suggested_action']);
        $this->assertSame('invalid', $result['rows'][2]['suggested_action']);
        $this->assertContains('app_code tidak ditemukan di core_applications.', $result['rows'][2]['errors']);
        $this->assertSame('invalid', $result['rows'][3]['suggested_action']);
        $this->assertContains('app_code tidak aktif.', $result['rows'][3]['errors']);
        $this->assertSame('invalid', $result['rows'][4]['suggested_action']);
        $this->assertContains('role_slug tidak ditemukan untuk app_code tersebut.', $result['rows'][4]['errors']);
        $this->assertSame('invalid', $result['rows'][5]['suggested_action']);
        $this->assertContains('role_slug untuk app_code tersebut tidak aktif.', $result['rows'][5]['errors']);
        $this->assertSame($beforeAccesses, UserAppAccess::count());
    }

    public function test_users_import_execute_requires_birth_date_when_birth_date_strategy_is_configured(): void
    {
        config(['core_identity.initial_password_strategy' => 'birth_date']);

        Storage::fake('local');

        $admin = $this->coreAdmin();
        $beforeUsers = User::count();

        $this->actingAs($admin);

        Livewire::test(CoreImportCenter::class)
            ->set('importType', 'users')
            ->set('importFile', $this->csv('users.csv', "name,username,identity_type,identity_number,email\nNo Execute User,NOEXEC001,admin,NOEXEC001,no-execute@example.test\n"))
            ->call('uploadAndPreview')
            ->assertSet('validationResult.valid_rows', 1)
            ->assertSet('decisionSummary.executable_rows', 1)
            ->assertSet('executionSummary', null)
            ->call('executeImport')
            ->assertSet('executionSummary.failed_count', 1);

        $this->assertSame($beforeUsers, User::count());
    }

    public function test_execute_users_create_update_and_name_based_initial_password(): void
    {
        Storage::fake('local');

        $admin = $this->coreAdmin();
        $this->actingAs($admin);

        Livewire::test(CoreImportCenter::class)
            ->set('importType', 'users')
            ->set('importFile', $this->csv('users.csv', "name,username,identity_type,identity_number,email,birth_date\nImport User,USR-IMP-001,admin,USR-IMP-001,usr-imp-001@example.test,01/01/1990\n"))
            ->call('uploadAndPreview')
            ->call('executeImport')
            ->assertSet('executionSummary.created_count', 1)
            ->assertSet('executionSummary.users_created_count', 0);

        $user = User::where('username', 'USR-IMP-001')->firstOrFail();
        $this->assertTrue(Hash::check('Import User', $user->password));
        $this->assertTrue($user->must_change_password);

        $existing = User::factory()->create([
            'name' => 'Old Import User',
            'username' => 'USR-UPD-001',
            'email' => 'usr-upd-001@example.test',
            'identity_type' => 'admin',
            'identity_number' => 'USR-UPD-001',
            'must_change_password' => true,
        ]);
        $oldPassword = $existing->password;

        $batch = CoreImportBatch::create([
            'source' => 'users',
            'mode' => 'validation',
            'status' => 'decision_ready',
            'operator_id' => $admin->id,
        ]);
        CoreImportRecord::create([
            'core_import_batch_id' => $batch->id,
            'source_table' => 'users',
            'source_id' => '2',
            'source_identifier' => 'USR-UPD-001',
            'target_table' => 'users',
            'action' => 'decision_preview',
            'validation_status' => 'conflict',
            'suggested_action' => 'needs_admin_decision',
            'admin_decision' => 'update_existing',
            'normalized_data' => [
                'name' => 'Updated Import User',
                'username' => 'USR-UPD-001',
                'identity_type' => 'admin',
                'identity_number' => 'USR-UPD-001',
                'email' => 'usr-upd-001-updated@example.test',
                'must_change_password' => 'false',
            ],
            'execution_status' => 'not_executed',
        ]);

        $summary = app(\App\Services\CoreImportExecutionService::class)->execute($batch, $admin);
        $this->assertSame(1, $summary['updated_count']);

        $existing->refresh();
        $this->assertSame('Updated Import User', $existing->name);
        $this->assertSame('usr-upd-001-updated@example.test', $existing->email);
        $this->assertSame($oldPassword, $existing->password);

        Livewire::test(CoreImportCenter::class)
            ->set('importType', 'users')
            ->set('importFile', $this->csv('users-no-birth.csv', "name,username,identity_type,identity_number,email\nNo Birth User,USR-NO-BIRTH,admin,USR-NO-BIRTH,usr-no-birth@example.test\n"))
            ->call('uploadAndPreview')
            ->call('executeImport')
            ->assertSet('executionSummary.created_count', 1);

        $noBirthUser = User::where('username', 'USR-NO-BIRTH')->firstOrFail();
        $this->assertTrue(Hash::check('No Birth User', $noBirthUser->password));
        $this->assertTrue($noBirthUser->must_change_password);
    }

    public function test_execute_user_role_assignment_and_rollback_only_import_created_assignment(): void
    {
        Storage::fake('local');

        $admin = $this->coreAdmin();
        $user = User::factory()->create(['username' => 'ROLE-EXEC-001', 'active' => true]);
        $role = Role::create(['name' => 'role-exec', 'label' => 'Role Execute', 'active' => true]);
        $existingRole = Role::create(['name' => 'role-existing', 'label' => 'Role Existing', 'active' => true]);
        $user->roles()->attach($existingRole);

        $this->actingAs($admin);

        $component = Livewire::test(CoreImportCenter::class)
            ->set('importType', 'user_role_assignments')
            ->set('importFile', $this->csv('role-assign.csv', "username,role_slug,action\nROLE-EXEC-001,role-exec,assign\nROLE-EXEC-001,role-existing,assign\n"))
            ->call('uploadAndPreview')
            ->call('executeImport')
            ->assertSet('executionSummary.role_assignments_assigned_count', 1)
            ->assertSet('executionSummary.skipped_count', 1);

        $this->assertTrue($user->fresh()->roles()->where('roles.id', $role->id)->exists());
        $this->assertTrue($user->fresh()->roles()->where('roles.id', $existingRole->id)->exists());

        $component
            ->call('rollbackImport')
            ->assertSet('rollbackSummary.rolled_back_count', 1)
            ->assertSet('rollbackSummary.skipped_count', 1);

        $this->assertFalse($user->fresh()->roles()->where('roles.id', $role->id)->exists());
        $this->assertTrue($user->fresh()->roles()->where('roles.id', $existingRole->id)->exists());
    }

    public function test_rollback_users_create_new_soft_deletes_only_when_safe(): void
    {
        Storage::fake('local');

        $admin = $this->coreAdmin();
        $this->actingAs($admin);

        $component = Livewire::test(CoreImportCenter::class)
            ->set('importType', 'users')
            ->set('importFile', $this->csv('users.csv', "name,username,identity_type,identity_number,email,birth_date\nRollback User,USR-ROLL-001,admin,USR-ROLL-001,usr-roll-001@example.test,01/01/1990\n"))
            ->call('uploadAndPreview')
            ->call('executeImport');

        $user = User::where('username', 'USR-ROLL-001')->firstOrFail();

        $component
            ->call('rollbackImport')
            ->assertSet('rollbackSummary.rolled_back_count', 1);

        $this->assertTrue(User::withTrashed()->find($user->id)->trashed());

        $unsafeComponent = Livewire::test(CoreImportCenter::class)
            ->set('importType', 'users')
            ->set('importFile', $this->csv('users-unsafe.csv', "name,username,identity_type,identity_number,email,birth_date\nUnsafe User,USR-ROLL-002,admin,USR-ROLL-002,usr-roll-002@example.test,01/01/1990\n"))
            ->call('uploadAndPreview')
            ->call('executeImport');

        $unsafeUser = User::where('username', 'USR-ROLL-002')->firstOrFail();
        $role = Role::create(['name' => 'unsafe-role', 'label' => 'Unsafe Role', 'active' => true]);
        $unsafeUser->roles()->attach($role);

        $unsafeComponent
            ->call('rollbackImport')
            ->assertSet('rollbackSummary.manual_review_count', 1);

        $this->assertFalse(User::withTrashed()->find($unsafeUser->id)->trashed());
    }

    public function test_rollback_users_update_existing_restores_snapshot(): void
    {
        $admin = $this->coreAdmin();
        $user = User::factory()->create([
            'name' => 'Before User Rollback',
            'username' => 'USR-ROLL-UPD',
            'email' => 'before-user-rollback@example.test',
            'identity_type' => 'admin',
            'identity_number' => 'USR-ROLL-UPD',
        ]);
        $batch = CoreImportBatch::create([
            'source' => 'users',
            'mode' => 'validation',
            'status' => 'decision_ready',
            'operator_id' => $admin->id,
        ]);
        CoreImportRecord::create([
            'core_import_batch_id' => $batch->id,
            'source_table' => 'users',
            'source_id' => '2',
            'source_identifier' => 'USR-ROLL-UPD',
            'target_table' => 'users',
            'action' => 'decision_preview',
            'validation_status' => 'conflict',
            'suggested_action' => 'needs_admin_decision',
            'admin_decision' => 'update_existing',
            'normalized_data' => [
                'name' => 'After User Rollback',
                'username' => 'USR-ROLL-UPD',
                'identity_type' => 'admin',
                'identity_number' => 'USR-ROLL-UPD',
                'email' => 'after-user-rollback@example.test',
            ],
            'execution_status' => 'not_executed',
        ]);

        app(\App\Services\CoreImportExecutionService::class)->execute($batch, $admin);
        app(CoreImportRollbackService::class)->rollback($batch->fresh(), $admin);

        $user->refresh();
        $this->assertSame('Before User Rollback', $user->name);
        $this->assertSame('before-user-rollback@example.test', $user->email);
    }

    public function test_execute_user_app_access_assign_deactivate_and_rollback(): void
    {
        Storage::fake('local');

        $admin = $this->coreAdmin();
        $user = User::factory()->create(['username' => 'APP-EXEC-001', 'active' => true]);
        $application = CoreApplication::create([
            'app_code' => 'app-exec',
            'name' => 'App Execute',
            'is_active' => true,
            'is_public_visible' => false,
            'requires_login' => true,
        ]);
        CoreApplicationRole::create([
            'core_application_id' => $application->id,
            'app_code' => 'app-exec',
            'role_slug' => 'reviewer',
            'role_name' => 'Reviewer',
            'is_active' => true,
        ]);
        CoreApplicationRole::create([
            'core_application_id' => $application->id,
            'app_code' => 'app-exec',
            'role_slug' => 'validator',
            'role_name' => 'Validator',
            'is_active' => true,
        ]);
        $existingInactive = UserAppAccess::create([
            'user_id' => $user->id,
            'app_code' => 'app-exec',
            'role_slug' => 'validator',
            'is_active' => false,
            'deactivated_at' => now()->subDay(),
        ]);

        $this->actingAs($admin);

        $component = Livewire::test(CoreImportCenter::class)
            ->set('importType', 'user_app_accesses')
            ->set('importFile', $this->csv('app-access.csv', "username,app_code,role_slug,action\nAPP-EXEC-001,app-exec,reviewer,assign\nAPP-EXEC-001,app-exec,validator,assign\n"))
            ->call('uploadAndPreview');

        $reactivateRecord = CoreImportRecord::where('source_table', 'user_app_accesses')
            ->where('source_identifier', 'APP-EXEC-001 app-exec validator')
            ->latest('id')
            ->firstOrFail();

        $component
            ->set("decisionRows.{$reactivateRecord->id}.admin_decision", 'assign')
            ->call('saveImportDecisions')
            ->call('executeImport')
            ->assertSet('executionSummary.app_accesses_assigned_count', 2);

        $createdAccess = UserAppAccess::where('user_id', $user->id)->where('app_code', 'app-exec')->where('role_slug', 'reviewer')->firstOrFail();
        $this->assertTrue($createdAccess->is_active);
        $this->assertTrue($existingInactive->fresh()->is_active);

        $component
            ->call('rollbackImport')
            ->assertSet('rollbackSummary.rolled_back_count', 2);

        $this->assertFalse($createdAccess->fresh()->is_active);
        $this->assertFalse($existingInactive->fresh()->is_active);

        $deactivateUser = User::factory()->create(['username' => 'APP-DEACT-001', 'active' => true]);
        $deactivateAccess = UserAppAccess::create([
            'user_id' => $deactivateUser->id,
            'app_code' => 'app-exec',
            'role_slug' => 'reviewer',
            'is_active' => true,
            'activated_at' => now(),
        ]);

        Livewire::test(CoreImportCenter::class)
            ->set('importType', 'user_app_accesses')
            ->set('importFile', $this->csv('app-deactivate.csv', "username,app_code,role_slug,action\nAPP-DEACT-001,app-exec,reviewer,deactivate\n"))
            ->call('uploadAndPreview')
            ->assertSet('decisionSummary.executable_rows', 1)
            ->call('executeImport')
            ->assertSet('executionSummary.app_accesses_deactivated_count', 1)
            ->call('rollbackImport')
            ->assertSet('rollbackSummary.rolled_back_count', 1);

        $this->assertTrue($deactivateAccess->fresh()->is_active);
    }

    public function test_upload_valid_students_file_shows_validation_summary(): void
    {
        Storage::fake('local');

        $studyProgram = $this->studyProgram();

        $this->actingAs($this->coreAdmin());

        Livewire::test(CoreImportCenter::class)
            ->set('importType', 'students')
            ->set('importFile', $this->csv('students.csv', "nim,name,study_program_code,birth_date\n230001,Nama Mahasiswa,{$studyProgram->code},07/08/2001\n"))
            ->call('uploadAndPreview')
            ->assertHasNoErrors()
            ->assertSet('validationResult.valid_rows', 1)
            ->assertSet('validationResult.rows.0.suggested_action', 'create_new');

        $batch = CoreImportBatch::latest('id')->first();

        $this->assertSame('validation', $batch->mode);
        $this->assertSame('decision_ready', $batch->status);
        $this->assertSame(1, $batch->summary['validation']['valid_rows']);
    }

    public function test_validation_results_are_persisted_as_decision_records(): void
    {
        Storage::fake('local');

        $studyProgram = $this->studyProgram();

        $this->actingAs($this->coreAdmin());

        Livewire::test(CoreImportCenter::class)
            ->set('importType', 'students')
            ->set('importFile', $this->csv('students.csv', "nim,name,study_program_code,birth_date,password\n230001,Nama Mahasiswa,{$studyProgram->code},07/08/2001,secret\n"))
            ->call('uploadAndPreview')
            ->assertSet('previewResult.status', 'invalid_heading');

        $this->assertSame(0, CoreImportRecord::count());

        Livewire::test(CoreImportCenter::class)
            ->set('importType', 'students')
            ->set('importFile', $this->csv('students-clean.csv', "nim,name,study_program_code,birth_date\n230001,Nama Mahasiswa,{$studyProgram->code},07/08/2001\n"))
            ->call('uploadAndPreview')
            ->assertSet('decisionSummary.executable_rows', 1)
            ->assertSet('decisionSummary.pending_decisions', 0);

        $record = CoreImportRecord::latest('id')->first();

        $this->assertSame('valid', $record->validation_status);
        $this->assertSame('create_new', $record->suggested_action);
        $this->assertSame('create_new', $record->admin_decision);
        $this->assertSame('not_executed', $record->execution_status);
        $this->assertStringNotContainsString('secret', json_encode($record->toArray()));
    }

    public function test_conflict_and_invalid_rows_have_safe_default_decisions(): void
    {
        Storage::fake('local');

        $studyProgram = $this->studyProgram();
        Student::create([
            'student_number' => '230001',
            'name' => 'Existing Student',
            'email' => 'existing-conflict@example.test',
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
            'active' => true,
        ]);

        $this->actingAs($this->coreAdmin());

        Livewire::test(CoreImportCenter::class)
            ->set('importType', 'students')
            ->set('importFile', $this->csv('students.csv', "nim,name,study_program_code,birth_date\n230001,Conflict Student,{$studyProgram->code},07/08/2001\n,Missing Nim,{$studyProgram->code},07/08/2001\n"))
            ->call('uploadAndPreview')
            ->assertSet('decisionSummary.pending_decisions', 1)
            ->assertSet('decisionSummary.invalid_decisions', 1);

        $this->assertTrue(CoreImportRecord::where('validation_status', 'conflict')->where('admin_decision', 'needs_admin_decision')->exists());
        $this->assertTrue(CoreImportRecord::where('validation_status', 'invalid')->where('admin_decision', 'invalid')->exists());
    }

    public function test_admin_can_save_import_decisions_without_master_data_mutation(): void
    {
        Storage::fake('local');

        $studyProgram = $this->studyProgram();
        Student::create([
            'student_number' => '230001',
            'name' => 'Existing Student',
            'email' => 'existing-decision@example.test',
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
            'active' => true,
        ]);
        $admin = $this->coreAdmin();
        $counts = [
            'users' => User::count(),
            'students' => Student::count(),
            'lecturers' => Lecturer::count(),
            'employees' => Employee::count(),
        ];

        $this->actingAs($admin);

        $component = Livewire::test(CoreImportCenter::class)
            ->set('importType', 'students')
            ->set('importFile', $this->csv('students.csv', "nim,name,study_program_code,birth_date\n230001,Conflict Student,{$studyProgram->code},07/08/2001\n230002,Valid Student,{$studyProgram->code},07/08/2001\n,Invalid Student,{$studyProgram->code},07/08/2001\n"))
            ->call('uploadAndPreview');

        $records = CoreImportRecord::orderBy('source_id')->get();
        $conflict = $records->firstWhere('validation_status', 'conflict');
        $valid = $records->firstWhere('validation_status', 'valid');
        $invalid = $records->firstWhere('validation_status', 'invalid');

        $component
            ->set("decisionRows.{$conflict->id}.admin_decision", 'update_existing')
            ->set("decisionRows.{$conflict->id}.decision_note", 'Update existing saat execute nanti.')
            ->set("decisionRows.{$valid->id}.admin_decision", 'skip')
            ->set("decisionRows.{$invalid->id}.admin_decision", 'skip')
            ->call('saveImportDecisions')
            ->assertSet('decisionSummary.pending_decisions', 0)
            ->assertSet('decisionSummary.executable_rows', 1)
            ->assertSet('decisionSummary.skipped_rows', 2);

        $this->assertSame('update_existing', $conflict->fresh()->admin_decision);
        $this->assertSame($admin->id, $conflict->fresh()->decided_by);
        $this->assertNotNull($conflict->fresh()->decided_at);
        $this->assertSame('skip', $valid->fresh()->admin_decision);
        $this->assertSame('skip', $invalid->fresh()->admin_decision);

        $batch = CoreImportBatch::latest('id')->first();
        $this->assertSame('ready', $batch->decision_status);
        $this->assertSame(0, $batch->pending_decision_rows_count);
        $this->assertSame(1, $batch->executable_rows_count);

        $this->assertSame($counts['users'], User::count());
        $this->assertSame($counts['students'], Student::count());
        $this->assertSame($counts['lecturers'], Lecturer::count());
        $this->assertSame($counts['employees'], Employee::count());
    }

    public function test_invalid_row_cannot_be_marked_create_new(): void
    {
        Storage::fake('local');

        $studyProgram = $this->studyProgram();

        $this->actingAs($this->coreAdmin());

        $component = Livewire::test(CoreImportCenter::class)
            ->set('importType', 'students')
            ->set('importFile', $this->csv('students.csv', "nim,name,study_program_code,birth_date\n,Invalid Student,{$studyProgram->code},07/08/2001\n"))
            ->call('uploadAndPreview');

        $invalid = CoreImportRecord::where('validation_status', 'invalid')->firstOrFail();

        $component
            ->set("decisionRows.{$invalid->id}.admin_decision", 'create_new')
            ->call('saveImportDecisions');

        $this->assertSame('invalid', $invalid->fresh()->admin_decision);
    }

    public function test_execute_students_create_new_creates_student_and_user_with_hashed_initial_password(): void
    {
        Storage::fake('local');

        $studyProgram = $this->studyProgram();
        $admin = $this->coreAdmin();

        $this->actingAs($admin);

        Livewire::test(CoreImportCenter::class)
            ->set('importType', 'students')
            ->set('importFile', $this->csv('students.csv', "nim,name,study_program_code,email,birth_date,username\n230050,Execute Student,{$studyProgram->code},execute-student@example.test,07/08/2001,230050\n"))
            ->call('uploadAndPreview')
            ->call('executeImport')
            ->assertSet('executionSummary.executed_count', 1)
            ->assertSet('executionSummary.created_count', 1)
            ->assertSet('executionSummary.users_created_count', 1);

        $student = Student::where('student_number', '230050')->firstOrFail();
        $user = $student->user;

        $this->assertNotNull($user);
        $this->assertSame('student', $user->identity_type);
        $this->assertSame('230050', $user->identity_number);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('Execute Student', $user->password));
        $this->assertNotSame('Execute Student', $user->password);
        $this->assertSame('executed', CoreImportRecord::latest('id')->first()->execution_status);
    }

    public function test_execute_skip_and_invalid_rows_do_not_change_master_data(): void
    {
        Storage::fake('local');

        $studyProgram = $this->studyProgram();
        $admin = $this->coreAdmin();
        $counts = [
            'users' => User::count(),
            'students' => Student::count(),
        ];

        $this->actingAs($admin);

        $component = Livewire::test(CoreImportCenter::class)
            ->set('importType', 'students')
            ->set('importFile', $this->csv('students.csv', "nim,name,study_program_code,email,birth_date\n230060,Skipped Student,{$studyProgram->code},skip-student@example.test,07/08/2001\n,Invalid Student,{$studyProgram->code},invalid-student@example.test,07/08/2001\n230061,Executed Student,{$studyProgram->code},executed-student@example.test,07/08/2001\n"))
            ->call('uploadAndPreview');

        $valid = CoreImportRecord::where('validation_status', 'valid')->firstOrFail();
        $invalid = CoreImportRecord::where('validation_status', 'invalid')->firstOrFail();

        $component
            ->set("decisionRows.{$valid->id}.admin_decision", 'skip')
            ->set("decisionRows.{$invalid->id}.admin_decision", 'skip')
            ->call('saveImportDecisions')
            ->call('executeImport')
            ->assertSet('executionSummary.skipped_count', 2)
            ->assertSet('executionSummary.executed_count', 1);

        $this->assertSame($counts['users'] + 1, User::count());
        $this->assertSame($counts['students'] + 1, Student::count());
        $this->assertFalse(Student::where('student_number', '230060')->exists());
        $this->assertSame('skipped', $valid->fresh()->execution_status);
        $this->assertSame('skipped', $invalid->fresh()->execution_status);
    }

    public function test_execute_students_update_existing_updates_only_approved_existing_student(): void
    {
        Storage::fake('local');

        $studyProgram = $this->studyProgram();
        $student = Student::create([
            'student_number' => '230070',
            'name' => 'Old Student',
            'email' => 'old-student@example.test',
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
            'active' => true,
        ]);

        $this->actingAs($this->coreAdmin());

        $component = Livewire::test(CoreImportCenter::class)
            ->set('importType', 'students')
            ->set('importFile', $this->csv('students.csv', "nim,name,study_program_code,email,birth_date\n230070,Updated Student,{$studyProgram->code},updated-student@example.test,07/08/2001\n"))
            ->call('uploadAndPreview');

        $record = CoreImportRecord::where('validation_status', 'conflict')->firstOrFail();

        $component
            ->set("decisionRows.{$record->id}.admin_decision", 'update_existing')
            ->call('saveImportDecisions')
            ->call('executeImport')
            ->assertSet('executionSummary.updated_count', 1);

        $student->refresh();

        $this->assertSame('Updated Student', $student->name);
        $this->assertSame('updated-student@example.test', $student->email);
        $this->assertSame('executed', $record->fresh()->execution_status);
    }

    public function test_execute_duplicate_student_create_new_fails_safely(): void
    {
        Storage::fake('local');

        $studyProgram = $this->studyProgram();
        Student::create([
            'student_number' => '230080',
            'name' => 'Existing Student',
            'email' => 'existing-230080@example.test',
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
            'active' => true,
        ]);

        $this->actingAs($this->coreAdmin());

        $component = Livewire::test(CoreImportCenter::class)
            ->set('importType', 'students')
            ->set('importFile', $this->csv('students.csv', "nim,name,study_program_code,email,birth_date\n230080,Duplicate Student,{$studyProgram->code},duplicate-student@example.test,07/08/2001\n"))
            ->call('uploadAndPreview');

        $record = CoreImportRecord::where('validation_status', 'conflict')->firstOrFail();

        $component
            ->set("decisionRows.{$record->id}.admin_decision", 'create_new')
            ->call('saveImportDecisions')
            ->call('executeImport')
            ->assertSet('executionSummary.failed_count', 1);

        $this->assertSame(1, Student::where('student_number', '230080')->count());
        $this->assertSame('failed', $record->fresh()->execution_status);
    }

    public function test_execute_lecturer_and_employee_create_new(): void
    {
        Storage::fake('local');

        $department = Department::firstOrCreate(['code' => 'FAR'], ['name' => 'Farmasi', 'active' => true]);
        $admin = $this->coreAdmin();

        $this->actingAs($admin);

        Livewire::test(CoreImportCenter::class)
            ->set('importType', 'lecturers')
            ->set('importFile', $this->csv('lecturers.csv', "name,nidn,email,department_code,birth_date,username\nExecute Lecturer,99887766,execute-lecturer@example.test,{$department->code},09/10/1988,99887766\n"))
            ->call('uploadAndPreview')
            ->call('executeImport')
            ->assertSet('executionSummary.created_count', 1);

        $lecturer = Lecturer::where('lecturer_number', '99887766')->firstOrFail();
        $this->assertSame('Execute Lecturer', $lecturer->name);
        $this->assertTrue($lecturer->user->must_change_password);
        $this->assertTrue(Hash::check('Execute Lecturer', $lecturer->user->password));

        Livewire::test(CoreImportCenter::class)
            ->set('importType', 'employees')
            ->set('importFile', $this->csv('employees.csv', "name,staff_type,employee_number,email,birth_date,username\nExecute Employee,tendik,EMP-EXEC-001,execute-employee@example.test,11/12/1991,EMP-EXEC-001\n"))
            ->call('uploadAndPreview')
            ->call('executeImport')
            ->assertSet('executionSummary.created_count', 1);

        $employee = Employee::where('employee_number', 'EMP-EXEC-001')->firstOrFail();
        $this->assertSame('Execute Employee', $employee->name);
        $this->assertTrue($employee->user->must_change_password);
        $this->assertTrue(Hash::check('Execute Employee', $employee->user->password));
    }

    public function test_execute_employee_update_existing_can_create_user_with_name_based_initial_password(): void
    {
        Storage::fake('local');

        $employee = Employee::create([
            'employee_number' => 'EMP-UPD-001',
            'name' => 'Old Employee',
            'staff_type' => 'tendik',
            'email' => 'old-employee@example.test',
            'status' => 'active',
        ]);

        $this->actingAs($this->coreAdmin());
        $component = Livewire::test(CoreImportCenter::class)
            ->set('importType', 'employees')
            ->set('importFile', $this->csv('employees.csv', "name,staff_type,employee_number,email,position_title\nUpdated Employee,tendik,EMP-UPD-001,updated-employee@example.test,Staff Akademik\n"))
            ->call('uploadAndPreview');

        $record = CoreImportRecord::where('validation_status', 'conflict')->firstOrFail();

        $component
            ->set("decisionRows.{$record->id}.admin_decision", 'update_existing')
            ->call('saveImportDecisions')
            ->call('executeImport')
            ->assertSet('executionSummary.updated_count', 1)
            ->assertSet('executionSummary.users_created_count', 1);

        $employee->refresh();

        $this->assertSame('Updated Employee', $employee->name);
        $this->assertSame('Staff Akademik', $employee->position_title);
        $this->assertNotNull($employee->user_id);
        $this->assertTrue(Hash::check('Updated Employee', $employee->user->password));
        $this->assertTrue($employee->user->must_change_password);
    }

    public function test_execute_does_not_modify_user_app_access_or_leadership_assignments(): void
    {
        Storage::fake('local');

        $studyProgram = $this->studyProgram();
        $admin = $this->coreAdmin();
        $accessCount = UserAppAccess::count();
        $leadershipCount = LeadershipAssignment::count();

        $this->actingAs($admin);

        Livewire::test(CoreImportCenter::class)
            ->set('importType', 'students')
            ->set('importFile', $this->csv('students.csv', "nim,name,study_program_code,email,birth_date\n230090,No Access Import,{$studyProgram->code},no-access@example.test,07/08/2001\n"))
            ->call('uploadAndPreview')
            ->call('executeImport')
            ->assertSet('executionSummary.executed_count', 1);

        $this->assertSame($accessCount, UserAppAccess::count());
        $this->assertSame($leadershipCount, LeadershipAssignment::count());
    }

    public function test_rollback_create_new_student_soft_deletes_student_and_import_created_user(): void
    {
        Storage::fake('local');

        $studyProgram = $this->studyProgram();
        $this->actingAs($this->coreAdmin());

        $component = Livewire::test(CoreImportCenter::class)
            ->set('importType', 'students')
            ->set('importFile', $this->csv('students.csv', "nim,name,study_program_code,email,birth_date,username\n230100,Rollback Student,{$studyProgram->code},rollback-student@example.test,07/08/2001,230100\n"))
            ->call('uploadAndPreview')
            ->call('executeImport');

        $student = Student::where('student_number', '230100')->firstOrFail();
        $userId = $student->user_id;

        $component
            ->call('rollbackImport')
            ->assertSet('rollbackSummary.rolled_back_count', 1);

        $this->assertTrue(Student::withTrashed()->find($student->id)->trashed());
        $this->assertTrue(User::withTrashed()->find($userId)->trashed());
        $this->assertSame('rolled_back', CoreImportRecord::latest('id')->first()->rollback_status);
    }

    public function test_rollback_create_new_lecturer_and_employee_soft_deletes_targets(): void
    {
        Storage::fake('local');

        $department = Department::firstOrCreate(['code' => 'FAR'], ['name' => 'Farmasi', 'active' => true]);
        $this->actingAs($this->coreAdmin());

        $lecturerComponent = Livewire::test(CoreImportCenter::class)
            ->set('importType', 'lecturers')
            ->set('importFile', $this->csv('lecturers.csv', "name,nidn,email,department_code,birth_date,username\nRollback Lecturer,ROLL001,rollback-lecturer@example.test,{$department->code},09/10/1988,ROLL001\n"))
            ->call('uploadAndPreview')
            ->call('executeImport');

        $lecturer = Lecturer::where('lecturer_number', 'ROLL001')->firstOrFail();

        $lecturerComponent->call('rollbackImport');

        $this->assertTrue(Lecturer::withTrashed()->find($lecturer->id)->trashed());

        $employeeComponent = Livewire::test(CoreImportCenter::class)
            ->set('importType', 'employees')
            ->set('importFile', $this->csv('employees.csv', "name,staff_type,employee_number,email,birth_date,username\nRollback Employee,tendik,EMP-ROLL-001,rollback-employee@example.test,11/12/1991,EMP-ROLL-001\n"))
            ->call('uploadAndPreview')
            ->call('executeImport');

        $employee = Employee::where('employee_number', 'EMP-ROLL-001')->firstOrFail();

        $employeeComponent->call('rollbackImport');

        $this->assertTrue(Employee::withTrashed()->find($employee->id)->trashed());
    }

    public function test_rollback_update_existing_restores_previous_snapshot(): void
    {
        Storage::fake('local');

        $studyProgram = $this->studyProgram();
        $student = Student::create([
            'student_number' => '230110',
            'name' => 'Before Rollback',
            'email' => 'before-rollback@example.test',
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
            'active' => true,
        ]);

        $this->actingAs($this->coreAdmin());

        $component = Livewire::test(CoreImportCenter::class)
            ->set('importType', 'students')
            ->set('importFile', $this->csv('students.csv', "nim,name,study_program_code,email,birth_date\n230110,After Import,{$studyProgram->code},after-import@example.test,07/08/2001\n"))
            ->call('uploadAndPreview');

        $record = CoreImportRecord::where('validation_status', 'conflict')->firstOrFail();

        $component
            ->set("decisionRows.{$record->id}.admin_decision", 'update_existing')
            ->call('saveImportDecisions')
            ->call('executeImport')
            ->call('rollbackImport')
            ->assertSet('rollbackSummary.rolled_back_count', 1);

        $student->refresh();

        $this->assertSame('Before Rollback', $student->name);
        $this->assertSame('before-rollback@example.test', $student->email);
        $this->assertSame('rolled_back', $record->fresh()->rollback_status);
    }

    public function test_rollback_update_existing_without_previous_snapshot_becomes_manual_review(): void
    {
        $admin = $this->coreAdmin();
        $batch = CoreImportBatch::create([
            'source' => 'students',
            'mode' => 'validation',
            'status' => 'executed',
            'operator_id' => $admin->id,
        ]);
        $studyProgram = $this->studyProgram();
        $student = Student::create([
            'student_number' => '230120',
            'name' => 'No Snapshot',
            'email' => 'no-snapshot@example.test',
            'study_program_id' => $studyProgram->id,
            'status' => 'active',
            'active' => true,
        ]);
        CoreImportRecord::create([
            'core_import_batch_id' => $batch->id,
            'source_table' => 'students',
            'source_id' => '2',
            'source_identifier' => '230120',
            'target_table' => 'students',
            'target_type' => Student::class,
            'target_id' => (string) $student->id,
            'action' => 'updated',
            'admin_decision' => 'update_existing',
            'validation_status' => 'conflict',
            'execution_status' => 'executed',
            'executed_action' => 'update_existing',
            'executed_by' => $admin->id,
            'executed_at' => now(),
        ]);

        $summary = app(CoreImportRollbackService::class)->rollback($batch, $admin);

        $this->assertSame(1, $summary['manual_review_count']);
        $this->assertSame('manual_review', $batch->fresh()->rollback_status);
    }

    public function test_rollback_does_not_delete_created_user_when_user_has_app_access(): void
    {
        Storage::fake('local');

        $studyProgram = $this->studyProgram();
        $this->actingAs($this->coreAdmin());

        $component = Livewire::test(CoreImportCenter::class)
            ->set('importType', 'students')
            ->set('importFile', $this->csv('students.csv', "nim,name,study_program_code,email,birth_date,username\n230130,Used User,{$studyProgram->code},used-user@example.test,07/08/2001,230130\n"))
            ->call('uploadAndPreview')
            ->call('executeImport');

        $student = Student::where('student_number', '230130')->firstOrFail();
        UserAppAccess::create([
            'user_id' => $student->user_id,
            'app_code' => 'core-farmasi',
            'role_slug' => 'admin-core',
            'is_active' => true,
        ]);

        $component->call('rollbackImport');

        $this->assertTrue(Student::withTrashed()->find($student->id)->trashed());
        $this->assertFalse(User::withTrashed()->find($student->user_id)->trashed());
        $this->assertSame('manual_review', CoreImportRecord::latest('id')->first()->rollback_status);
    }

    public function test_rollback_skip_and_invalid_records_are_not_changed_destructively(): void
    {
        Storage::fake('local');

        $studyProgram = $this->studyProgram();
        $this->actingAs($this->coreAdmin());

        $component = Livewire::test(CoreImportCenter::class)
            ->set('importType', 'students')
            ->set('importFile', $this->csv('students.csv', "nim,name,study_program_code,email,birth_date\n230140,Skip Rollback,{$studyProgram->code},skip-rollback@example.test,07/08/2001\n,Invalid Rollback,{$studyProgram->code},invalid-rollback@example.test,07/08/2001\n230141,Execute Rollback,{$studyProgram->code},execute-rollback@example.test,07/08/2001\n"))
            ->call('uploadAndPreview');

        $validRecords = CoreImportRecord::where('validation_status', 'valid')->orderBy('source_id')->get();
        $invalid = CoreImportRecord::where('validation_status', 'invalid')->firstOrFail();

        $component
            ->set("decisionRows.{$validRecords[0]->id}.admin_decision", 'skip')
            ->set("decisionRows.{$invalid->id}.admin_decision", 'skip')
            ->call('saveImportDecisions')
            ->call('executeImport')
            ->call('rollbackImport');

        $this->assertSame('skipped', $validRecords[0]->fresh()->rollback_status);
        $this->assertSame('skipped', $invalid->fresh()->rollback_status);
        $this->assertTrue(Student::withTrashed()->where('student_number', '230141')->firstOrFail()->trashed());
    }

    public function test_rollback_cannot_run_twice_destructively(): void
    {
        Storage::fake('local');

        $studyProgram = $this->studyProgram();
        $this->actingAs($this->coreAdmin());

        $component = Livewire::test(CoreImportCenter::class)
            ->set('importType', 'students')
            ->set('importFile', $this->csv('students.csv', "nim,name,study_program_code,email,birth_date\n230150,Twice Rollback,{$studyProgram->code},twice-rollback@example.test,07/08/2001\n"))
            ->call('uploadAndPreview')
            ->call('executeImport')
            ->call('rollbackImport')
            ->call('rollbackImport');

        $component->assertSet('rollbackSummary.already_rolled_back_count', 1);

        $this->assertSame('already_rolled_back', CoreImportRecord::latest('id')->first()->rollback_status);
    }

    public function test_rollback_does_not_touch_user_app_accesses_or_leadership_assignments(): void
    {
        Storage::fake('local');

        $studyProgram = $this->studyProgram();
        $admin = $this->coreAdmin();
        $accessCount = UserAppAccess::count();
        $leadershipCount = LeadershipAssignment::count();

        $this->actingAs($admin);

        Livewire::test(CoreImportCenter::class)
            ->set('importType', 'students')
            ->set('importFile', $this->csv('students.csv', "nim,name,study_program_code,email,birth_date\n230160,Rollback Scope,{$studyProgram->code},rollback-scope@example.test,07/08/2001\n"))
            ->call('uploadAndPreview')
            ->call('executeImport')
            ->call('rollbackImport')
            ->assertSet('rollbackSummary.rolled_back_count', 1);

        $this->assertSame($accessCount, UserAppAccess::count());
        $this->assertSame($leadershipCount, LeadershipAssignment::count());
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

    private function csv(string $filename, string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($filename, $content);
    }

    private function validateCsv(string $type, string $content): array
    {
        $path = tempnam(sys_get_temp_dir(), 'core-import-validation-');
        file_put_contents($path, $content);

        try {
            return app(CoreImportValidationService::class)->validate($type, $path);
        } finally {
            @unlink($path);
        }
    }

    private function studyProgram(): StudyProgram
    {
        $department = Department::firstOrCreate(
            ['code' => 'FAR'],
            ['name' => 'Farmasi', 'active' => true],
        );

        return StudyProgram::firstOrCreate(
            ['code' => 'S1-FAR'],
            ['department_id' => $department->id, 'name' => 'S1 Farmasi', 'active' => true],
        );
    }
}
