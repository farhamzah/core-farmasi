<?php

namespace App\Services;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\CoreImportBatch;
use App\Models\CoreImportRecord;
use App\Models\Employee;
use App\Models\LeadershipAssignment;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserAppAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CoreDataQualityService
{
    public function summary(int $exampleLimit = 5): array
    {
        return [
            'identity' => $this->identityQuality($exampleLimit),
            'profiles' => $this->profileQuality($exampleLimit),
            'app_access' => $this->appAccessQuality($exampleLimit),
            'leadership' => $this->leadershipQuality($exampleLimit),
            'imports' => $this->importQuality($exampleLimit),
        ];
    }

    protected function identityQuality(int $limit): array
    {
        $usersWithoutRoles = User::doesntHave('roles')->count();
        $usersWithoutAppAccess = User::doesntHave('appAccesses')->count();
        $inactiveWithActiveAccess = User::query()
            ->where('active', false)
            ->whereHas('appAccesses', fn (Builder $query) => $query->where('is_active', true))
            ->count();

        return [
            'metrics' => [
                'total_users' => User::count(),
                'active_users' => User::where('active', true)->count(),
                'inactive_users' => User::where('active', false)->count(),
                'users_without_roles' => $usersWithoutRoles,
                'users_without_app_access' => $usersWithoutAppAccess,
                'users_with_must_change_password' => User::where('must_change_password', true)->count(),
                'users_missing_username' => User::whereNull('username')->orWhere('username', '')->count(),
                'users_missing_identity_number' => User::whereNull('identity_number')->orWhere('identity_number', '')->count(),
                'duplicate_user_emails' => $this->duplicateCount(User::query(), 'email'),
                'duplicate_usernames' => $this->duplicateCount(User::query()->whereNotNull('username'), 'username'),
                'duplicate_identity_numbers' => $this->duplicateCount(User::query()->whereNotNull('identity_number'), 'identity_number'),
                'inactive_users_with_active_app_access' => $inactiveWithActiveAccess,
            ],
            'examples' => [
                'users_without_roles' => $this->userExamples(User::doesntHave('roles'), $limit),
                'users_without_app_access' => $this->userExamples(User::doesntHave('appAccesses'), $limit),
                'inactive_users_with_active_app_access' => $this->userExamples(
                    User::query()
                        ->where('active', false)
                        ->whereHas('appAccesses', fn (Builder $query) => $query->where('is_active', true)),
                    $limit,
                ),
            ],
        ];
    }

    protected function profileQuality(int $limit): array
    {
        return [
            'metrics' => [
                'students_total' => Student::count(),
                'students_without_user' => Student::whereNull('user_id')->count(),
                'students_without_birth_date' => Student::whereNull('birth_date')->count(),
                'students_without_study_program' => Student::whereNull('study_program_id')->count(),
                'duplicate_student_nim' => $this->duplicateCount(Student::query(), 'student_number'),
                'lecturers_total' => Lecturer::count(),
                'lecturers_without_user' => Lecturer::whereNull('user_id')->count(),
                'lecturers_without_birth_date' => Lecturer::whereNull('birth_date')->count(),
                'duplicate_lecturer_nidn' => $this->duplicateCount(Lecturer::query(), 'lecturer_number'),
                'duplicate_lecturer_email' => $this->duplicateCount(Lecturer::query(), 'email'),
                'employees_total' => Employee::count(),
                'employees_without_user' => Employee::whereNull('user_id')->count(),
                'employees_without_birth_date' => Employee::whereNull('birth_date')->count(),
                'duplicate_employee_number' => $this->duplicateCount(Employee::query()->whereNotNull('employee_number'), 'employee_number'),
                'duplicate_employee_email' => $this->duplicateCount(Employee::query()->whereNotNull('email'), 'email'),
            ],
            'examples' => [
                'students_without_user' => $this->profileExamples(Student::whereNull('user_id'), 'student_number', $limit),
                'lecturers_without_birth_date' => $this->profileExamples(Lecturer::whereNull('birth_date'), 'lecturer_number', $limit),
                'employees_without_user' => $this->profileExamples(Employee::whereNull('user_id'), 'employee_number', $limit),
            ],
        ];
    }

    protected function appAccessQuality(int $limit): array
    {
        $unknownAppQuery = UserAppAccess::query()
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('core_applications')
                    ->whereColumn('core_applications.app_code', 'user_app_accesses.app_code')
                    ->whereNull('core_applications.deleted_at');
            });
        $unknownRoleQuery = UserAppAccess::query()
            ->whereNotNull('role_slug')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('core_application_roles')
                    ->whereColumn('core_application_roles.app_code', 'user_app_accesses.app_code')
                    ->whereColumn('core_application_roles.role_slug', 'user_app_accesses.role_slug')
                    ->whereNull('core_application_roles.deleted_at');
            });

        return [
            'metrics' => [
                'total_applications' => CoreApplication::count(),
                'active_applications' => CoreApplication::where('is_active', true)->count(),
                'public_visible_sensitive_apps' => CoreApplication::where('is_public_visible', true)->where('is_sensitive', true)->count(),
                'core_public_visible_warning' => CoreApplication::where('app_code', 'core-farmasi')->where('is_public_visible', true)->exists() ? 1 : 0,
                'app_roles_total' => CoreApplicationRole::count(),
                'user_app_accesses_total' => UserAppAccess::count(),
                'active_user_app_accesses' => UserAppAccess::where('is_active', true)->count(),
                'app_accesses_for_inactive_users' => UserAppAccess::where('is_active', true)->whereHas('user', fn (Builder $query) => $query->where('active', false))->count(),
                'app_accesses_with_unknown_app_code' => (clone $unknownAppQuery)->count(),
                'app_accesses_with_unknown_role_slug' => (clone $unknownRoleQuery)->count(),
            ],
            'examples' => [
                'unknown_app_code' => $this->accessExamples($unknownAppQuery, $limit),
                'unknown_role_slug' => $this->accessExamples($unknownRoleQuery, $limit),
            ],
        ];
    }

    protected function leadershipQuality(int $limit): array
    {
        $today = now()->toDateString();
        $currentDeanQuery = LeadershipAssignment::query()
            ->active()
            ->current($today)
            ->forPosition('dekan');
        $multipleCurrentDeans = max(0, (clone $currentDeanQuery)->count() - 1);
        $activeKaprodiCount = LeadershipAssignment::query()
            ->active()
            ->current($today)
            ->forPosition('kaprodi')
            ->count();
        $expiredButActiveQuery = LeadershipAssignment::query()
            ->where('is_active', true)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', $today);

        return [
            'metrics' => [
                'active_dean_count' => (clone $currentDeanQuery)->count(),
                'current_dean_exists' => (clone $currentDeanQuery)->exists() ? 1 : 0,
                'multiple_current_deans' => $multipleCurrentDeans,
                'study_programs_without_kaprodi_reference' => StudyProgram::whereNull('head_lecturer_id')->count(),
                'active_kaprodi_assignments_count' => $activeKaprodiCount,
                'leadership_assignments_expired_but_active' => (clone $expiredButActiveQuery)->count(),
                'leadership_assignments_without_valid_person' => $this->leadershipWithoutValidPersonCount(),
            ],
            'examples' => [
                'multiple_current_deans' => $this->leadershipExamples($currentDeanQuery->limit($limit), $limit),
                'expired_but_active' => $this->leadershipExamples($expiredButActiveQuery, $limit),
                'without_valid_person' => $this->leadershipWithoutValidPersonExamples($limit),
            ],
        ];
    }

    protected function importQuality(int $limit): array
    {
        return [
            'metrics' => [
                'import_batches_total' => CoreImportBatch::count(),
                'import_batches_failed' => CoreImportBatch::where('status', 'failed')->count(),
                'import_batches_partially_failed' => CoreImportBatch::where('status', 'partially_failed')->count(),
                'import_batches_manual_review' => CoreImportBatch::where('rollback_status', 'manual_review')->count(),
                'rollback_manual_review_count' => CoreImportRecord::where('rollback_status', 'manual_review')->count(),
            ],
            'examples' => [
                'recent_import_batches' => CoreImportBatch::query()
                    ->latest('id')
                    ->limit($limit)
                    ->get(['id', 'source', 'mode', 'status', 'decision_status', 'rollback_status', 'created_at'])
                    ->map(fn (CoreImportBatch $batch): array => [
                        'id' => $batch->id,
                        'source' => $batch->source,
                        'mode' => $batch->mode,
                        'status' => $batch->status,
                        'decision_status' => $batch->decision_status,
                        'rollback_status' => $batch->rollback_status,
                        'created_at' => optional($batch->created_at)->format('Y-m-d H:i'),
                    ])
                    ->all(),
            ],
        ];
    }

    protected function duplicateCount(Builder $query, string $column): int
    {
        return DB::query()
            ->fromSub(
                $query->select($column)->whereNotNull($column)->groupBy($column)->havingRaw('COUNT(*) > 1'),
                'duplicates',
            )
            ->count();
    }

    protected function userExamples(Builder $query, int $limit): array
    {
        return $query
            ->limit($limit)
            ->get(['id', 'name', 'email', 'username', 'active'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'active' => $user->active,
            ])
            ->all();
    }

    protected function profileExamples(Builder $query, string $identifierColumn, int $limit): array
    {
        return $query
            ->limit($limit)
            ->get(['id', 'name', $identifierColumn])
            ->map(fn ($profile): array => [
                'id' => $profile->id,
                'identifier' => $profile->{$identifierColumn},
                'name' => $profile->name,
            ])
            ->all();
    }

    protected function accessExamples(Builder $query, int $limit): array
    {
        return $query
            ->limit($limit)
            ->get(['id', 'user_id', 'app_code', 'role_slug', 'is_active'])
            ->map(fn (UserAppAccess $access): array => [
                'id' => $access->id,
                'user_id' => $access->user_id,
                'app_code' => $access->app_code,
                'role_slug' => $access->role_slug,
                'is_active' => $access->is_active,
            ])
            ->all();
    }

    protected function leadershipExamples(Builder $query, int $limit): array
    {
        return $query
            ->limit($limit)
            ->get(['id', 'position_type', 'unit_type', 'unit_id', 'person_type', 'person_id', 'start_date', 'end_date'])
            ->map(fn (LeadershipAssignment $assignment): array => [
                'id' => $assignment->id,
                'position_type' => $assignment->position_type,
                'unit_type' => $assignment->unit_type,
                'unit_id' => $assignment->unit_id,
                'person_type' => $assignment->person_type,
                'person_id' => $assignment->person_id,
                'start_date' => optional($assignment->start_date)->format('Y-m-d'),
                'end_date' => optional($assignment->end_date)->format('Y-m-d'),
            ])
            ->all();
    }

    protected function leadershipWithoutValidPersonCount(): int
    {
        return LeadershipAssignment::query()
            ->get(['id', 'person_type', 'person_id'])
            ->filter(fn (LeadershipAssignment $assignment): bool => ! $assignment->person)
            ->count();
    }

    protected function leadershipWithoutValidPersonExamples(int $limit): array
    {
        return LeadershipAssignment::query()
            ->get(['id', 'position_type', 'person_type', 'person_id'])
            ->filter(fn (LeadershipAssignment $assignment): bool => ! $assignment->person)
            ->take($limit)
            ->map(fn (LeadershipAssignment $assignment): array => [
                'id' => $assignment->id,
                'position_type' => $assignment->position_type,
                'person_type' => $assignment->person_type,
                'person_id' => $assignment->person_id,
            ])
            ->values()
            ->all();
    }
}
