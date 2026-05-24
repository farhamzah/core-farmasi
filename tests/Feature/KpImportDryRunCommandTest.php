<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Role;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\KpMasterDataDryRunAuditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KpImportDryRunCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $kpDatabasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kpDatabasePath = tempnam(sys_get_temp_dir(), 'kp-source-');

        config()->set('database.connections.kp_source', [
            'driver' => 'sqlite',
            'database' => $this->kpDatabasePath,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        DB::purge('kp_source');
        $this->createKpSourceSchema();
        $this->seedCoreReferenceData();
    }

    protected function tearDown(): void
    {
        DB::purge('kp_source');

        if (isset($this->kpDatabasePath) && file_exists($this->kpDatabasePath)) {
            unlink($this->kpDatabasePath);
        }

        parent::tearDown();
    }

    public function test_command_is_registered(): void
    {
        $this->artisan('list core')
            ->expectsOutputToContain('core:import-kp-master-data')
            ->assertSuccessful();
    }

    public function test_dry_run_runs_without_writing_core_data(): void
    {
        $this->seedValidKpRows();

        $beforeUsers = User::count();

        $this->artisan('core:import-kp-master-data --dry-run --only=users,roles,user_roles --limit=10')
            ->expectsOutputToContain('Mode: dry-run only; no Core import writes were performed.')
            ->expectsOutputToContain('users: insert=1')
            ->assertSuccessful();

        $this->assertSame($beforeUsers, User::count());
    }

    public function test_role_mapping_is_correct_and_kp_admin_does_not_become_admin_core(): void
    {
        $this->assertSame('admin-kp', KpMasterDataDryRunAuditor::mapRole('admin'));
        $this->assertSame('koordinator-kp', KpMasterDataDryRunAuditor::mapRole('koordinator_kp'));
        $this->assertSame('pembimbing-dalam', KpMasterDataDryRunAuditor::mapRole('pembimbing_dalam'));
        $this->assertSame('pembimbing-lapangan', KpMasterDataDryRunAuditor::mapRole('pembimbing_lapangan'));
        $this->assertNotSame('admin-core', KpMasterDataDryRunAuditor::mapRole('admin'));
    }

    public function test_study_program_and_department_mapping_are_loaded_from_config(): void
    {
        $this->assertSame('S1 Farmasi', KpMasterDataDryRunAuditor::mapStudyProgram('Farmasi'));
        $this->assertSame('Farmasi Klinis', KpMasterDataDryRunAuditor::mapDepartment('Farmasi Klinis'));
    }

    public function test_strict_dry_run_blocks_unmapped_study_program_or_department(): void
    {
        $this->seedValidKpRows();
        DB::connection('kp_source')->table('students')->insert([
            'id' => 1,
            'user_id' => 1,
            'nim' => '999',
            'study_program' => 'Tidak Ada',
            'status' => 'active',
        ]);
        DB::connection('kp_source')->table('lecturers')->insert([
            'id' => 1,
            'user_id' => 1,
            'nidn_nip' => '123',
            'employee_number' => null,
            'study_program' => 'S1 Farmasi',
            'department' => 'Tidak Ada',
            'status' => 'active',
        ]);

        $report = app(KpMasterDataDryRunAuditor::class)->run(['strict' => true]);

        $this->assertContains('Tidak Ada', $report['normalization']['study_program_unmatched']);
        $this->assertContains('Tidak Ada', $report['normalization']['department_unmatched']);
        $this->assertNotEmpty($report['blockers']);
        $this->assertFalse($report['safe_for_d2']);
    }

    public function test_strict_dry_run_passes_for_mapped_reference_values(): void
    {
        $this->seedValidKpRows();
        DB::connection('kp_source')->table('users')->insert([
            'id' => 2,
            'name' => 'KP Lecturer',
            'email' => 'lecturer@example.test',
            'password' => 'hash',
            'status' => 'active',
        ]);
        DB::connection('kp_source')->table('students')->insert([
            'id' => 1,
            'user_id' => 1,
            'nim' => '221063120001',
            'study_program' => 'Farmasi',
            'status' => 'active',
        ]);
        DB::connection('kp_source')->table('lecturers')->insert([
            'id' => 1,
            'user_id' => 2,
            'nidn_nip' => '0012345601',
            'employee_number' => null,
            'study_program' => 'Farmasi',
            'department' => 'Farmasi Klinis',
            'status' => 'active',
        ]);

        $report = app(KpMasterDataDryRunAuditor::class)->run(['strict' => true]);

        $this->assertSame([], $report['normalization']['study_program_unmatched']);
        $this->assertSame([], $report['normalization']['department_unmatched']);
        $this->assertSame([], $report['blockers']);
        $this->assertTrue($report['safe_for_d2']);
    }

    public function test_duplicate_and_orphan_checker_reports_blockers(): void
    {
        DB::connection('kp_source')->table('users')->insert([
            ['id' => 1, 'name' => 'One', 'email' => 'DUP@example.test', 'password' => 'hash', 'status' => 'active'],
            ['id' => 2, 'name' => 'Two', 'email' => 'dup@example.test', 'password' => 'hash', 'status' => 'active'],
        ]);
        DB::connection('kp_source')->table('roles')->insert([
            ['id' => 1, 'name' => 'admin', 'label' => 'Admin'],
        ]);
        DB::connection('kp_source')->table('user_roles')->insert([
            ['id' => 1, 'user_id' => 99, 'role_id' => 1],
        ]);

        $report = app(KpMasterDataDryRunAuditor::class)->run(['only' => 'users,user_roles']);

        $this->assertSame(1, $report['duplicates']['kp_user_email']);
        $this->assertSame(1, $report['orphans']['user_roles_user_missing']);
        $this->assertNotEmpty($report['blockers']);
        $this->assertFalse($report['safe_for_d2']);
    }

    public function test_execute_mode_is_guarded(): void
    {
        $this->artisan('core:import-kp-master-data --execute')
            ->expectsOutputToContain('Execute refused by D3A guardrails:')
            ->expectsOutputToContain('Missing --confirm-execute.')
            ->assertFailed();
    }

    private function createKpSourceSchema(): void
    {
        Schema::connection('kp_source')->create('users', function ($table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('status')->default('active');
            $table->boolean('must_change_password')->default(true);
            $table->boolean('profile_completed')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('kp_source')->create('roles', function ($table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::connection('kp_source')->create('user_roles', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();
        });

        Schema::connection('kp_source')->create('students', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('nim')->nullable();
            $table->string('study_program')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::connection('kp_source')->create('lecturers', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('nidn_nip')->nullable();
            $table->string('employee_number')->nullable();
            $table->string('study_program')->nullable();
            $table->string('department')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::connection('kp_source')->create('field_supervisors', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('institution_name')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    private function seedCoreReferenceData(): void
    {
        foreach (['admin-kp', 'koordinator-kp', 'mahasiswa', 'pembimbing-dalam', 'pembimbing-lapangan', 'penguji'] as $name) {
            Role::create(['name' => $name, 'label' => $name, 'active' => true]);
        }

        $department = Department::create(['code' => 'FF', 'name' => 'Fakultas Farmasi', 'active' => true]);
        Department::create(['code' => 'FARKLIN', 'name' => 'Farmasi Klinis', 'active' => true]);
        StudyProgram::create(['department_id' => $department->id, 'code' => 'S1-FARMASI', 'name' => 'S1 Farmasi', 'active' => true]);
    }

    private function seedValidKpRows(): void
    {
        DB::connection('kp_source')->table('users')->insert([
            ['id' => 1, 'name' => 'KP Admin', 'email' => 'admin-kp@example.test', 'password' => 'hash', 'status' => 'active'],
        ]);

        DB::connection('kp_source')->table('roles')->insert([
            ['id' => 1, 'name' => 'admin', 'label' => 'Admin'],
        ]);

        DB::connection('kp_source')->table('user_roles')->insert([
            ['id' => 1, 'user_id' => 1, 'role_id' => 1],
        ]);
    }
}
