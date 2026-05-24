<?php

namespace App\Console\Commands;

use App\Models\CoreImportBatch;
use App\Models\CoreImportRecord;
use App\Services\KpMasterDataDryRunAuditor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class ImportKpMasterDataCommand extends Command
{
    protected $signature = 'core:import-kp-master-data
        {--dry-run : Preview the import without writing data}
        {--execute : Write approved changes to the Core database}
        {--source=kp : Source system key}
        {--limit= : Limit preview rows per source table}
        {--only= : Comma-separated sections: users,roles,user_roles,students,lecturers,field_supervisors}
        {--report-json : Save the dry-run report as JSON}
        {--show-samples : Show sample planned rows}
        {--strict : Treat unresolved normalization values as blockers}
        {--confirm-execute : Explicitly confirm execute intent}
        {--backup-confirmed : Confirm Core and KP backups are complete and restore-tested}
        {--maintenance-window-approved : Confirm production maintenance window approval}
        {--operator-email= : Operator email for future execute audit}
        {--batch-note= : Note stored with the future execute batch}
        {--force-approved : Non-production test-only guard bypass for future execute tests}';

    protected $description = 'Dry-run KP master-data import readiness for Core';

    public function handle(KpMasterDataDryRunAuditor $auditor): int
    {
        if ($this->option('execute')) {
            return $this->guardExecute($auditor);
        }

        $report = $auditor->run([
            'source' => $this->option('source'),
            'limit' => $this->option('limit'),
            'only' => $this->option('only'),
            'show_samples' => (bool) $this->option('show-samples'),
            'strict' => (bool) $this->option('strict'),
        ]);

        $this->renderReport($report);

        if ($this->option('report-json')) {
            $path = $this->writeJsonReport($report);
            $this->info("JSON report written: {$path}");
        }

        return self::SUCCESS;
    }

    private function guardExecute(KpMasterDataDryRunAuditor $auditor): int
    {
        $guardErrors = [];

        if (! $this->option('confirm-execute')) {
            $guardErrors[] = 'Missing --confirm-execute.';
        }

        if (! $this->option('backup-confirmed')) {
            $guardErrors[] = 'Missing --backup-confirmed.';
        }

        if (App::environment('production') && ! $this->option('maintenance-window-approved')) {
            $guardErrors[] = 'Production execute requires --maintenance-window-approved.';
        }

        if ($this->option('force-approved') && App::environment('production')) {
            $guardErrors[] = '--force-approved is not allowed in production.';
        }

        $report = $auditor->run([
            'source' => $this->option('source'),
            'limit' => $this->option('limit'),
            'only' => $this->option('only'),
            'show_samples' => false,
            'strict' => true,
        ]);

        if (! $report['connectivity']['core']) {
            $guardErrors[] = 'Core DB is not readable.';
        }

        if (! $report['connectivity']['kp_source']) {
            $guardErrors[] = 'KP source DB is not readable.';
        }

        foreach ($report['blockers'] as $blocker) {
            $guardErrors[] = "Strict dry-run blocker: {$blocker}";
        }

        if ($guardErrors) {
            $this->error('Execute refused by D3A guardrails:');
            foreach (array_unique($guardErrors) as $error) {
                $this->error("  - {$error}");
            }

            return self::FAILURE;
        }

        return $this->executeImport($auditor, $report);
    }

    private function executeImport(KpMasterDataDryRunAuditor $auditor, array $preflightReport): int
    {
        $operator = $this->resolveOperator();
        $batch = CoreImportBatch::create([
            'source' => (string) $this->option('source'),
            'mode' => 'execute',
            'status' => 'running',
            'started_at' => now(),
            'operator_id' => $operator?->id,
            'options' => [
                'operator_email' => $this->option('operator-email'),
                'batch_note' => $this->option('batch-note'),
                'backup_confirmed' => (bool) $this->option('backup-confirmed'),
                'maintenance_window_approved' => (bool) $this->option('maintenance-window-approved'),
            ],
            'summary' => [
                'preflight' => $this->summarizeReport($preflightReport),
            ],
        ]);

        try {
            $summary = DB::transaction(function () use ($batch): array {
                $summary = $this->emptyExecuteSummary();

                $this->importUsers($batch, $summary);
                $this->importRoles($batch, $summary);
                $this->importUserRolesAndAccesses($batch, $summary);
                $this->importStudents($batch, $summary);
                $this->importLecturers($batch, $summary);
                $this->importFieldSupervisorIdentityAccess($batch, $summary);

                return $summary;
            });

            $postReport = $auditor->run([
                'source' => $this->option('source'),
                'limit' => $this->option('limit'),
                'only' => $this->option('only'),
                'show_samples' => false,
                'strict' => true,
            ]);

            $batch->update([
                'status' => 'completed',
                'finished_at' => now(),
                'summary' => [
                    'preflight' => $this->summarizeReport($preflightReport),
                    'execute' => $summary,
                    'post_execute_dry_run' => $this->summarizeReport($postReport),
                ],
            ]);

            $this->info("KP master-data import completed. Batch ID: {$batch->id}");
            foreach ($summary as $section => $counts) {
                $this->line(sprintf(
                    '  %s: insert=%d update=%d skip=%d blocker=%d',
                    $section,
                    $counts['insert'],
                    $counts['update'],
                    $counts['skip'],
                    $counts['blocker'],
                ));
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $batch->update([
                'status' => 'failed',
                'finished_at' => now(),
                'summary' => [
                    'preflight' => $this->summarizeReport($preflightReport),
                    'error' => $exception->getMessage(),
                ],
            ]);

            $this->error("KP master-data import failed. Batch ID: {$batch->id}");
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function renderReport(array $report): void
    {
        $this->info('KP -> Core master-data import dry-run');
        $this->line('Mode: dry-run only; no Core import writes were performed.');
        $this->newLine();

        $this->line('Connectivity:');
        $this->line('  Core DB: '.($report['connectivity']['core'] ? 'connected' : 'failed'));
        $this->line('  KP source DB: '.($report['connectivity']['kp_source'] ? 'connected' : 'failed'));
        $this->newLine();

        if (! $report['connectivity']['core'] || ! $report['connectivity']['kp_source']) {
            $this->renderIssues($report);
            return;
        }

        $this->line('Counts:');
        foreach (['kp', 'core'] as $group) {
            $this->line("  {$group}:");
            foreach ($report['counts'][$group] as $table => $count) {
                $this->line("    {$table}: ".($count ?? 'missing'));
            }
        }

        $this->newLine();
        $this->line('Planned changes:');
        foreach ($report['planned'] as $section => $plan) {
            $this->line(sprintf(
                '  %s: insert=%d update=%d skip=%d blockers=%d',
                $section,
                $plan['insert'],
                $plan['update'],
                $plan['skip'],
                $plan['blocker'],
            ));
        }

        $this->newLine();
        $this->line('Duplicate checks:');
        foreach ($report['duplicates'] as $key => $count) {
            $this->line("  {$key}: {$count}");
        }

        $this->newLine();
        $this->line('Orphan checks:');
        foreach ($report['orphans'] as $key => $count) {
            $this->line("  {$key}: ".($count ?? 'not checked'));
        }

        if ($report['samples']) {
            $this->newLine();
            $this->line('Samples:');
            foreach ($report['samples'] as $section => $rows) {
                $this->line("  {$section}:");
                foreach ($rows as $row) {
                    $this->line('    '.json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                }
            }
        }

        $this->renderIssues($report);
        $this->newLine();
        $this->line('Safe for D2: '.($report['safe_for_d2'] ? 'yes' : 'no'));
    }

    private function renderIssues(array $report): void
    {
        $this->newLine();
        $this->line('Warnings:');
        if ($report['warnings']) {
            foreach ($report['warnings'] as $warning) {
                $this->warn("  - {$warning}");
            }
        } else {
            $this->line('  none');
        }

        $this->newLine();
        $this->line('Blockers:');
        if ($report['blockers']) {
            foreach ($report['blockers'] as $blocker) {
                $this->error("  - {$blocker}");
            }
        } else {
            $this->line('  none');
        }
    }

    private function writeJsonReport(array $report): string
    {
        $directory = storage_path('app/reports');
        File::ensureDirectoryExists($directory);

        $path = $directory.'/kp-core-import-dry-run-'.now()->format('Ymd-His').'.json';
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $path;
    }

    private function resolveOperator(): ?object
    {
        $email = $this->normalize($this->option('operator-email'));

        return $email ? DB::table('users')->whereRaw('LOWER(TRIM(email)) = ?', [$email])->first() : null;
    }

    private function emptyExecuteSummary(): array
    {
        return collect(['users', 'roles', 'user_roles', 'user_app_accesses', 'students', 'lecturers', 'field_supervisors'])
            ->mapWithKeys(fn (string $section) => [$section => ['insert' => 0, 'update' => 0, 'skip' => 0, 'blocker' => 0]])
            ->all();
    }

    private function summarizeReport(array $report): array
    {
        return [
            'generated_at' => $report['generated_at'] ?? null,
            'warnings' => count($report['warnings'] ?? []),
            'blockers' => count($report['blockers'] ?? []),
            'planned' => $report['planned'] ?? [],
        ];
    }

    private function importUsers(CoreImportBatch $batch, array &$summary): void
    {
        DB::connection('kp_source')->table('users')->orderBy('id')->get()->each(function ($kpUser) use ($batch, &$summary): void {
            $email = $this->normalize($kpUser->email);
            $payload = $this->userPayload($kpUser, $email);
            $existing = $this->coreUserByEmail($email);

            if (! $existing) {
                $id = DB::table('users')->insertGetId($payload);
                $this->record($batch, $summary, 'users', 'insert', 'users', $kpUser->id, $email, $id, $this->userSnapshot($kpUser, $email));
                return;
            }

            $updates = collect($payload)
                ->except(['email', 'created_at'])
                ->filter(fn ($value, $key) => $this->dbValue($existing->{$key} ?? null) !== $this->dbValue($value))
                ->all();

            if ($updates) {
                DB::table('users')->where('id', $existing->id)->update($updates);
                $this->record($batch, $summary, 'users', 'update', 'users', $kpUser->id, $email, $existing->id, $this->userSnapshot($kpUser, $email), 'Updated identity/password hash from KP.');
                return;
            }

            $this->record($batch, $summary, 'users', 'skip', 'users', $kpUser->id, $email, $existing->id, $this->userSnapshot($kpUser, $email));
        });
    }

    private function userPayload(object $kpUser, string $email): array
    {
        return [
            'name' => $kpUser->name ?: $email,
            'email' => $email,
            'password' => config('kp_import.password_policy.copy_hash') && $kpUser->password
                ? $kpUser->password
                : bcrypt(Str::random(40)),
            'active' => $kpUser->status === 'active',
            'email_verified_at' => $kpUser->email_verified_at,
            'remember_token' => null,
            'created_at' => $kpUser->created_at ?? now(),
            'updated_at' => now(),
        ];
    }

    private function userSnapshot(object $kpUser, string $email): array
    {
        return [
            'kp_id' => $kpUser->id,
            'email' => $email,
            'name' => $kpUser->name,
            'status' => $kpUser->status,
            'password_hash_copied' => (bool) (config('kp_import.password_policy.copy_hash') && $kpUser->password),
            'remember_token_imported' => false,
            'snapshot_candidates' => [
                'must_change_password' => $kpUser->must_change_password ?? null,
                'profile_completed' => $kpUser->profile_completed ?? null,
                'last_login_at' => $kpUser->last_login_at ?? null,
                'avatar_path' => $kpUser->avatar_path ?? null,
                'avatar_disk' => $kpUser->avatar_disk ?? null,
                'avatar_original_filename' => $kpUser->avatar_original_filename ?? null,
                'avatar_mime' => $kpUser->avatar_mime ?? null,
                'avatar_size' => $kpUser->avatar_size ?? null,
            ],
        ];
    }

    private function importRoles(CoreImportBatch $batch, array &$summary): void
    {
        DB::connection('kp_source')->table('roles')->orderBy('id')->get()->each(function ($kpRole) use ($batch, &$summary): void {
            $mapped = KpMasterDataDryRunAuditor::mapRole($kpRole->name);
            $existing = DB::table('roles')->where('name', $mapped)->first();

            if (! $existing) {
                $id = DB::table('roles')->insertGetId([
                    'name' => $mapped,
                    'label' => $kpRole->label ?: Str::headline($mapped),
                    'description' => $kpRole->description,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->record($batch, $summary, 'roles', 'insert', 'roles', $kpRole->id, $kpRole->name, $id, ['kp_role' => $kpRole->name, 'core_role' => $mapped]);
                return;
            }

            $this->record($batch, $summary, 'roles', 'skip', 'roles', $kpRole->id, $kpRole->name, $existing->id, ['kp_role' => $kpRole->name, 'core_role' => $mapped]);
        });
    }

    private function importUserRolesAndAccesses(CoreImportBatch $batch, array &$summary): void
    {
        $rows = DB::connection('kp_source')->table('user_roles')
            ->join('users', 'users.id', '=', 'user_roles.user_id')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->select('user_roles.*', 'users.email', 'users.status', 'roles.name as role_name')
            ->orderBy('user_roles.id')
            ->get();

        foreach ($rows as $row) {
            $this->upsertRoleAndAccess(
                $batch,
                $summary,
                $row->id,
                $this->normalize($row->email),
                KpMasterDataDryRunAuditor::mapRole($row->role_name),
                $row->status === 'active',
                'kp_user_role'
            );
        }
    }

    private function importStudents(CoreImportBatch $batch, array &$summary): void
    {
        $rows = DB::connection('kp_source')->table('students')
            ->leftJoin('users', 'users.id', '=', 'students.user_id')
            ->select('students.*', 'users.name as user_name', 'users.email as user_email')
            ->orderBy('students.id')
            ->get();

        foreach ($rows as $student) {
            $email = $this->normalize($student->user_email);
            $user = $this->coreUserByEmail($email);
            $studyProgram = $this->resolveStudyProgram($student->study_program);
            $studentNumber = trim((string) $student->nim);
            $existing = DB::table('students')->where('student_number', $studentNumber)->first();
            $payload = [
                'user_id' => $user?->id,
                'student_number' => $studentNumber,
                'name' => $student->user_name ?: $studentNumber,
                'email' => $email,
                'study_program_id' => $studyProgram->id,
                'status' => $student->status ?: 'active',
                'active' => $student->status === 'active',
                'created_at' => $student->created_at ?? now(),
                'updated_at' => now(),
            ];
            $snapshot = [
                'kp_id' => $student->id,
                'nim' => $studentNumber,
                'email' => $email,
                'study_program' => $student->study_program,
                'mapped_study_program' => $studyProgram->name,
            ];

            if (! $existing) {
                $id = DB::table('students')->insertGetId($payload);
                $this->record($batch, $summary, 'students', 'insert', 'students', $student->id, $studentNumber, $id, $snapshot);
                continue;
            }

            $updates = collect($payload)->except(['student_number', 'created_at'])->filter(fn ($value, $key) => $this->dbValue($existing->{$key} ?? null) !== $this->dbValue($value))->all();
            if ($updates) {
                DB::table('students')->where('id', $existing->id)->update($updates);
                $this->record($batch, $summary, 'students', 'update', 'students', $student->id, $studentNumber, $existing->id, $snapshot);
                continue;
            }

            $this->record($batch, $summary, 'students', 'skip', 'students', $student->id, $studentNumber, $existing->id, $snapshot);
        }
    }

    private function importLecturers(CoreImportBatch $batch, array &$summary): void
    {
        $rows = DB::connection('kp_source')->table('lecturers')
            ->leftJoin('users', 'users.id', '=', 'lecturers.user_id')
            ->select('lecturers.*', 'users.name as user_name', 'users.email as user_email')
            ->orderBy('lecturers.id')
            ->get();

        foreach ($rows as $lecturer) {
            $email = $this->normalize($lecturer->user_email);
            $user = $this->coreUserByEmail($email);
            $department = $this->resolveDepartment($lecturer->department);
            $studyProgram = $this->resolveStudyProgram($lecturer->study_program);
            $lecturerNumber = $this->normalize($lecturer->nidn_nip) ?: $this->normalize($lecturer->employee_number) ?: $email;
            $existing = DB::table('lecturers')
                ->where('lecturer_number', $lecturerNumber)
                ->orWhere('email', $email)
                ->first();
            $payload = [
                'user_id' => $user?->id,
                'lecturer_number' => $lecturerNumber,
                'name' => $lecturer->user_name ?: $lecturerNumber,
                'email' => $email,
                'department_id' => $department->id,
                'study_program_id' => $studyProgram?->id,
                'phone' => $lecturer->phone,
                'notes' => $lecturer->expertise,
                'active' => $lecturer->status === 'active',
                'created_at' => $lecturer->created_at ?? now(),
                'updated_at' => now(),
            ];
            $snapshot = [
                'kp_id' => $lecturer->id,
                'lecturer_number' => $lecturerNumber,
                'email' => $email,
                'department' => $lecturer->department,
                'mapped_department' => $department->name,
                'study_program' => $lecturer->study_program,
                'mapped_study_program' => $studyProgram?->name,
            ];

            if (! $existing) {
                $id = DB::table('lecturers')->insertGetId($payload);
                $this->record($batch, $summary, 'lecturers', 'insert', 'lecturers', $lecturer->id, $lecturerNumber, $id, $snapshot);
                continue;
            }

            $updates = collect($payload)->except(['lecturer_number', 'created_at'])->filter(fn ($value, $key) => $this->dbValue($existing->{$key} ?? null) !== $this->dbValue($value))->all();
            if ($updates) {
                DB::table('lecturers')->where('id', $existing->id)->update($updates);
                $this->record($batch, $summary, 'lecturers', 'update', 'lecturers', $lecturer->id, $lecturerNumber, $existing->id, $snapshot);
                continue;
            }

            $this->record($batch, $summary, 'lecturers', 'skip', 'lecturers', $lecturer->id, $lecturerNumber, $existing->id, $snapshot);
        }
    }

    private function importFieldSupervisorIdentityAccess(CoreImportBatch $batch, array &$summary): void
    {
        $rows = DB::connection('kp_source')->table('field_supervisors')
            ->join('users', 'users.id', '=', 'field_supervisors.user_id')
            ->select('field_supervisors.*', 'users.email', 'users.status')
            ->orderBy('field_supervisors.id')
            ->get();

        foreach ($rows as $fieldSupervisor) {
            $email = $this->normalize($fieldSupervisor->email);
            $user = $this->coreUserByEmail($email);
            $this->record($batch, $summary, 'field_supervisors', $user ? 'skip' : 'blocker', 'users', $fieldSupervisor->id, $email, $user?->id, [
                'kp_field_supervisor_id' => $fieldSupervisor->id,
                'email' => $email,
                'profile_location' => 'kp',
                'core_identity_only' => true,
            ], $user ? 'Field supervisor profile remains in KP; Core has identity/access only.' : 'Core user missing for field supervisor.');

            if ($user) {
                $this->upsertRoleAndAccess($batch, $summary, $fieldSupervisor->id, $email, 'pembimbing-lapangan', $fieldSupervisor->status === 'active', 'kp_field_supervisor');
            }
        }
    }

    private function upsertRoleAndAccess(CoreImportBatch $batch, array &$summary, int|string|null $sourceId, string $email, ?string $roleSlug, bool $active, string $sourceTable): void
    {
        $user = $this->coreUserByEmail($email);
        $role = $roleSlug ? DB::table('roles')->where('name', $roleSlug)->first() : null;
        $snapshot = ['email' => $email, 'role_slug' => $roleSlug, 'app_code' => 'kp-farmasi', 'is_active' => $active];

        if (! $user || ! $role) {
            $this->record($batch, $summary, 'user_roles', 'blocker', 'user_roles', $sourceId, $email.':'.$roleSlug, null, $snapshot, 'User or role could not be resolved.');
            $this->record($batch, $summary, 'user_app_accesses', 'blocker', 'user_app_accesses', $sourceId, $email.':'.$roleSlug, null, $snapshot, 'User or role could not be resolved.');
            return;
        }

        $existingRole = DB::table('user_roles')->where('user_id', $user->id)->where('role_id', $role->id)->first();
        if (! $existingRole) {
            $id = DB::table('user_roles')->insertGetId(['user_id' => $user->id, 'role_id' => $role->id, 'created_at' => now(), 'updated_at' => now()]);
            $this->record($batch, $summary, 'user_roles', 'insert', 'user_roles', $sourceId, $email.':'.$roleSlug, $id, $snapshot + ['source' => $sourceTable]);
        } else {
            $this->record($batch, $summary, 'user_roles', 'skip', 'user_roles', $sourceId, $email.':'.$roleSlug, $existingRole->id, $snapshot + ['source' => $sourceTable]);
        }

        $existingAccess = DB::table('user_app_accesses')
            ->where('user_id', $user->id)
            ->where('app_code', 'kp-farmasi')
            ->where('role_slug', $roleSlug)
            ->first();
        $payload = [
            'user_id' => $user->id,
            'app_code' => 'kp-farmasi',
            'role_slug' => $roleSlug,
            'permissions' => null,
            'is_active' => $active,
            'activated_at' => $active ? now() : null,
            'deactivated_at' => $active ? null : now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (! $existingAccess) {
            $id = DB::table('user_app_accesses')->insertGetId($payload);
            $this->record($batch, $summary, 'user_app_accesses', 'insert', 'user_app_accesses', $sourceId, $email.':kp-farmasi:'.$roleSlug, $id, $snapshot + ['source' => $sourceTable]);
            return;
        }

        $updates = collect($payload)->except(['user_id', 'app_code', 'role_slug', 'created_at'])->filter(fn ($value, $key) => $this->dbValue($existingAccess->{$key} ?? null) !== $this->dbValue($value))->all();
        if ($updates) {
            DB::table('user_app_accesses')->where('id', $existingAccess->id)->update($updates);
            $this->record($batch, $summary, 'user_app_accesses', 'update', 'user_app_accesses', $sourceId, $email.':kp-farmasi:'.$roleSlug, $existingAccess->id, $snapshot + ['source' => $sourceTable]);
            return;
        }

        $this->record($batch, $summary, 'user_app_accesses', 'skip', 'user_app_accesses', $sourceId, $email.':kp-farmasi:'.$roleSlug, $existingAccess->id, $snapshot + ['source' => $sourceTable]);
    }

    private function record(CoreImportBatch $batch, array &$summary, string $section, string $action, string $targetTable, int|string|null $sourceId, ?string $sourceIdentifier, int|string|null $targetId, ?array $snapshot = null, ?string $message = null): void
    {
        $summary[$section][$action]++;

        CoreImportRecord::create([
            'core_import_batch_id' => $batch->id,
            'source_table' => $section === 'user_app_accesses' ? 'user_app_accesses' : $section,
            'source_id' => $sourceId,
            'source_identifier' => $sourceIdentifier,
            'target_table' => $targetTable,
            'target_id' => $targetId,
            'action' => $action,
            'payload_snapshot' => $snapshot,
            'message' => $message,
        ]);
    }

    private function coreUserByEmail(string $email): ?object
    {
        return DB::table('users')->whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();
    }

    private function resolveStudyProgram(?string $sourceValue): ?object
    {
        $target = KpMasterDataDryRunAuditor::mapStudyProgram($sourceValue);

        return $target ? DB::table('study_programs')->whereRaw('LOWER(TRIM(name)) = ?', [$this->normalize($target)])->first() : null;
    }

    private function resolveDepartment(?string $sourceValue): object
    {
        $target = KpMasterDataDryRunAuditor::mapDepartment($sourceValue);

        return DB::table('departments')->whereRaw('LOWER(TRIM(name)) = ?', [$this->normalize($target)])->first();
    }

    private function normalize(mixed $value): string
    {
        return strtolower(trim((string) $value));
    }

    private function dbValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        return $value;
    }
}
