<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\CoreImportBatch;
use App\Models\CoreImportRecord;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserAppAccess;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class CoreImportValidationService
{
    protected const SUPPORTED_TYPES = [
        'students',
        'lecturers',
        'employees',
        'users',
        'user_role_assignments',
        'user_app_accesses',
    ];

    protected const PROFILE_APP_ROLE_COLUMNS = ['app_code', 'role_slug', 'app_role', 'app_access', 'app_accesses'];

    protected const PROHIBITED_SENSITIVE_COLUMNS = ['password', 'password_confirmation', 'api_token', 'secret', 'client_secret'];

    protected const STAFF_TYPES = ['tendik', 'admin', 'staf_tu', 'laboran', 'other'];

    protected const STATUS_VALUES = ['active', 'inactive'];

    protected const GENDER_VALUES = ['male', 'female'];

    protected const BOOLEAN_VALUES = ['active', 'inactive', 'true', 'false', '1', '0', 'yes', 'no'];

    protected const USER_ROLE_ACTIONS = ['assign', 'skip'];

    protected const USER_APP_ACCESS_ACTIONS = ['assign', 'deactivate', 'skip'];

    protected const EXECUTABLE_DECISIONS = ['create_new', 'update_existing', 'assign', 'deactivate'];

    public function validate(string $importType, string $filePath, int $limit = 50): array
    {
        $result = [
            'import_type' => $importType,
            'is_supported' => in_array($importType, self::SUPPORTED_TYPES, true),
            'total_rows_checked' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'conflict_rows' => 0,
            'warnings_count' => 0,
            'errors_count' => 0,
            'rows' => [],
            'errors' => [],
            'warnings' => [],
        ];

        if (! $result['is_supported']) {
            $result['warnings'][] = 'Row validation belum tersedia untuk import type ini.';

            return $result;
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestDataRow();
            $highestColumn = $sheet->getHighestDataColumn();
            $headings = $this->normalizeHeadings($sheet->rangeToArray("A1:{$highestColumn}1", null, true, false)[0] ?? []);
            $rows = $sheet->rangeToArray("A2:{$highestColumn}" . min($highestRow, $limit + 1), null, true, false);
            $seen = [];

            foreach ($rows as $offset => $row) {
                $rowNumber = $offset + 2;
                $data = $this->mapRow($headings, $row);

                if ($this->isBlankRow($data)) {
                    continue;
                }

                $rowResult = match ($importType) {
                    'students' => $this->validateStudentRow($rowNumber, $data, $seen),
                    'lecturers' => $this->validateLecturerRow($rowNumber, $data, $seen),
                    'employees' => $this->validateEmployeeRow($rowNumber, $data, $seen),
                    'users' => $this->validateUserRow($rowNumber, $data, $seen),
                    'user_role_assignments' => $this->validateUserRoleAssignmentRow($rowNumber, $data, $seen),
                    'user_app_accesses' => $this->validateUserAppAccessRow($rowNumber, $data, $seen),
                };

                $result['rows'][] = $rowResult;
                $result['total_rows_checked']++;
                $result['warnings_count'] += count($rowResult['warnings']);
                $result['errors_count'] += count($rowResult['errors']);

                if ($rowResult['is_valid']) {
                    $result['valid_rows']++;
                } else {
                    $result['invalid_rows']++;
                }

                if ($rowResult['conflicts'] !== []) {
                    $result['conflict_rows']++;
                }
            }
        } catch (Throwable $exception) {
            $result['errors'][] = 'File tidak dapat dibaca untuk validasi row.';
            $result['warnings'][] = $exception->getMessage();
        }

        return $result;
    }

    public function persistValidationResults(CoreImportBatch $batch, array $validationResult): void
    {
        if (! ($validationResult['is_supported'] ?? false)) {
            $batch->forceFill([
                'decision_status' => 'none',
                'decided_rows_count' => 0,
                'pending_decision_rows_count' => 0,
                'executable_rows_count' => 0,
            ])->save();

            return;
        }

        $batch->records()->delete();

        foreach ($validationResult['rows'] ?? [] as $row) {
            $validationStatus = $this->validationStatus($row);
            $defaultDecision = $this->defaultDecision($validationStatus, $row['suggested_action'] ?? null, $validationResult['import_type'] ?? null);

            CoreImportRecord::create([
                'core_import_batch_id' => $batch->id,
                'source_table' => $validationResult['import_type'],
                'source_id' => (string) ($row['row_number'] ?? ''),
                'source_identifier' => $row['identifier'] ?? null,
                'target_table' => $validationResult['import_type'],
                'target_id' => null,
                'action' => 'decision_preview',
                'payload_snapshot' => [
                    'row_number' => $row['row_number'] ?? null,
                    'identifier' => $row['identifier'] ?? null,
                ],
                'message' => $this->recordMessage($row),
                'validation_status' => $validationStatus,
                'suggested_action' => $row['suggested_action'] ?? null,
                'admin_decision' => $defaultDecision,
                'normalized_data' => $this->stripSensitiveData($row['normalized_data'] ?? []),
                'errors' => $row['errors'] ?? [],
                'warnings' => $row['warnings'] ?? [],
                'conflicts' => $row['conflicts'] ?? [],
                'execution_status' => 'not_executed',
            ]);
        }

        $this->summarizeDecisions($batch->fresh());
    }

    public function summarizeDecisions(CoreImportBatch $batch): array
    {
        $records = $batch->records()->get();
        $pending = $records->where('admin_decision', 'needs_admin_decision')->count();
        $executable = $records->whereIn('admin_decision', self::EXECUTABLE_DECISIONS)->count();
        $skipped = $records->where('admin_decision', 'skip')->count();
        $invalid = $records->where('admin_decision', 'invalid')->count();
        $decided = $records->whereNotNull('decided_at')->count();

        $summary = [
            'total_rows' => $records->count(),
            'valid_rows' => $records->where('validation_status', 'valid')->count(),
            'conflict_rows' => $records->where('validation_status', 'conflict')->count(),
            'invalid_rows' => $records->where('validation_status', 'invalid')->count(),
            'pending_decisions' => $pending,
            'executable_rows' => $executable,
            'skipped_rows' => $skipped,
            'invalid_decisions' => $invalid,
            'decided_rows' => $decided,
        ];

        $batchSummary = $batch->summary ?? [];
        $batchSummary['decision'] = $summary;

        $batch->forceFill([
            'status' => $pending > 0 ? 'waiting_decision' : 'decision_ready',
            'decision_status' => $pending > 0 ? ($decided > 0 ? 'partial' : 'pending') : 'ready',
            'decided_rows_count' => $decided,
            'pending_decision_rows_count' => $pending,
            'executable_rows_count' => $executable,
            'summary' => $batchSummary,
        ])->save();

        return $summary;
    }

    public function allowedDecisionsForStatus(string $validationStatus, ?string $importType = null): array
    {
        if ($importType === 'user_role_assignments') {
            return match ($validationStatus) {
                'valid', 'conflict' => ['assign', 'skip'],
                'invalid' => ['invalid', 'skip'],
                default => ['skip'],
            };
        }

        if ($importType === 'user_app_accesses') {
            return match ($validationStatus) {
                'valid', 'conflict' => ['assign', 'deactivate', 'skip'],
                'invalid' => ['invalid', 'skip'],
                default => ['skip'],
            };
        }

        return match ($validationStatus) {
            'valid' => ['create_new', 'skip'],
            'conflict' => ['needs_admin_decision', 'update_existing', 'skip', 'create_new'],
            'invalid' => ['invalid', 'skip'],
            default => ['skip'],
        };
    }

    protected function validationStatus(array $row): string
    {
        if (($row['errors'] ?? []) !== []) {
            return 'invalid';
        }

        if (($row['conflicts'] ?? []) !== []) {
            return 'conflict';
        }

        return 'valid';
    }

    public function defaultDecision(string $validationStatus, ?string $suggestedAction, ?string $importType = null): string
    {
        $allowed = $this->allowedDecisionsForStatus($validationStatus, $importType);

        if ($suggestedAction && in_array($suggestedAction, $allowed, true) && $suggestedAction !== 'needs_admin_decision') {
            return $suggestedAction;
        }

        return match ($validationStatus) {
            'valid' => $importType === 'user_role_assignments' || $importType === 'user_app_accesses' ? 'assign' : 'create_new',
            'conflict' => $importType === 'user_role_assignments' || $importType === 'user_app_accesses' ? 'skip' : 'needs_admin_decision',
            'invalid' => 'invalid',
            default => $suggestedAction ?: 'skip',
        };
    }

    protected function recordMessage(array $row): ?string
    {
        return collect([
            ...($row['errors'] ?? []),
            ...($row['warnings'] ?? []),
            ...($row['conflicts'] ?? []),
        ])->implode(' ');
    }

    protected function stripSensitiveData(array $data): array
    {
        foreach (array_keys($data) as $key) {
            if (str_contains($key, 'password') || in_array($key, self::PROHIBITED_SENSITIVE_COLUMNS, true) || str_contains($key, 'secret') || str_contains($key, 'token')) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    protected function validateStudentRow(int $rowNumber, array $data, array &$seen): array
    {
        $errors = [];
        $warnings = [];
        $conflicts = [];

        $data = $this->sanitizeProfileData($data, $warnings);
        $nim = $this->value($data, 'nim');

        $this->require($data, 'nim', $errors);
        $this->require($data, 'name', $errors);
        $this->require($data, 'study_program_code', $errors);
        $this->validateEmail($data, 'email', $errors);
        $this->validateDate($data, 'birth_date', $errors);
        $this->validateIn($data, 'status', self::STATUS_VALUES, $errors);
        $this->validateIn($data, 'gender', self::GENDER_VALUES, $errors);

        if (filled($this->value($data, 'study_program_code')) && ! StudyProgram::where('code', $this->value($data, 'study_program_code'))->exists()) {
            $errors[] = 'study_program_code tidak ditemukan.';
        }

        if (filled($nim)) {
            if (Student::where('student_number', $nim)->exists()) {
                $conflicts[] = 'NIM sudah ada di students.';
            }

            $this->detectDuplicateInFile($seen, 'students:nim', $nim, $warnings);
        }

        $this->detectUserConflicts($data, $conflicts);
        $this->detectNameBirthDateWarning(Student::query(), $data, $warnings);

        return $this->rowResult($rowNumber, $data, $errors, $warnings, $conflicts, $nim ?: $this->value($data, 'name'));
    }

    protected function validateLecturerRow(int $rowNumber, array $data, array &$seen): array
    {
        $errors = [];
        $warnings = [];
        $conflicts = [];

        $data = $this->sanitizeProfileData($data, $warnings);
        $nidn = $this->value($data, 'nidn');
        $nip = $this->value($data, 'nip');
        $primary = $nidn ?: $nip ?: $this->value($data, 'identity_number');

        $this->require($data, 'name', $errors);
        $this->validateEmail($data, 'email', $errors);
        $this->validateDate($data, 'birth_date', $errors);
        $this->validateIn($data, 'status', self::STATUS_VALUES, $errors);

        if (blank($nidn) && blank($nip) && blank($this->value($data, 'email')) && blank($this->value($data, 'identity_number'))) {
            $warnings[] = 'Minimal salah satu dari nidn/nip/email/identity_number sebaiknya diisi.';
        }

        if (filled($this->value($data, 'department_code')) && ! Department::where('code', $this->value($data, 'department_code'))->exists()) {
            $errors[] = 'department_code tidak ditemukan.';
        }

        if (filled($this->value($data, 'study_program_code')) && ! StudyProgram::where('code', $this->value($data, 'study_program_code'))->exists()) {
            $errors[] = 'study_program_code tidak ditemukan.';
        }

        foreach (array_filter([$nidn, $nip]) as $identifier) {
            if (Lecturer::where('lecturer_number', $identifier)->exists()) {
                $conflicts[] = 'NIDN/NIP sudah ada di lecturers.';
            }

            $this->detectDuplicateInFile($seen, 'lecturers:identifier', $identifier, $warnings);
        }

        $this->detectUserConflicts($data, $conflicts);
        $this->detectEmailConflict(Lecturer::query(), $data, $conflicts);
        $this->detectNameBirthDateWarning(Lecturer::query(), $data, $warnings);

        return $this->rowResult($rowNumber, $data, $errors, $warnings, array_values(array_unique($conflicts)), $primary ?: $this->value($data, 'name'));
    }

    protected function validateEmployeeRow(int $rowNumber, array $data, array &$seen): array
    {
        $errors = [];
        $warnings = [];
        $conflicts = [];

        $data = $this->sanitizeProfileData($data, $warnings);
        $employeeNumber = $this->value($data, 'employee_number');
        $nationalId = $this->value($data, 'national_id_number');

        $this->require($data, 'name', $errors);
        $this->require($data, 'staff_type', $errors);
        $this->validateIn($data, 'staff_type', self::STAFF_TYPES, $errors);
        $this->validateEmail($data, 'email', $errors);
        $this->validateDate($data, 'birth_date', $errors);
        $this->validateIn($data, 'status', self::STATUS_VALUES, $errors);

        if (filled($this->value($data, 'department_code')) && ! Department::where('code', $this->value($data, 'department_code'))->exists()) {
            $errors[] = 'department_code tidak ditemukan.';
        }

        if (filled($this->value($data, 'study_program_code')) && ! StudyProgram::where('code', $this->value($data, 'study_program_code'))->exists()) {
            $errors[] = 'study_program_code tidak ditemukan.';
        }

        if (filled($employeeNumber)) {
            if (Employee::where('employee_number', $employeeNumber)->exists()) {
                $conflicts[] = 'employee_number sudah ada di employees.';
            }

            $this->detectDuplicateInFile($seen, 'employees:employee_number', $employeeNumber, $warnings);
        }

        if (filled($nationalId)) {
            if (Employee::where('national_id_number', $nationalId)->exists()) {
                $conflicts[] = 'national_id_number sudah ada di employees.';
            }

            $this->detectDuplicateInFile($seen, 'employees:national_id_number', $nationalId, $warnings);
        }

        $this->detectUserConflicts($data, $conflicts);
        $this->detectEmailConflict(Employee::query(), $data, $conflicts);
        $this->detectNameBirthDateWarning(Employee::query(), $data, $warnings);

        return $this->rowResult($rowNumber, $data, $errors, $warnings, $conflicts, $employeeNumber ?: $nationalId ?: $this->value($data, 'name'));
    }

    protected function validateUserRow(int $rowNumber, array $data, array &$seen): array
    {
        $errors = [];
        $warnings = [];
        $conflicts = [];

        $data = $this->sanitizeSensitiveImportData($data, $errors);
        $username = $this->value($data, 'username');
        $email = $this->value($data, 'email');
        $identityType = $this->value($data, 'identity_type');
        $identityNumber = $this->value($data, 'identity_number');

        $this->require($data, 'name', $errors);
        $this->require($data, 'username', $errors);
        $this->require($data, 'identity_type', $errors);
        $this->require($data, 'identity_number', $errors);
        $this->validateIn($data, 'identity_type', $this->identityTypes(), $errors);
        $this->validateEmail($data, 'email', $errors);
        $this->validateDate($data, 'birth_date', $errors);
        $this->validateBooleanLike($data, 'is_active', $errors);
        $this->validateBooleanLike($data, 'must_change_password', $errors);

        if (filled($username)) {
            if (User::where('username', $username)->exists()) {
                $conflicts[] = 'username sudah ada di users.';
            }

            $this->detectDuplicateInFile($seen, 'users:username', $username, $warnings);
        }

        if (filled($email)) {
            if (User::where('email', $email)->exists()) {
                $conflicts[] = 'email sudah ada di users.';
            }

            $this->detectDuplicateInFile($seen, 'users:email', $email, $warnings);
        }

        if (filled($identityNumber)) {
            if (User::where('identity_number', $identityNumber)->exists()) {
                $conflicts[] = 'identity_number sudah ada di users.';
            }

            if (filled($identityType) && User::where('identity_type', $identityType)->where('identity_number', $identityNumber)->exists()) {
                $conflicts[] = 'identity_type dan identity_number sudah ada di users.';
            }

            $this->detectDuplicateInFile($seen, 'users:identity_number', $identityNumber, $warnings);
        }

        return $this->rowResult($rowNumber, $data, $errors, $warnings, array_values(array_unique($conflicts)), $username ?: $identityNumber ?: $this->value($data, 'name'));
    }

    protected function validateUserRoleAssignmentRow(int $rowNumber, array $data, array &$seen): array
    {
        $errors = [];
        $warnings = [];
        $conflicts = [];

        $data = $this->sanitizeSensitiveImportData($data, $errors);
        $username = $this->value($data, 'username');
        $roleSlug = $this->value($data, 'role_slug');
        $action = $this->value($data, 'action') ?: 'assign';
        $user = filled($username) ? User::where('username', $username)->first() : null;

        $this->require($data, 'username', $errors);
        $this->require($data, 'role_slug', $errors);
        $this->validateIn(['action' => $action], 'action', self::USER_ROLE_ACTIONS, $errors);

        if (array_key_exists('app_code', $data)) {
            unset($data['app_code']);
            $warnings[] = 'app_code tidak diproses di import role global. Gunakan user_app_accesses untuk app role.';
        }

        if (filled($username) && ! $user) {
            $errors[] = 'username tidak ditemukan di users.';
        }

        $role = filled($roleSlug) ? Role::where('name', $roleSlug)->where('active', true)->first() : null;

        if (filled($roleSlug) && ! $role) {
            $errors[] = 'role_slug tidak ditemukan di global roles aktif.';
        }

        if ($user && $role && $user->roles()->where('roles.id', $role->id)->exists()) {
            $conflicts[] = 'User sudah memiliki role global ini.';
        }

        if (filled($username) && filled($roleSlug)) {
            $this->detectDuplicateInFile($seen, 'user_role_assignments', "{$username}:{$roleSlug}", $warnings);
        }

        $suggestedAction = $errors !== []
            ? 'invalid'
            : ($action === 'skip' || $conflicts !== [] ? 'skip' : 'assign');

        return $this->rowResult($rowNumber, $data, $errors, $warnings, $conflicts, trim("{$username} {$roleSlug}"), $suggestedAction);
    }

    protected function validateUserAppAccessRow(int $rowNumber, array $data, array &$seen): array
    {
        $errors = [];
        $warnings = [];
        $conflicts = [];

        $data = $this->sanitizeSensitiveImportData($data, $errors);
        $username = $this->value($data, 'username');
        $appCode = $this->value($data, 'app_code');
        $roleSlug = $this->value($data, 'role_slug');
        $action = $this->value($data, 'action') ?: 'assign';
        $user = filled($username) ? User::where('username', $username)->first() : null;

        $this->require($data, 'username', $errors);
        $this->require($data, 'app_code', $errors);
        $this->require($data, 'role_slug', $errors);
        $this->validateIn(['action' => $action], 'action', self::USER_APP_ACCESS_ACTIONS, $errors);
        $this->validateBooleanLike($data, 'is_active', $errors);

        if (filled($username) && ! $user) {
            $errors[] = 'username tidak ditemukan di users.';
        }

        $application = filled($appCode) ? CoreApplication::where('app_code', $appCode)->first() : null;

        if (filled($appCode) && ! $application) {
            $errors[] = 'app_code tidak ditemukan di core_applications.';
        } elseif ($application && ! $application->is_active) {
            $errors[] = 'app_code tidak aktif.';
        }

        $applicationRole = filled($appCode) && filled($roleSlug)
            ? CoreApplicationRole::where('app_code', $appCode)->where('role_slug', $roleSlug)->first()
            : null;

        if (filled($appCode) && filled($roleSlug) && ! $applicationRole) {
            $errors[] = 'role_slug tidak ditemukan untuk app_code tersebut.';
        } elseif ($applicationRole && ! $applicationRole->is_active) {
            $errors[] = 'role_slug untuk app_code tersebut tidak aktif.';
        }

        $existingAccess = $user && filled($appCode) && filled($roleSlug)
            ? UserAppAccess::where('user_id', $user->id)->where('app_code', $appCode)->where('role_slug', $roleSlug)->first()
            : null;

        if ($existingAccess?->is_active) {
            $conflicts[] = 'User sudah memiliki app access aktif ini.';
        } elseif ($existingAccess && ! $existingAccess->is_active) {
            $conflicts[] = 'User memiliki app access ini dalam status inactive.';
        }

        if (filled($username) && filled($appCode) && filled($roleSlug)) {
            $this->detectDuplicateInFile($seen, 'user_app_accesses', "{$username}:{$appCode}:{$roleSlug}", $warnings);
        }

        $suggestedAction = 'assign';

        if ($errors !== []) {
            $suggestedAction = 'invalid';
        } elseif ($action === 'skip') {
            $suggestedAction = 'skip';
        } elseif ($action === 'deactivate') {
            $suggestedAction = $existingAccess?->is_active ? 'deactivate' : 'skip';
        } elseif ($existingAccess?->is_active) {
            $suggestedAction = 'skip';
        } elseif ($existingAccess && ! $existingAccess->is_active) {
            $suggestedAction = 'skip';
        }

        return $this->rowResult($rowNumber, $data, $errors, $warnings, $conflicts, trim("{$username} {$appCode} {$roleSlug}"), $suggestedAction);
    }

    protected function rowResult(int $rowNumber, array $data, array $errors, array $warnings, array $conflicts, ?string $identifier, ?string $suggestedAction = null): array
    {
        $isValid = $errors === [];

        return [
            'row_number' => $rowNumber,
            'identifier' => $identifier,
            'normalized_data' => $data,
            'errors' => array_values(array_unique($errors)),
            'warnings' => array_values(array_unique($warnings)),
            'conflicts' => array_values(array_unique($conflicts)),
            'suggested_action' => $suggestedAction ?: $this->suggestedAction($errors, $conflicts),
            'is_valid' => $isValid,
            'can_import_later' => $isValid,
        ];
    }

    protected function suggestedAction(array $errors, array $conflicts): string
    {
        if ($errors !== []) {
            return 'invalid';
        }

        if ($conflicts !== []) {
            return 'needs_admin_decision';
        }

        return 'create_new';
    }

    protected function sanitizeProfileData(array $data, array &$warnings): array
    {
        foreach (array_keys($data) as $key) {
            if (str_contains($key, 'password') || in_array($key, self::PROHIBITED_SENSITIVE_COLUMNS, true) || str_contains($key, 'secret') || str_contains($key, 'token')) {
                unset($data[$key]);
                $warnings[] = str_contains($key, 'password')
                    ? 'Kolom password tidak diproses.'
                    : 'Kolom sensitif tidak diproses.';
            }

            if (in_array($key, self::PROFILE_APP_ROLE_COLUMNS, true)) {
                unset($data[$key]);
                $warnings[] = 'Kolom app role/app access tidak diproses pada profile import.';
            }
        }

        return $data;
    }

    protected function sanitizeSensitiveImportData(array $data, array &$errors): array
    {
        foreach (array_keys($data) as $key) {
            if (str_contains($key, 'password') || in_array($key, self::PROHIBITED_SENSITIVE_COLUMNS, true) || str_contains($key, 'secret') || str_contains($key, 'token')) {
                unset($data[$key]);
                $errors[] = "Kolom {$key} tidak diperbolehkan.";
            }
        }

        return $data;
    }

    protected function detectUserConflicts(array $data, array &$conflicts): void
    {
        if (filled($this->value($data, 'username')) && User::where('username', $this->value($data, 'username'))->exists()) {
            $conflicts[] = 'username sudah ada di users.';
        }

        if (filled($this->value($data, 'identity_number')) && User::where('identity_number', $this->value($data, 'identity_number'))->exists()) {
            $conflicts[] = 'identity_number sudah ada di users.';
        }

        if (filled($this->value($data, 'email')) && User::where('email', $this->value($data, 'email'))->exists()) {
            $conflicts[] = 'email sudah ada di users.';
        }
    }

    protected function detectEmailConflict($query, array $data, array &$conflicts): void
    {
        if (filled($this->value($data, 'email')) && $query->where('email', $this->value($data, 'email'))->exists()) {
            $conflicts[] = 'email sudah ada di profile terkait.';
        }
    }

    protected function detectNameBirthDateWarning($query, array $data, array &$warnings): void
    {
        if (blank($this->value($data, 'name')) || blank($this->value($data, 'birth_date'))) {
            return;
        }

        $date = $this->parseDate($this->value($data, 'birth_date'));

        if (! $date) {
            return;
        }

        if ($query->where('name', $this->value($data, 'name'))->whereDate('birth_date', $date->format('Y-m-d'))->exists()) {
            $warnings[] = 'Nama dan tanggal lahir sama dengan data existing.';
        }
    }

    protected function detectDuplicateInFile(array &$seen, string $key, string $value, array &$warnings): void
    {
        $seen[$key] ??= [];

        if (in_array($value, $seen[$key], true)) {
            $warnings[] = "Duplikasi {$key} di file upload.";

            return;
        }

        $seen[$key][] = $value;
    }

    protected function require(array $data, string $key, array &$errors): void
    {
        if (blank($this->value($data, $key))) {
            $errors[] = "{$key} wajib diisi.";
        }
    }

    protected function validateEmail(array $data, string $key, array &$errors): void
    {
        $value = $this->value($data, $key);

        if (filled($value) && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "{$key} tidak valid.";
        }
    }

    protected function validateDate(array $data, string $key, array &$errors): void
    {
        $value = $this->value($data, $key);

        if (filled($value) && ! $this->parseDate($value)) {
            $errors[] = "{$key} tidak valid.";
        }
    }

    protected function validateIn(array $data, string $key, array $allowed, array &$errors): void
    {
        $value = $this->value($data, $key);

        if (filled($value) && ! in_array($value, $allowed, true)) {
            $errors[] = "{$key} harus salah satu: " . implode(', ', $allowed) . '.';
        }
    }

    protected function validateBooleanLike(array $data, string $key, array &$errors): void
    {
        $value = $this->value($data, $key);

        if (filled($value) && ! in_array(strtolower($value), self::BOOLEAN_VALUES, true)) {
            $errors[] = "{$key} harus bernilai active/inactive, true/false, 1/0, atau yes/no.";
        }
    }

    protected function identityTypes(): array
    {
        return array_keys(config('core_identity.identity_types', []));
    }

    protected function parseDate(string $value): ?Carbon
    {
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);

                if ($date && $date->format($format) === $value) {
                    return $date;
                }
            } catch (Throwable) {
                //
            }
        }

        return null;
    }

    protected function mapRow(array $headings, array $row): array
    {
        $mapped = [];

        foreach ($headings as $index => $heading) {
            $value = $row[$index] ?? null;
            $mapped[$heading] = is_string($value) ? trim($value) : $value;
        }

        return $mapped;
    }

    protected function normalizeHeadings(array $headings): array
    {
        return collect($headings)
            ->map(fn (mixed $heading): string => str((string) $heading)
                ->trim()
                ->lower()
                ->replace([' ', '-', '.'], '_')
                ->replaceMatches('/[^a-z0-9_]/', '')
                ->replaceMatches('/_+/', '_')
                ->trim('_')
                ->toString())
            ->filter()
            ->values()
            ->all();
    }

    protected function isBlankRow(array $data): bool
    {
        return collect($data)->every(fn (mixed $value): bool => blank($value));
    }

    protected function value(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return filled($value) ? (string) $value : null;
    }
}
