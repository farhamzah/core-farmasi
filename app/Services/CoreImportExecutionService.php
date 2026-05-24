<?php

namespace App\Services;

use App\Models\CoreImportBatch;
use App\Models\CoreImportRecord;
use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Models\UserAppAccess;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class CoreImportExecutionService
{
    protected const SUPPORTED_TYPES = ['students', 'lecturers', 'employees', 'users', 'user_role_assignments', 'user_app_accesses'];

    protected const EXECUTABLE_DECISIONS = ['create_new', 'update_existing', 'assign', 'deactivate', 'skip', 'invalid'];

    public function __construct(
        protected CoreInitialPasswordService $passwords,
    ) {}

    public function execute(CoreImportBatch $batch, ?User $actor = null): array
    {
        if (! in_array($batch->source, self::SUPPORTED_TYPES, true)) {
            throw new InvalidArgumentException('Import type belum didukung untuk execute.');
        }

        $records = $batch->records()->orderByRaw('CAST(source_id AS INTEGER)')->get();

        if ($records->where('admin_decision', 'needs_admin_decision')->isNotEmpty()) {
            throw new InvalidArgumentException('Masih ada row yang membutuhkan keputusan admin.');
        }

        $summary = [
            'total_records' => $records->count(),
            'executed_count' => 0,
            'skipped_count' => 0,
            'failed_count' => 0,
            'ignored_invalid_count' => 0,
            'created_count' => 0,
            'updated_count' => 0,
            'users_created_count' => 0,
            'users_linked_count' => 0,
            'role_assignments_assigned_count' => 0,
            'app_accesses_assigned_count' => 0,
            'app_accesses_deactivated_count' => 0,
        ];

        $batch->forceFill(['status' => 'executing'])->save();

        foreach ($records as $record) {
            $this->executeRecordSafely($batch, $record, $actor, $summary);
        }

        $finalStatus = $summary['failed_count'] > 0
            ? ($summary['executed_count'] > 0 || $summary['skipped_count'] > 0 || $summary['ignored_invalid_count'] > 0 ? 'partially_failed' : 'failed')
            : 'executed';

        $batchSummary = $batch->summary ?? [];
        $batchSummary['execution'] = $summary;

        $batch->forceFill([
            'status' => $finalStatus,
            'finished_at' => now(),
            'summary' => $batchSummary,
        ])->save();

        $this->logActivity($actor, 'import.batch_executed', [
            'import_batch_id' => $batch->id,
            'import_type' => $batch->source,
            'status' => $finalStatus,
            'summary' => $summary,
        ]);

        return $summary;
    }

    protected function executeRecordSafely(CoreImportBatch $batch, CoreImportRecord $record, ?User $actor, array &$summary): void
    {
        if (! in_array($record->admin_decision, self::EXECUTABLE_DECISIONS, true)) {
            $record->forceFill([
                'execution_status' => 'pending',
                'message' => trim(($record->message ? "{$record->message} " : '') . 'Row belum memiliki decision executable.'),
            ])->save();

            return;
        }

        if ($record->admin_decision === 'skip') {
            $record->forceFill(['execution_status' => 'skipped'])->save();
            $summary['skipped_count']++;

            return;
        }

        if ($record->admin_decision === 'invalid' || $record->validation_status === 'invalid') {
            $record->forceFill(['execution_status' => 'ignored_invalid'])->save();
            $summary['ignored_invalid_count']++;

            return;
        }

        try {
            DB::transaction(function () use ($batch, $record, $actor, &$summary) {
                $result = match ($batch->source) {
                    'students' => $this->executeStudentRecord($record, $actor),
                    'lecturers' => $this->executeLecturerRecord($record, $actor),
                    'employees' => $this->executeEmployeeRecord($record, $actor),
                    'users' => $this->executeUserRecord($record, $actor),
                    'user_role_assignments' => $this->executeUserRoleAssignmentRecord($record),
                    'user_app_accesses' => $this->executeUserAppAccessRecord($record),
                    default => throw new InvalidArgumentException('Import type belum didukung.'),
                };

                $record->forceFill([
                    'target_table' => $result['target_table'],
                    'target_type' => $result['target_type'],
                    'target_id' => (string) $result['target_id'],
                    'action' => $result['action'],
                    'executed_action' => $record->admin_decision,
                    'executed_by' => $actor?->id,
                    'executed_at' => now(),
                    'previous_snapshot' => $result['previous_snapshot'],
                    'created_user_id' => $result['created_user_id'],
                    'linked_user_id' => $result['linked_user_id'],
                    'execution_status' => $result['execution_status'] ?? 'executed',
                    'rollback_status' => null,
                    'message' => $result['message'] ?? null,
                ])->save();

                if (($result['execution_status'] ?? 'executed') === 'skipped') {
                    $summary['skipped_count']++;
                } else {
                    $summary['executed_count']++;
                }

                if ($result['action'] === 'updated') {
                    $summary['updated_count']++;
                } elseif ($result['action'] === 'created') {
                    $summary['created_count']++;
                } elseif ($result['action'] === 'assigned') {
                    $summary['role_assignments_assigned_count']++;
                } elseif ($result['action'] === 'app_access_assigned') {
                    $summary['app_accesses_assigned_count']++;
                } elseif ($result['action'] === 'app_access_deactivated') {
                    $summary['app_accesses_deactivated_count']++;
                }

                $summary['users_created_count'] += $result['created_user_id'] ? 1 : 0;
                $summary['users_linked_count'] += $result['linked_user_id'] ? 1 : 0;

                $this->logActivity($actor, "import.row_{$result['action']}", [
                    'import_batch_id' => $batch->id,
                    'import_record_id' => $record->id,
                    'import_type' => $batch->source,
                    'target_model' => $result['target_table'],
                    'target_id' => $result['target_id'],
                    'created_user_id' => $result['created_user_id'],
                    'linked_user_id' => $result['linked_user_id'],
                ]);
            });
        } catch (Throwable $exception) {
            $record->forceFill([
                'execution_status' => 'failed',
                'message' => $this->safeFailureMessage($exception),
            ])->save();

            $summary['failed_count']++;
        }
    }

    protected function executeStudentRecord(CoreImportRecord $record, ?User $actor): array
    {
        $data = $record->normalized_data ?? [];

        return $record->admin_decision === 'update_existing'
            ? $this->updateStudent($record, $data, $actor)
            : $this->createStudent($record, $data, $actor);
    }

    protected function executeUserRecord(CoreImportRecord $record, ?User $actor): array
    {
        $data = $record->normalized_data ?? [];

        return $record->admin_decision === 'update_existing'
            ? $this->updateUser($data)
            : $this->createUser($data, $actor);
    }

    protected function createUser(array $data, ?User $actor): array
    {
        $username = $this->required($data, 'username');
        $email = $this->required($data, 'email');
        $identityType = $this->required($data, 'identity_type');
        $identityNumber = $this->required($data, 'identity_number');

        $this->assertIdentityType($identityType);

        if (blank($data['birth_date'] ?? null)) {
            throw new InvalidArgumentException('birth_date wajib untuk membuat initial password user baru.');
        }

        if (User::where('username', $username)->exists()) {
            throw new InvalidArgumentException('username sudah ada saat execute.');
        }

        if (User::where('email', $email)->exists()) {
            throw new InvalidArgumentException('email sudah ada saat execute.');
        }

        if (User::where('identity_type', $identityType)->where('identity_number', $identityNumber)->exists()) {
            throw new InvalidArgumentException('identity_type dan identity_number sudah ada saat execute.');
        }

        $user = User::create([
            'name' => $this->required($data, 'name'),
            'email' => $email,
            'username' => $username,
            'identity_type' => $identityType,
            'identity_number' => $identityNumber,
            'password' => $this->passwords->hashFromBirthDate($data['birth_date']),
            'active' => $this->booleanValue($data['is_active'] ?? $data['status'] ?? true),
            'must_change_password' => true,
            'password_changed_at' => null,
            'last_password_reset_at' => now(),
            'password_reset_by' => $actor?->id,
        ]);

        $this->logActivity($actor, 'import.user_created', [
            'target_user_id' => $user->id,
            'identity_type' => $identityType,
            'method' => 'birth_date_based',
        ]);

        return $this->result('users', User::class, $user->id, 'created', ['user' => null, 'user_created' => false, 'user_linked' => false], 'User created from import.');
    }

    protected function updateUser(array $data): array
    {
        $user = User::where('username', $this->required($data, 'username'))->firstOrFail();

        $updates = $this->nonEmpty([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'identity_type' => $data['identity_type'] ?? null,
            'identity_number' => $data['identity_number'] ?? null,
            'active' => array_key_exists('is_active', $data) ? $this->booleanValue($data['is_active']) : null,
            'must_change_password' => array_key_exists('must_change_password', $data) ? $this->booleanValue($data['must_change_password']) : null,
        ]);

        if (isset($updates['identity_type'])) {
            $this->assertIdentityType((string) $updates['identity_type']);
        }

        if (isset($updates['email']) && User::where('email', $updates['email'])->whereKeyNot($user->id)->exists()) {
            throw new InvalidArgumentException('email sudah dipakai user lain saat execute.');
        }

        if (isset($updates['identity_type'], $updates['identity_number']) && User::where('identity_type', $updates['identity_type'])->where('identity_number', $updates['identity_number'])->whereKeyNot($user->id)->exists()) {
            throw new InvalidArgumentException('identity_type dan identity_number sudah dipakai user lain saat execute.');
        }

        $previousSnapshot = $this->snapshot($user, array_keys($updates));
        $user->fill($updates)->save();

        return $this->result('users', User::class, $user->id, 'updated', ['user' => null, 'user_created' => false, 'user_linked' => false], 'User updated from import.', $previousSnapshot);
    }

    protected function executeUserRoleAssignmentRecord(CoreImportRecord $record): array
    {
        $data = $record->normalized_data ?? [];
        $user = User::where('username', $this->required($data, 'username'))->firstOrFail();
        $role = Role::where('name', $this->required($data, 'role_slug'))->where('active', true)->firstOrFail();
        $exists = $user->roles()->where('roles.id', $role->id)->exists();

        if ($exists) {
            return [
                'target_table' => 'user_roles',
                'target_type' => Role::class,
                'target_id' => $role->id,
                'action' => 'skipped',
                'execution_status' => 'skipped',
                'message' => 'Role assignment already exists.',
                'created_user_id' => null,
                'linked_user_id' => null,
                'previous_snapshot' => ['assignment_existed' => true, 'user_id' => $user->id, 'role_id' => $role->id],
            ];
        }

        $user->roles()->attach($role->id);

        return [
            'target_table' => 'user_roles',
            'target_type' => Role::class,
            'target_id' => $role->id,
            'action' => 'assigned',
            'message' => 'Global role assigned from import.',
            'created_user_id' => null,
            'linked_user_id' => null,
            'previous_snapshot' => ['assignment_existed' => false, 'user_id' => $user->id, 'role_id' => $role->id],
        ];
    }

    protected function executeUserAppAccessRecord(CoreImportRecord $record): array
    {
        $data = $record->normalized_data ?? [];
        $user = User::where('username', $this->required($data, 'username'))->firstOrFail();
        $appCode = $this->required($data, 'app_code');
        $roleSlug = $this->required($data, 'role_slug');

        $application = CoreApplication::where('app_code', $appCode)->where('is_active', true)->firstOrFail();
        CoreApplicationRole::where('app_code', $application->app_code)->where('role_slug', $roleSlug)->where('is_active', true)->firstOrFail();

        $access = UserAppAccess::where('user_id', $user->id)->where('app_code', $appCode)->where('role_slug', $roleSlug)->first();

        if ($record->admin_decision === 'deactivate') {
            if (! $access || ! $access->is_active) {
                return $this->skippedAppAccessResult($access, 'Active app access not found.');
            }

            $previousSnapshot = $this->snapshot($access, ['is_active', 'activated_at', 'deactivated_at']);
            $access->forceFill([
                'is_active' => false,
                'deactivated_at' => now(),
            ])->save();

            return $this->appAccessResult($access, 'app_access_deactivated', 'App access deactivated from import.', $previousSnapshot);
        }

        if ($access?->is_active) {
            return $this->skippedAppAccessResult($access, 'App access already active.');
        }

        if ($access) {
            $previousSnapshot = $this->snapshot($access, ['is_active', 'activated_at', 'deactivated_at']);
            $access->forceFill([
                'is_active' => true,
                'activated_at' => $access->activated_at ?? now(),
                'deactivated_at' => null,
            ])->save();

            return $this->appAccessResult($access, 'app_access_assigned', 'Inactive app access reactivated from import.', $previousSnapshot);
        }

        $access = UserAppAccess::create([
            'user_id' => $user->id,
            'app_code' => $appCode,
            'role_slug' => $roleSlug,
            'is_active' => true,
            'activated_at' => now(),
        ]);

        return $this->appAccessResult($access, 'app_access_assigned', 'App access assigned from import.', ['created_by_import' => true]);
    }

    protected function createStudent(CoreImportRecord $record, array $data, ?User $actor): array
    {
        $nim = $this->required($data, 'nim');
        $email = $this->required($data, 'email');
        $studyProgram = $this->studyProgram($this->required($data, 'study_program_code'));

        if (Student::where('student_number', $nim)->exists()) {
            throw new InvalidArgumentException('NIM sudah ada saat execute.');
        }

        $userResult = $this->resolveUserForProfile($data, 'student', $nim, $actor);

        $student = Student::create([
            'user_id' => $userResult['user']?->id,
            'student_number' => $nim,
            'name' => $this->required($data, 'name'),
            'email' => $email,
            'birth_date' => $this->dateValue($data['birth_date'] ?? null),
            'study_program_id' => $studyProgram->id,
            'status' => $this->statusValue($data),
            'active' => $this->activeValue($data),
        ]);

        return $this->result('students', Student::class, $student->id, 'created', $userResult, 'Student created from import.');
    }

    protected function updateStudent(CoreImportRecord $record, array $data, ?User $actor): array
    {
        $student = Student::where('student_number', $this->required($data, 'nim'))->firstOrFail();

        $updates = $this->nonEmpty([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'birth_date' => $this->dateValue($data['birth_date'] ?? null),
            'status' => $data['status'] ?? null,
            'active' => array_key_exists('status', $data) ? $this->activeValue($data) : null,
        ]);

        if (filled($data['study_program_code'] ?? null)) {
            $updates['study_program_id'] = $this->studyProgram($data['study_program_code'])->id;
        }

        if (! $student->user_id) {
            $userResult = $this->resolveUserForProfile($data, 'student', $student->student_number, $actor);
            $updates['user_id'] = $userResult['user']?->id;
        } else {
            $userResult = ['user' => $student->user, 'user_created' => false, 'user_linked' => false];
        }

        $previousSnapshot = $this->snapshot($student, array_keys($updates));
        $student->fill($updates)->save();

        return $this->result('students', Student::class, $student->id, 'updated', $userResult, 'Student updated from import.', $previousSnapshot);
    }

    protected function executeLecturerRecord(CoreImportRecord $record, ?User $actor): array
    {
        $data = $record->normalized_data ?? [];

        return $record->admin_decision === 'update_existing'
            ? $this->updateLecturer($record, $data, $actor)
            : $this->createLecturer($record, $data, $actor);
    }

    protected function createLecturer(CoreImportRecord $record, array $data, ?User $actor): array
    {
        $identifier = $this->lecturerIdentifier($data);
        $email = $this->required($data, 'email');
        $department = $this->department($this->required($data, 'department_code'));

        if (Lecturer::where('lecturer_number', $identifier)->exists()) {
            throw new InvalidArgumentException('NIDN/NIP sudah ada saat execute.');
        }

        $userResult = $this->resolveUserForProfile($data, 'lecturer', $identifier, $actor);

        $lecturer = Lecturer::create([
            'user_id' => $userResult['user']?->id,
            'lecturer_number' => $identifier,
            'name' => $this->required($data, 'name'),
            'email' => $email,
            'birth_date' => $this->dateValue($data['birth_date'] ?? null),
            'department_id' => $department->id,
            'study_program_id' => filled($data['study_program_code'] ?? null) ? $this->studyProgram($data['study_program_code'])->id : null,
            'phone' => $data['phone'] ?? null,
            'active' => $this->activeValue($data),
        ]);

        return $this->result('lecturers', Lecturer::class, $lecturer->id, 'created', $userResult, 'Lecturer created from import.');
    }

    protected function updateLecturer(CoreImportRecord $record, array $data, ?User $actor): array
    {
        $lecturer = $this->findLecturerForUpdate($data);

        $updates = $this->nonEmpty([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'birth_date' => $this->dateValue($data['birth_date'] ?? null),
            'phone' => $data['phone'] ?? null,
            'active' => array_key_exists('status', $data) ? $this->activeValue($data) : null,
        ]);

        if (filled($data['department_code'] ?? null)) {
            $updates['department_id'] = $this->department($data['department_code'])->id;
        }

        if (filled($data['study_program_code'] ?? null)) {
            $updates['study_program_id'] = $this->studyProgram($data['study_program_code'])->id;
        }

        if (! $lecturer->user_id) {
            $userResult = $this->resolveUserForProfile($data, 'lecturer', $lecturer->lecturer_number, $actor);
            $updates['user_id'] = $userResult['user']?->id;
        } else {
            $userResult = ['user' => $lecturer->user, 'user_created' => false, 'user_linked' => false];
        }

        $previousSnapshot = $this->snapshot($lecturer, array_keys($updates));
        $lecturer->fill($updates)->save();

        return $this->result('lecturers', Lecturer::class, $lecturer->id, 'updated', $userResult, 'Lecturer updated from import.', $previousSnapshot);
    }

    protected function executeEmployeeRecord(CoreImportRecord $record, ?User $actor): array
    {
        $data = $record->normalized_data ?? [];

        return $record->admin_decision === 'update_existing'
            ? $this->updateEmployee($record, $data, $actor)
            : $this->createEmployee($record, $data, $actor);
    }

    protected function createEmployee(CoreImportRecord $record, array $data, ?User $actor): array
    {
        if (filled($data['employee_number'] ?? null) && Employee::where('employee_number', $data['employee_number'])->exists()) {
            throw new InvalidArgumentException('employee_number sudah ada saat execute.');
        }

        if (filled($data['national_id_number'] ?? null) && Employee::where('national_id_number', $data['national_id_number'])->exists()) {
            throw new InvalidArgumentException('national_id_number sudah ada saat execute.');
        }

        $userResult = $this->resolveUserForProfile(
            $data,
            'employee',
            $data['employee_number'] ?? $data['national_id_number'] ?? null,
            $actor,
        );

        $employee = Employee::create([
            'user_id' => $userResult['user']?->id,
            ...$this->employeePayload($data),
        ]);

        return $this->result('employees', Employee::class, $employee->id, 'created', $userResult, 'Employee created from import.');
    }

    protected function updateEmployee(CoreImportRecord $record, array $data, ?User $actor): array
    {
        $employee = $this->findEmployeeForUpdate($data);
        $updates = $this->nonEmpty($this->employeePayload($data));

        if (! $employee->user_id) {
            $userResult = $this->resolveUserForProfile(
                $data,
                'employee',
                $employee->employee_number ?? $employee->national_id_number,
                $actor,
            );
            $updates['user_id'] = $userResult['user']?->id;
        } else {
            $userResult = ['user' => $employee->user, 'user_created' => false, 'user_linked' => false];
        }

        $previousSnapshot = $this->snapshot($employee, array_keys($updates));
        $employee->fill($updates)->save();

        return $this->result('employees', Employee::class, $employee->id, 'updated', $userResult, 'Employee updated from import.', $previousSnapshot);
    }

    protected function resolveUserForProfile(array $data, string $identityType, ?string $fallbackIdentifier, ?User $actor): array
    {
        $username = $this->usernameFor($data, $fallbackIdentifier);
        $email = $data['email'] ?? null;
        $identityNumber = $data['identity_number'] ?? $fallbackIdentifier;

        if (blank($username) || blank($email) || blank($data['birth_date'] ?? null)) {
            return ['user' => null, 'user_created' => false, 'user_linked' => false];
        }

        $matches = User::query()
            ->where(function ($query) use ($username, $email, $identityNumber) {
                $query->where('username', $username)
                    ->orWhere('email', $email);

                if (filled($identityNumber)) {
                    $query->orWhere('identity_number', $identityNumber);
                }
            })
            ->get();

        if ($matches->count() > 1) {
            throw new InvalidArgumentException('Lebih dari satu user cocok untuk row import.');
        }

        if ($matches->count() === 1) {
            return ['user' => $matches->first(), 'user_created' => false, 'user_linked' => true];
        }

        $user = User::create([
            'name' => $this->required($data, 'name'),
            'email' => $email,
            'username' => $username,
            'identity_type' => $identityType,
            'identity_number' => $identityNumber,
            'password' => $this->passwords->hashFromBirthDate($data['birth_date']),
            'active' => true,
            'must_change_password' => true,
            'password_changed_at' => null,
            'last_password_reset_at' => now(),
            'password_reset_by' => $actor?->id,
        ]);

        $this->logActivity($actor, 'import.user_created', [
            'target_user_id' => $user->id,
            'identity_type' => $identityType,
            'method' => 'birth_date_based',
        ]);

        return ['user' => $user, 'user_created' => true, 'user_linked' => false];
    }

    protected function employeePayload(array $data): array
    {
        return [
            'employee_number' => $data['employee_number'] ?? null,
            'national_id_number' => $data['national_id_number'] ?? null,
            'name' => $this->required($data, 'name'),
            'staff_type' => $this->required($data, 'staff_type'),
            'department_id' => filled($data['department_code'] ?? null) ? $this->department($data['department_code'])->id : null,
            'study_program_id' => filled($data['study_program_code'] ?? null) ? $this->studyProgram($data['study_program_code'])->id : null,
            'position_title' => $data['position_title'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'birth_date' => $this->dateValue($data['birth_date'] ?? null),
            'status' => $this->statusValue($data),
        ];
    }

    protected function appAccessResult(UserAppAccess $access, string $action, string $message, ?array $previousSnapshot): array
    {
        return [
            'target_table' => 'user_app_accesses',
            'target_type' => UserAppAccess::class,
            'target_id' => $access->id,
            'action' => $action,
            'message' => $message,
            'created_user_id' => null,
            'linked_user_id' => null,
            'previous_snapshot' => $previousSnapshot,
        ];
    }

    protected function skippedAppAccessResult(?UserAppAccess $access, string $message): array
    {
        return [
            'target_table' => 'user_app_accesses',
            'target_type' => UserAppAccess::class,
            'target_id' => $access?->id ?? 0,
            'action' => 'skipped',
            'execution_status' => 'skipped',
            'message' => $message,
            'created_user_id' => null,
            'linked_user_id' => null,
            'previous_snapshot' => $access ? $this->snapshot($access, ['is_active', 'activated_at', 'deactivated_at']) : null,
        ];
    }

    protected function result(string $table, string $targetType, int $id, string $action, array $userResult, string $message, ?array $previousSnapshot = null): array
    {
        return [
            'target_table' => $table,
            'target_type' => $targetType,
            'target_id' => $id,
            'action' => $action,
            'message' => $message,
            'created_user_id' => ($userResult['user_created'] ?? false) ? $userResult['user']?->id : null,
            'linked_user_id' => ($userResult['user_linked'] ?? false) ? $userResult['user']?->id : null,
            'previous_snapshot' => $previousSnapshot,
        ];
    }

    protected function snapshot($model, array $keys): array
    {
        return collect($keys)
            ->filter(fn (string $key): bool => $key !== '')
            ->mapWithKeys(fn (string $key): array => [$key => $model->getAttribute($key)])
            ->all();
    }

    protected function studyProgram(string $code): StudyProgram
    {
        return StudyProgram::where('code', $code)->firstOrFail();
    }

    protected function department(string $code): Department
    {
        return Department::where('code', $code)->firstOrFail();
    }

    protected function lecturerIdentifier(array $data): string
    {
        return $this->requiredAny($data, ['nidn', 'nip', 'identity_number'], 'nidn/nip/identity_number wajib untuk membuat lecturer.');
    }

    protected function findLecturerForUpdate(array $data): Lecturer
    {
        $query = Lecturer::query();
        $identifiers = array_values(array_filter([$data['nidn'] ?? null, $data['nip'] ?? null]));

        if ($identifiers !== []) {
            $query->whereIn('lecturer_number', $identifiers);
        } elseif (filled($data['email'] ?? null)) {
            $query->where('email', $data['email']);
        } else {
            throw new InvalidArgumentException('Identifier lecturer untuk update tidak tersedia.');
        }

        return $query->firstOrFail();
    }

    protected function findEmployeeForUpdate(array $data): Employee
    {
        if (blank($data['employee_number'] ?? null) && blank($data['national_id_number'] ?? null) && blank($data['email'] ?? null)) {
            throw new InvalidArgumentException('Identifier employee untuk update tidak tersedia.');
        }

        return Employee::query()
            ->when(filled($data['employee_number'] ?? null), fn ($query) => $query->orWhere('employee_number', $data['employee_number']))
            ->when(filled($data['national_id_number'] ?? null), fn ($query) => $query->orWhere('national_id_number', $data['national_id_number']))
            ->when(filled($data['email'] ?? null), fn ($query) => $query->orWhere('email', $data['email']))
            ->firstOrFail();
    }

    protected function required(array $data, string $key): string
    {
        if (blank($data[$key] ?? null)) {
            throw new InvalidArgumentException("{$key} wajib tersedia saat execute.");
        }

        return (string) $data[$key];
    }

    protected function requiredAny(array $data, array $keys, string $message): string
    {
        foreach ($keys as $key) {
            if (filled($data[$key] ?? null)) {
                return (string) $data[$key];
            }
        }

        throw new InvalidArgumentException($message);
    }

    protected function nonEmpty(array $data): array
    {
        return collect($data)
            ->reject(fn (mixed $value): bool => $value === null || $value === '')
            ->all();
    }

    protected function usernameFor(array $data, ?string $fallbackIdentifier): ?string
    {
        return $data['username'] ?? $fallbackIdentifier ?? ($data['email'] ?? null);
    }

    protected function dateValue(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, (string) $value);

                if ($date && $date->format($format) === (string) $value) {
                    return $date->toDateString();
                }
            } catch (Throwable) {
                //
            }
        }

        return Carbon::parse($value)->toDateString();
    }

    protected function statusValue(array $data): string
    {
        return filled($data['status'] ?? null) ? (string) $data['status'] : 'active';
    }

    protected function activeValue(array $data): bool
    {
        return ($data['status'] ?? 'active') !== 'inactive';
    }

    protected function booleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['active', 'true', '1', 'yes'], true);
    }

    protected function assertIdentityType(string $identityType): void
    {
        if (! in_array($identityType, array_keys(config('core_identity.identity_types', [])), true)) {
            throw new InvalidArgumentException('identity_type tidak valid saat execute.');
        }
    }

    protected function safeFailureMessage(Throwable $exception): string
    {
        return Str::limit($exception->getMessage(), 500, '');
    }

    protected function logActivity(?User $actor, string $action, array $meta): void
    {
        if (! $actor) {
            return;
        }

        UserActivityLog::create([
            'user_id' => $actor->id,
            'action' => $action,
            'meta' => $meta,
        ]);
    }
}
