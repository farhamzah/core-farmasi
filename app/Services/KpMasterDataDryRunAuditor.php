<?php

namespace App\Services;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class KpMasterDataDryRunAuditor
{
    private const TABLES = [
        'users',
        'roles',
        'user_roles',
        'students',
        'lecturers',
        'field_supervisors',
    ];

    public function run(array $options = []): array
    {
        $only = $this->parseOnly($options['only'] ?? null);
        $limit = $this->parseLimit($options['limit'] ?? null);
        $strict = (bool) ($options['strict'] ?? false);
        $showSamples = (bool) ($options['show_samples'] ?? false);

        $report = $this->baseReport($only, $limit, $strict);

        if (($options['source'] ?? 'kp') !== 'kp') {
            $report['blockers'][] = 'Unsupported source. D1 only supports --source=kp.';
            $report['safe_for_d2'] = false;

            return $report;
        }

        if (! $this->connectivity($report)) {
            $report['safe_for_d2'] = false;

            return $report;
        }

        $kp = DB::connection('kp_source');
        $core = DB::connection();

        $this->collectCounts($report, $kp, $core);
        $this->collectDuplicates($report, $kp, $core);
        $this->collectOrphans($report, $kp);
        $this->collectNormalization($report, $kp, $core, $strict);

        $context = $this->buildContext($kp, $core);

        if (in_array('users', $only, true)) {
            $this->previewUsers($report, $kp, $context, $limit, $showSamples);
        }

        if (in_array('roles', $only, true)) {
            $this->previewRoles($report, $kp, $context, $limit, $showSamples);
        }

        if (in_array('user_roles', $only, true)) {
            $this->previewUserRolesAndAccess($report, $kp, $context, $limit, $showSamples);
        }

        if (in_array('students', $only, true)) {
            $this->previewStudents($report, $kp, $context, $limit, $strict, $showSamples);
        }

        if (in_array('lecturers', $only, true)) {
            $this->previewLecturers($report, $kp, $context, $limit, $strict, $showSamples);
        }

        if (in_array('field_supervisors', $only, true)) {
            $this->previewFieldSupervisors($report, $kp, $context, $limit, $showSamples);
        }

        $this->finalize($report);

        return $report;
    }

    public static function mapRole(?string $kpRole): ?string
    {
        return config('kp_import.role_map.'.($kpRole ?? ''));
    }

    public static function mapStudyProgram(?string $kpStudyProgram): ?string
    {
        return self::mappedValue('kp_import.study_program_map', $kpStudyProgram);
    }

    public static function mapDepartment(?string $kpDepartment): ?string
    {
        return self::mappedValue('kp_import.department_map', $kpDepartment);
    }

    private static function mappedValue(string $configKey, ?string $sourceValue): ?string
    {
        $normalizedSource = strtolower(trim((string) $sourceValue));

        if ($normalizedSource === '') {
            return null;
        }

        foreach (config($configKey, []) as $source => $target) {
            if (strtolower(trim((string) $source)) === $normalizedSource) {
                return $target;
            }
        }

        return $sourceValue;
    }

