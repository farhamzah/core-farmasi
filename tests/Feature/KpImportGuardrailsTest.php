<?php

namespace Tests\Feature;

use App\Models\CoreImportBatch;
use App\Models\CoreImportRecord;
use App\Models\Department;
use App\Models\Role;
use App\Models\StudyProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KpImportGuardrailsTest extends TestCase
{
    use RefreshDatabase;

    private string $kpDatabasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kpDatabasePath = tempnam(sys_get_temp_dir(), 'kp-guard-');
        config()->set('database.connections.kp_source', [
            'driver' => 'sqlite',
            'database' => $this->kpDatabasePath,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        DB::purge('kp_source');
        $this->createKpSourceSchema();
        $this->seedCoreReferenceData();
        $this->seedValidKpRows();
    }

    protected function tearDown(): void
    {
        DB::purge('kp_source');

        if (isset($this->kpDatabasePath) && file_exists($this->kpDatabasePath)) {
            unlink($this->kpDatabasePath);
        }

        parent::tearDown();
    }

    public function test_import_batch_models_and_relations_work(): void
    {
        $batch = CoreImportBatch::create([
            'source' => 'kp',
            'mode' => 'execute',
            'status' => 'running',
            'options' => ['strict' => true],
        ]);

        $record = CoreImportRecord::create([
            'core_import_batch_id' => $batch->id,
            'source_table' => 'users',
            'source_id' => '1',
            'source_identifier' => 'admin@sikp.test',
            'target_table' => 'users',
            'target_id' => null,
            'action' => 'insert',
            'payload_snapshot' => ['email' => 'admin@sikp.test'],
        ]);

        $this->assertTrue($batch->records()->whereKey($record->id)->exists());
        $this->assertTrue($record->batch->is($batch));
    }

    public function test_execute_without_confirm_is_rejected(): void
    {
        $this->artisan('core:import-kp-master-data --execute')
            ->expectsOutputToContain('Missing --confirm-execute.')
            ->assertFailed();
    }

    public function test_execute_without_backup_confirmed_is_rejected(): void
    {
        $this->artisan('core:import-kp-master-data --execute --confirm-execute')
            ->expectsOutputToContain('Missing --backup-confirmed.')
            ->assertFailed();
    }

    public function test_production_execute_without_maintenance_approval_is_rejected(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('core:import-kp-master-data --execute --confirm-execute --backup-confirmed')
            ->expectsOutputToContain('Production execute requires --maintenance-window-approved.')
            ->assertFailed();
    }

    public function test_execute_is_rejected_when_strict_dry_run_has_blocker(): void
    {
        DB::connection('kp_source')->table('students')->insert([
            'id' => 1,
            'user_id' => 1,
            'nim' => null,
            'study_program' => 'S1 Farmasi',
            'status' => 'active',
        ]);

        $this->artisan('core:import-kp-master-data --execute --confirm-execute --backup-confirmed --maintenance-window-approved')
            ->expectsOutputToContain('Strict dry-run blocker:')
            ->assertFailed();
    }

    public function test_kp_admin_mapping_still_does_not_create_admin_core(): void
    {
        $this->artisan('core:import-kp-master-data --dry-run --strict --show-samples')
            ->expectsOutputToContain('"role":"admin-kp"')
            ->doesntExpectOutputToContain('"role":"admin-core"')
            ->assertSuccessful();
    }

    public function test_rollback_without_confirm_is_rejected(): void
    {
        $batch = CoreImportBatch::create([
            'source' => 'kp',
            'mode' => 'execute',
            'status' => 'completed',
        ]);

        $this->artisan("core:rollback-kp-import {$batch->id} --dry-run")
            ->expectsOutputToContain('Rollback refused: missing --confirm-rollback.')
            ->assertFailed();
    }

    public function test_dry_run_does_not_write_import_batches_or_records(): void
    {
        $this->artisan('core:import-kp-master-data --dry-run --strict')
            ->assertSuccessful();

        $this->assertSame(0, CoreImportBatch::count());
        $this->assertSame(0, CoreImportRecord::count());
    }

    private function createKpSourceSchema(): void
    {
        Schema::connection('kp_source')->create('users', function ($table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::connection('kp_source')->create('roles', function ($table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('label')->nullable();
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
            ['id' => 1, 'name' => 'KP Admin', 'email' => 'admin@sikp.test', 'password' => 'hash', 'status' => 'active'],
        ]);
        DB::connection('kp_source')->table('roles')->insert([
            ['id' => 1, 'name' => 'admin', 'label' => 'Admin'],
        ]);
        DB::connection('kp_source')->table('user_roles')->insert([
            ['id' => 1, 'user_id' => 1, 'role_id' => 1],
        ]);
    }
}