    private function baseReport(array $only, ?int $limit, bool $strict): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'mode' => 'dry-run',
            'source' => 'kp',
            'options' => [
                'only' => $only,
                'limit' => $limit,
                'strict' => $strict,
            ],
            'mapping' => [
                'field_supervisor_policy' => config('kp_import.field_supervisor_policy'),
                'password_policy' => config('kp_import.password_policy'),
            ],
            'connectivity' => [
                'core' => false,
                'kp_source' => false,
            ],
            'counts' => [
                'kp' => [],
                'core' => [],
            ],
            'duplicates' => [],
            'orphans' => [],
            'normalization' => [],
            'planned' => [
                'users' => $this->emptyPlan(),
                'roles' => $this->emptyPlan(),
                'user_roles' => $this->emptyPlan(),
                'user_app_accesses' => $this->emptyPlan(),
                'students' => $this->emptyPlan(),
                'lecturers' => $this->emptyPlan(),
                'field_supervisors' => $this->emptyPlan(),
            ],
            'warnings' => [],
            'blockers' => [],
            'samples' => [],
            'safe_for_d2' => false,
        ];
    }

    private function emptyPlan(): array
    {
        return [
            'insert' => 0,
            'update' => 0,
            'skip' => 0,
            'blocker' => 0,
        ];
    }

    private function parseOnly(?string $only): array
    {
        if (! $only) {
            return self::TABLES;
        }

        $tables = collect(explode(',', $only))
            ->map(fn (string $table) => trim($table))
            ->filter()
            ->values();

        return $tables
            ->filter(fn (string $table) => in_array($table, self::TABLES, true))
            ->whenEmpty(fn () => collect(self::TABLES))
            ->values()
            ->all();
    }

    private function parseLimit(mixed $limit): ?int
    {
        if ($limit === null || $limit === '') {
            return null;
        }

        $limit = (int) $limit;

        return $limit > 0 ? $limit : null;
    }

    private function connectivity(array &$report): bool
    {
        foreach (['core' => DB::connection(), 'kp_source' => DB::connection('kp_source')] as $name => $connection) {
            try {
                $connection->getPdo();
                $report['connectivity'][$name] = true;
            } catch (Throwable $exception) {
                $report['connectivity'][$name] = false;
                $report['blockers'][] = sprintf(
                    '%s database connection failed: %s',
                    $name,
                    $exception->getMessage()
                );
            }
        }

        return $report['connectivity']['core'] && $report['connectivity']['kp_source'];
    }

    private function collectCounts(array &$report, ConnectionInterface $kp, ConnectionInterface $core): void
    {
        foreach (self::TABLES as $table) {
            $report['counts']['kp'][$table] = $this->countTable($kp, 'kp_source', $table);
        }

        foreach (['users', 'roles', 'user_roles', 'students', 'lecturers', 'user_app_accesses'] as $table) {
            $report['counts']['core'][$table] = $this->countTable($core, null, $table);
        }
    }

    private function countTable(ConnectionInterface $connection, ?string $connectionName, string $table): ?int
    {
        if (! Schema::connection($connectionName)->hasTable($table)) {
            return null;
        }

        return $connection->table($table)->count();
    }

    private function collectDuplicates(array &$report, ConnectionInterface $kp, ConnectionInterface $core): void
    {
        $report['duplicates']['kp_user_email'] = $this->duplicateCount($kp, 'users', 'email', true);
        $report['duplicates']['core_user_email'] = $this->duplicateCount($core, 'users', 'email', true);
        $report['duplicates']['kp_nim'] = $this->duplicateCount($kp, 'students', 'nim');
        $report['duplicates']['kp_nidn_nip'] = $this->duplicateCount($kp, 'lecturers', 'nidn_nip');
        $report['duplicates']['kp_employee_number'] = $this->duplicateCount($kp, 'lecturers', 'employee_number');

        $kpEmails = $this->normalizedValues($kp->table('users')->pluck('email'));
        $coreEmails = $this->normalizedValues($core->table('users')->pluck('email'));
        $kpNims = $this->normalizedValues($kp->table('students')->pluck('nim'));
        $coreStudentNumbers = $this->normalizedValues($core->table('students')->pluck('student_number'));

        $report['duplicates']['kp_emails_already_in_core'] = count(array_intersect($kpEmails, $coreEmails));
        $report['duplicates']['kp_nim_already_in_core'] = count(array_intersect($kpNims, $coreStudentNumbers));
        $report['duplicates']['lecturer_identifier_collision'] = $this->lecturerIdentifierCollisions($kp);
        $report['duplicates']['multi_profile_email'] = $this->multiProfileEmailCount($kp);

        foreach ($report['duplicates'] as $key => $count) {
            if ($count > 0 && in_array($key, ['kp_user_email', 'kp_nim', 'kp_nidn_nip', 'kp_employee_number', 'lecturer_identifier_collision', 'multi_profile_email'], true)) {
                $report['blockers'][] = "Duplicate check found {$count} issue(s): {$key}.";
            }
        }
    }

    private function duplicateCount(ConnectionInterface $connection, string $table, string $column, bool $normalize = false): int
    {
        $expression = $normalize ? "LOWER(TRIM({$column}))" : $column;

        return $connection->table($table)
            ->selectRaw("{$expression} as value, COUNT(*) as aggregate")
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->groupBy('value')
            ->having('aggregate', '>', 1)
            ->count();
    }

    private function normalizedValues(Collection $values): array
    {
        return $values
            ->map(fn ($value) => $this->normalize($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function lecturerIdentifierCollisions(ConnectionInterface $kp): int
    {
        $seen = [];
        $collisions = 0;

        $kp->table('lecturers')
            ->leftJoin('users', 'users.id', '=', 'lecturers.user_id')
            ->select('lecturers.id', 'lecturers.nidn_nip', 'lecturers.employee_number', 'users.email')
            ->orderBy('lecturers.id')
            ->get()
            ->each(function ($lecturer) use (&$seen, &$collisions): void {
                foreach ([$lecturer->nidn_nip, $lecturer->employee_number, $lecturer->email] as $identifier) {
                    $key = $this->normalize($identifier);
                    if (! $key) {
                        continue;
                    }

                    if (isset($seen[$key]) && $seen[$key] !== $lecturer->id) {
                        $collisions++;
                    }

                    $seen[$key] = $lecturer->id;
                }
            });

        return $collisions;
    }

    private function multiProfileEmailCount(ConnectionInterface $kp): int
    {
        $emails = [];

        foreach (['students', 'lecturers', 'field_supervisors'] as $table) {
            $kp->table($table)
                ->join('users', 'users.id', '=', "{$table}.user_id")
                ->pluck('users.email')
                ->each(function ($email) use (&$emails, $table): void {
                    $key = $this->normalize($email);
                    if ($key) {
                        $emails[$key][$table] = true;
                    }
                });
        }

        return collect($emails)->filter(fn (array $tables) => count($tables) > 1)->count();
    }

    private function collectOrphans(array &$report, ConnectionInterface $kp): void
    {
        $checks = [
            'students_user_missing' => ['students', 'user_id', 'users'],
            'lecturers_user_missing' => ['lecturers', 'user_id', 'users'],
            'field_supervisors_user_missing' => ['field_supervisors', 'user_id', 'users'],
            'user_roles_user_missing' => ['user_roles', 'user_id', 'users'],
            'user_roles_role_missing' => ['user_roles', 'role_id', 'roles'],
        ];

        foreach ($checks as $key => [$table, $column, $target]) {
            $report['orphans'][$key] = $kp->table($table)
                ->leftJoin($target, "{$target}.id", '=', "{$table}.{$column}")
                ->whereNull("{$target}.id")
                ->count();
        }

        $transactionChecks = [
            'kp_registrations_student_missing' => ['kp_registrations', 'student_id', 'students'],
            'kp_assignments_student_missing' => ['kp_assignments', 'student_id', 'students'],
            'kp_assignments_internal_lecturer_missing' => ['kp_assignments', 'internal_supervisor_id', 'lecturers'],
            'kp_assignments_field_supervisor_missing' => ['kp_assignments', 'field_supervisor_id', 'field_supervisors'],
        ];

        foreach ($transactionChecks as $key => [$table, $column, $target]) {
            if (! Schema::connection('kp_source')->hasTable($table)) {
                $report['orphans'][$key] = null;
                continue;
            }

            $report['orphans'][$key] = $kp->table($table)
                ->leftJoin($target, "{$target}.id", '=', "{$table}.{$column}")
                ->whereNotNull("{$table}.{$column}")
                ->whereNull("{$target}.id")
                ->count();
        }

        foreach ($report['orphans'] as $key => $count) {
            if ($count > 0) {
                $report['blockers'][] = "Orphan check found {$count} issue(s): {$key}.";
            }
        }
    }

    private function collectNormalization(array &$report, ConnectionInterface $kp, ConnectionInterface $core, bool $strict): void
    {
        $kpRoles = $kp->table('roles')->pluck('name')->all();
        $unmappedRoles = collect($kpRoles)->filter(fn ($role) => ! self::mapRole($role))->values()->all();
        $mappedCoreRoles = collect($kpRoles)->map(fn ($role) => self::mapRole($role))->filter()->unique()->values();
        $existingCoreRoles = $core->table('roles')->whereIn('name', $mappedCoreRoles)->pluck('name')->all();

        $report['normalization']['role_mapping'] = config('kp_import.role_map', []);
        $report['normalization']['unmapped_roles'] = $unmappedRoles;
        $report['normalization']['mapped_roles_missing_in_core'] = $mappedCoreRoles->diff($existingCoreRoles)->values()->all();

        if ($unmappedRoles) {
            $report['blockers'][] = 'Unmapped KP roles: '.implode(', ', $unmappedRoles).'.';
        }

        $coreStudyPrograms = $this->coreLookup($core, 'study_programs', ['name', 'code']);
        $kpStudyPrograms = $kp->table('students')->pluck('study_program')
            ->merge($kp->table('lecturers')->pluck('study_program'))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        $unmatchedStudyPrograms = $kpStudyPrograms
            ->filter(fn ($value) => ! $this->resolveMappedStudyProgram($value, $coreStudyPrograms))
            ->values()
            ->all();

        $report['normalization']['study_program_values'] = $kpStudyPrograms->all();
        $report['normalization']['study_program_unmatched'] = $unmatchedStudyPrograms;

        $coreDepartments = $this->coreLookup($core, 'departments', ['name', 'code']);
        $kpDepartments = $kp->table('lecturers')->pluck('department')
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        $unmatchedDepartments = $kpDepartments
            ->filter(fn ($value) => ! $this->resolveMappedDepartment($value, $coreDepartments))
            ->values()
            ->all();

        $report['normalization']['department_values'] = $kpDepartments->all();
        $report['normalization']['department_unmatched'] = $unmatchedDepartments;

        foreach ([
            'study program' => $unmatchedStudyPrograms,
            'department' => $unmatchedDepartments,
        ] as $label => $values) {
            if (! $values) {
                continue;
            }

            $message = 'Unmatched '.$label.' value(s): '.implode(', ', $values).'.';
            $strict ? $report['blockers'][] = $message : $report['warnings'][] = $message;
        }
    }

    private function buildContext(ConnectionInterface $kp, ConnectionInterface $core): array
    {
        return [
            'core_users_by_email' => $core->table('users')->get()->keyBy(fn ($row) => $this->normalize($row->email)),
            'core_roles_by_name' => $core->table('roles')->get()->keyBy('name'),
            'core_user_roles' => $core->table('user_roles')->get()->mapWithKeys(fn ($row) => [$row->user_id.':'.$row->role_id => true]),
            'core_app_accesses' => $core->table('user_app_accesses')->get()->mapWithKeys(fn ($row) => [$row->user_id.':'.$row->app_code.':'.$row->role_slug => true]),
            'core_students_by_number' => $core->table('students')->get()->keyBy(fn ($row) => $this->normalize($row->student_number)),
            'core_lecturers_by_number' => $core->table('lecturers')->get()->keyBy(fn ($row) => $this->normalize($row->lecturer_number)),
            'core_lecturers_by_email' => $core->table('lecturers')->get()->keyBy(fn ($row) => $this->normalize($row->email)),
            'study_programs' => $this->coreLookup($core, 'study_programs', ['name', 'code']),
            'departments' => $this->coreLookup($core, 'departments', ['name', 'code']),
            'kp_users_by_id' => $kp->table('users')->get()->keyBy('id'),
            'kp_roles_by_id' => $kp->table('roles')->get()->keyBy('id'),
        ];
    }

    private function coreLookup(ConnectionInterface $core, string $table, array $columns): array
    {
        return $core->table($table)->get()->reduce(function (array $carry, object $row) use ($columns) {
            foreach ($columns as $column) {
                $key = $this->normalize($row->{$column} ?? null);
                if ($key) {
                    $carry[$key] = $row;
                }
            }

            return $carry;
        }, []);
    }

    private function previewUsers(array &$report, ConnectionInterface $kp, array $context, ?int $limit, bool $showSamples): void
    {
        $this->sourceRows($kp, 'users', $limit)->each(function ($user) use (&$report, $context, $showSamples): void {
            $email = $this->normalize($user->email);
            if (! $email) {
                $this->mark($report, 'users', 'blocker', 'KP user has empty email.', $showSamples, ['kp_user_id' => $user->id]);
                return;
            }

            $coreUser = $context['core_users_by_email'][$email] ?? null;
            if (! $coreUser) {
                $this->mark($report, 'users', 'insert', null, $showSamples, ['email' => $email, 'active' => $user->status === 'active']);
                return;
            }

            $targetActive = $user->status === 'active';
            if ((bool) $coreUser->active !== $targetActive || $coreUser->name !== $user->name) {
                $this->mark($report, 'users', 'update', null, $showSamples, ['email' => $email, 'active' => $targetActive]);
                return;
            }

            $this->mark($report, 'users', 'skip');
        });
    }

    private function previewRoles(array &$report, ConnectionInterface $kp, array $context, ?int $limit, bool $showSamples): void
    {
        $this->sourceRows($kp, 'roles', $limit)->each(function ($role) use (&$report, $context, $showSamples): void {
            $mapped = self::mapRole($role->name);
            if (! $mapped) {
                $this->mark($report, 'roles', 'blocker', "No Core mapping for KP role {$role->name}.", $showSamples, ['role' => $role->name]);
                return;
            }

            if (isset($context['core_roles_by_name'][$mapped])) {
                $this->mark($report, 'roles', 'skip');
                return;
            }

            $this->mark($report, 'roles', 'insert', null, $showSamples, ['kp_role' => $role->name, 'core_role' => $mapped]);
        });
    }

    private function previewUserRolesAndAccess(array &$report, ConnectionInterface $kp, array $context, ?int $limit, bool $showSamples): void
    {
        $query = $kp->table('user_roles')
            ->join('users', 'users.id', '=', 'user_roles.user_id')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->select('user_roles.*', 'users.email', 'users.status', 'roles.name as role_name')
            ->orderBy('user_roles.id');

        $this->applyLimit($query, $limit)->get()->each(function ($row) use (&$report, $context, $showSamples): void {
            $email = $this->normalize($row->email);
            $mappedRole = self::mapRole($row->role_name);
            $coreUser = $context['core_users_by_email'][$email] ?? null;
            $coreRole = $context['core_roles_by_name'][$mappedRole] ?? null;

            if (! $email || ! $mappedRole) {
                $this->mark($report, 'user_roles', 'blocker', 'User role cannot resolve email or role mapping.', $showSamples, ['email' => $email, 'role' => $row->role_name]);
                $this->mark($report, 'user_app_accesses', 'blocker');
                return;
            }

            if (! $coreUser || ! $coreRole) {
                $this->mark($report, 'user_roles', 'insert', null, $showSamples, ['email' => $email, 'role' => $mappedRole, 'depends_on_planned_user_or_role' => true]);
                $this->mark($report, 'user_app_accesses', 'insert');
                return;
            }

            isset($context['core_user_roles'][$coreUser->id.':'.$coreRole->id])
                ? $this->mark($report, 'user_roles', 'skip')
                : $this->mark($report, 'user_roles', 'insert', null, $showSamples, ['email' => $email, 'role' => $mappedRole]);

            $accessKey = $coreUser->id.':kp-farmasi:'.$mappedRole;
            isset($context['core_app_accesses'][$accessKey])
                ? $this->mark($report, 'user_app_accesses', 'skip')
                : $this->mark($report, 'user_app_accesses', 'insert', null, $showSamples, ['email' => $email, 'app_code' => 'kp-farmasi', 'role_slug' => $mappedRole, 'is_active' => $row->status === 'active']);
        });
    }

    private function previewStudents(array &$report, ConnectionInterface $kp, array $context, ?int $limit, bool $strict, bool $showSamples): void
    {
        $query = $kp->table('students')
            ->leftJoin('users', 'users.id', '=', 'students.user_id')
            ->select('students.*', 'users.name as user_name', 'users.email as user_email')
            ->orderBy('students.id');

        $this->applyLimit($query, $limit)->get()->each(function ($student) use (&$report, $context, $strict, $showSamples): void {
            $nim = $this->normalize($student->nim);
            if (! $nim) {
                $this->mark($report, 'students', 'blocker', 'KP student has empty NIM.', $showSamples, ['kp_student_id' => $student->id]);
                return;
            }

            $studyKey = $this->normalize($student->study_program);
            $studyProgram = $studyKey ? $this->resolveMappedStudyProgram($student->study_program, $context['study_programs']) : null;
            if (! $studyProgram) {
                $message = "Student {$student->nim} study program cannot be resolved: {$student->study_program}.";
                $strict ? $this->mark($report, 'students', 'blocker', $message, $showSamples, ['nim' => $student->nim]) : $report['warnings'][] = $message;
            }

            if (isset($context['core_students_by_number'][$nim])) {
                $this->mark($report, 'students', 'skip');
                return;
            }

            $this->mark($report, 'students', 'insert', null, $showSamples, [
                'student_number' => $student->nim,
                'email' => $this->normalize($student->user_email),
                'study_program' => $student->study_program,
                'mapped_study_program' => self::mapStudyProgram($student->study_program),
            ]);
        });
    }

    private function previewLecturers(array &$report, ConnectionInterface $kp, array $context, ?int $limit, bool $strict, bool $showSamples): void
    {
        $query = $kp->table('lecturers')
            ->leftJoin('users', 'users.id', '=', 'lecturers.user_id')
            ->select('lecturers.*', 'users.name as user_name', 'users.email as user_email')
            ->orderBy('lecturers.id');

        $this->applyLimit($query, $limit)->get()->each(function ($lecturer) use (&$report, $context, $strict, $showSamples): void {
            $identifier = $this->normalize($lecturer->nidn_nip) ?: $this->normalize($lecturer->employee_number);
            $email = $this->normalize($lecturer->user_email);

            if (! $identifier) {
                $message = "Lecturer {$lecturer->id} has no nidn_nip or employee_number; email fallback will be used.";
                $strict ? $this->mark($report, 'lecturers', 'blocker', $message, $showSamples, ['email' => $email]) : $report['warnings'][] = $message;
                $identifier = $email;
            }

            if (! $identifier) {
                $this->mark($report, 'lecturers', 'blocker', 'Lecturer cannot be matched by identifier or email.', $showSamples, ['kp_lecturer_id' => $lecturer->id]);
                return;
            }

            $departmentKey = $this->normalize($lecturer->department);
            $department = $departmentKey ? $this->resolveMappedDepartment($lecturer->department, $context['departments']) : null;
            if (! $department) {
                $message = "Lecturer {$identifier} department cannot be resolved: {$lecturer->department}.";
                $strict ? $this->mark($report, 'lecturers', 'blocker', $message, $showSamples, ['identifier' => $identifier]) : $report['warnings'][] = $message;
            }

            $studyKey = $this->normalize($lecturer->study_program);
            if ($studyKey && ! $this->resolveMappedStudyProgram($lecturer->study_program, $context['study_programs'])) {
                $message = "Lecturer {$identifier} study program cannot be resolved: {$lecturer->study_program}.";
                $strict ? $this->mark($report, 'lecturers', 'blocker', $message, $showSamples, ['identifier' => $identifier]) : $report['warnings'][] = $message;
            }

            if (isset($context['core_lecturers_by_number'][$identifier]) || ($email && isset($context['core_lecturers_by_email'][$email]))) {
                $this->mark($report, 'lecturers', 'skip');
                return;
            }

            $this->mark($report, 'lecturers', 'insert', null, $showSamples, [
                'lecturer_number' => $identifier,
                'email' => $email,
                'department' => $lecturer->department,
                'mapped_department' => self::mapDepartment($lecturer->department),
                'study_program' => $lecturer->study_program,
                'mapped_study_program' => self::mapStudyProgram($lecturer->study_program),
            ]);
        });
    }

    private function previewFieldSupervisors(array &$report, ConnectionInterface $kp, array $context, ?int $limit, bool $showSamples): void
    {
        $query = $kp->table('field_supervisors')
            ->leftJoin('users', 'users.id', '=', 'field_supervisors.user_id')
            ->select('field_supervisors.*', 'users.email as user_email', 'users.status as user_status')
            ->orderBy('field_supervisors.id');

        $this->applyLimit($query, $limit)->get()->each(function ($fieldSupervisor) use (&$report, $context, $showSamples): void {
            $email = $this->normalize($fieldSupervisor->user_email);
            $coreUser = $context['core_users_by_email'][$email] ?? null;
            $coreRole = $context['core_roles_by_name']['pembimbing-lapangan'] ?? null;

            if (! $email) {
                $this->mark($report, 'field_supervisors', 'blocker', 'Field supervisor user email cannot be resolved.', $showSamples, ['kp_field_supervisor_id' => $fieldSupervisor->id]);
                return;
            }

            $coreUser ? $this->mark($report, 'field_supervisors', 'skip') : $this->mark($report, 'field_supervisors', 'insert', null, $showSamples, ['email' => $email]);

            if ($coreUser && $coreRole) {
                isset($context['core_user_roles'][$coreUser->id.':'.$coreRole->id])
                    ? $this->mark($report, 'user_roles', 'skip')
                    : $this->mark($report, 'user_roles', 'insert');

                isset($context['core_app_accesses'][$coreUser->id.':kp-farmasi:pembimbing-lapangan'])
                    ? $this->mark($report, 'user_app_accesses', 'skip')
                    : $this->mark($report, 'user_app_accesses', 'insert');
            } else {
                $this->mark($report, 'user_roles', 'insert');
                $this->mark($report, 'user_app_accesses', 'insert');
            }
        });
    }

    private function mark(array &$report, string $section, string $action, ?string $message = null, bool $sample = false, array $samplePayload = []): void
    {
        $report['planned'][$section][$action]++;

        if ($message) {
            $action === 'blocker' ? $report['blockers'][] = $message : $report['warnings'][] = $message;
        }

        if ($sample && $samplePayload && count($report['samples'][$section] ?? []) < 5) {
            $report['samples'][$section][] = ['action' => $action] + $samplePayload;
        }
    }

    private function sourceRows(ConnectionInterface $connection, string $table, ?int $limit): Collection
    {
        $query = $connection->table($table)->orderBy('id');

        return $this->applyLimit($query, $limit)->get();
    }

    private function applyLimit($query, ?int $limit)
    {
        if ($limit) {
            $query->limit($limit);
        }

        return $query;
    }

    private function normalize(mixed $value): string
    {
        return strtolower(trim((string) $value));
    }

    private function resolveMappedStudyProgram(?string $sourceValue, array $coreStudyPrograms): ?object
    {
        $target = self::mapStudyProgram($sourceValue);

        return $target ? ($coreStudyPrograms[$this->normalize($target)] ?? null) : null;
    }

    private function resolveMappedDepartment(?string $sourceValue, array $coreDepartments): ?object
    {
        $target = self::mapDepartment($sourceValue);

        return $target ? ($coreDepartments[$this->normalize($target)] ?? null) : null;
    }

    private function finalize(array &$report): void
    {
        $report['warnings'] = array_values(array_unique($report['warnings']));
        $report['blockers'] = array_values(array_unique($report['blockers']));
        $report['safe_for_d2'] = count($report['blockers']) === 0;
    }
}
