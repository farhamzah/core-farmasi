<?php

namespace App\Services;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Models\UserAppAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BulkUserAppAccessService
{
    public const TARGET_SCOPES = [
        'identity_type' => 'Jenis Akun',
        'global_role' => 'Role Global',
        'student_nim_prefix' => 'Prefix NIM Mahasiswa',
        'student_study_program' => 'Program Studi Mahasiswa',
        'lecturer_nidn_prefix' => 'Prefix NIDN Dosen',
        'lecturer_department' => 'Departemen Dosen',
        'employee_staff_type' => 'Jenis Tendik / Staf / Laboran',
        'employee_department' => 'Departemen Tendik / Staf',
    ];

    public function preview(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);
        $blockers = $this->validateCatalog($filters);

        if ($blockers !== []) {
            return $this->emptyResult($filters, $blockers);
        }

        $users = $this->eligibleUsersQuery($filters)
            ->with(['roles:id,name,label', 'student:id,user_id,student_number,study_program_id', 'lecturer:id,user_id,lecturer_number,nidn,department_id', 'employee:id,user_id,employee_number,staff_type,department_id'])
            ->orderBy('name')
            ->get();

        $rows = $this->buildRows($users, $filters);
        $counts = $this->summarizeRows($rows);

        return [
            'filters' => $filters,
            'blockers' => [],
            'warnings' => $this->warnings($rows, $filters),
            'counts' => $counts,
            'rows' => $rows,
            'samples' => $rows->take(12)->values()->all(),
            'can_apply' => $counts['planned_insert'] + $counts['planned_reactivate'] > 0,
        ];
    }

    public function apply(array $filters, ?User $operator = null): array
    {
        $preview = $this->preview($filters);

        if ($preview['blockers'] !== []) {
            return array_merge($preview, [
                'applied' => false,
                'message' => 'Bulk access tidak dijalankan karena masih ada blocker.',
            ]);
        }

        $filters = $preview['filters'];
        $rows = collect($preview['rows']);
        $applicableRows = $rows->whereIn('action', ['insert', 'reactivate'])->values();

        if ($applicableRows->isEmpty()) {
            return array_merge($preview, [
                'applied' => false,
                'message' => 'Tidak ada akses baru yang perlu dibuat atau diaktifkan ulang.',
            ]);
        }

        $created = 0;
        $reactivated = 0;

        DB::transaction(function () use ($applicableRows, $filters, $operator, &$created, &$reactivated): void {
            foreach ($applicableRows as $row) {
                if ($row['action'] === 'insert') {
                    $access = UserAppAccess::query()->create([
                        'user_id' => $row['user_id'],
                        'app_code' => $filters['app_code'],
                        'role_slug' => $filters['role_slug'],
                        'permissions' => [],
                        'is_active' => true,
                        'activated_at' => $filters['activated_at'] ?? now(),
                        'deactivated_at' => $filters['deactivated_at'],
                    ]);

                    $created++;
                    $this->logAccessChange($access, 'user_app_access.bulk_created', $filters, $operator);

                    continue;
                }

                $access = UserAppAccess::query()
                    ->where('user_id', $row['user_id'])
                    ->where('app_code', $filters['app_code'])
                    ->where('role_slug', $filters['role_slug'])
                    ->first();

                if (! $access) {
                    continue;
                }

                $access->forceFill([
                    'is_active' => true,
                    'activated_at' => $filters['activated_at'] ?? now(),
                    'deactivated_at' => $filters['deactivated_at'],
                ])->save();

                $reactivated++;
                $this->logAccessChange($access, 'user_app_access.bulk_reactivated', $filters, $operator);
            }
        });

        return array_merge($preview, [
            'applied' => true,
            'created' => $created,
            'reactivated' => $reactivated,
            'message' => "Bulk access selesai. Dibuat {$created}, diaktifkan ulang {$reactivated}.",
        ]);
    }

    public function eligibleUsersQuery(array $filters): Builder
    {
        $filters = $this->normalizeFilters($filters);

        return User::query()
            ->when(! $filters['include_inactive'], fn (Builder $query): Builder => $query->where('active', true))
            ->when($filters['target_scope'] === 'identity_type', fn (Builder $query): Builder => $query->where('identity_type', $filters['target_value']))
            ->when($filters['target_scope'] === 'global_role', fn (Builder $query): Builder => $query->whereHas('roles', fn (Builder $roleQuery): Builder => $roleQuery->where('name', $filters['target_value'])))
            ->when($filters['target_scope'] === 'student_nim_prefix', fn (Builder $query): Builder => $query->whereHas('student', fn (Builder $studentQuery): Builder => $studentQuery->where('student_number', 'like', $filters['target_value'].'%')))
            ->when($filters['target_scope'] === 'student_study_program', fn (Builder $query): Builder => $query->whereHas('student', fn (Builder $studentQuery): Builder => $studentQuery->where('study_program_id', $filters['target_value'])))
            ->when($filters['target_scope'] === 'lecturer_nidn_prefix', fn (Builder $query): Builder => $query->whereHas('lecturer', fn (Builder $lecturerQuery): Builder => $lecturerQuery->where('nidn', 'like', $filters['target_value'].'%')))
            ->when($filters['target_scope'] === 'lecturer_department', fn (Builder $query): Builder => $query->whereHas('lecturer', fn (Builder $lecturerQuery): Builder => $lecturerQuery->where('department_id', $filters['target_value'])))
            ->when($filters['target_scope'] === 'employee_staff_type', fn (Builder $query): Builder => $query->whereHas('employee', fn (Builder $employeeQuery): Builder => $employeeQuery->where('staff_type', $filters['target_value'])))
            ->when($filters['target_scope'] === 'employee_department', fn (Builder $query): Builder => $query->whereHas('employee', fn (Builder $employeeQuery): Builder => $employeeQuery->where('department_id', $filters['target_value'])));
    }

    public function normalizeFilters(array $filters): array
    {
        return [
            'app_code' => trim((string) ($filters['app_code'] ?? '')),
            'role_slug' => trim((string) ($filters['role_slug'] ?? '')),
            'target_scope' => trim((string) ($filters['target_scope'] ?? 'identity_type')),
            'target_value' => trim((string) ($filters['target_value'] ?? '')),
            'include_inactive' => (bool) ($filters['include_inactive'] ?? false),
            'reactivate_existing' => (bool) ($filters['reactivate_existing'] ?? true),
            'activated_at' => $filters['activated_at'] ?? null,
            'deactivated_at' => $filters['deactivated_at'] ?? null,
        ];
    }

    protected function validateCatalog(array $filters): array
    {
        $blockers = [];

        if (! array_key_exists($filters['target_scope'], self::TARGET_SCOPES)) {
            $blockers[] = 'Target kolektif tidak dikenali.';
        }

        if ($filters['target_value'] === '') {
            $blockers[] = 'Nilai target kolektif wajib diisi.';
        }

        $applicationExists = CoreApplication::query()
            ->active()
            ->where('app_code', $filters['app_code'])
            ->exists();

        if (! $applicationExists) {
            $blockers[] = 'Aplikasi tidak ditemukan atau tidak aktif.';
        }

        $roleExists = CoreApplicationRole::query()
            ->active()
            ->where('app_code', $filters['app_code'])
            ->where('role_slug', $filters['role_slug'])
            ->exists();

        if (! $roleExists) {
            $blockers[] = 'Role aplikasi tidak ditemukan, tidak aktif, atau tidak cocok dengan aplikasi yang dipilih.';
        }

        return $blockers;
    }

    protected function buildRows(Collection $users, array $filters): Collection
    {
        $userIds = $users->pluck('id')->all();

        $existingByUser = UserAppAccess::query()
            ->whereIn('user_id', $userIds)
            ->where('app_code', $filters['app_code'])
            ->where('role_slug', $filters['role_slug'])
            ->get()
            ->keyBy('user_id');

        $sameAppOtherRoleCounts = UserAppAccess::query()
            ->whereIn('user_id', $userIds)
            ->where('app_code', $filters['app_code'])
            ->where('role_slug', '!=', $filters['role_slug'])
            ->selectRaw('user_id, COUNT(*) as aggregate')
            ->groupBy('user_id')
            ->pluck('aggregate', 'user_id');

        return $users->map(function (User $user) use ($existingByUser, $sameAppOtherRoleCounts, $filters): array {
            $existing = $existingByUser->get($user->id);
            $otherRoleCount = (int) ($sameAppOtherRoleCounts[$user->id] ?? 0);
            $action = 'insert';
            $status = 'Akses akan dibuat';

            if (! $user->active) {
                $action = 'skip_inactive_user';
                $status = 'Skip: user tidak aktif';
            } elseif ($existing?->is_active) {
                $action = 'skip_existing';
                $status = 'Skip: akses aktif sudah ada';
            } elseif ($existing && $filters['reactivate_existing']) {
                $action = 'reactivate';
                $status = 'Akses lama akan diaktifkan ulang';
            } elseif ($existing) {
                $action = 'skip_inactive_access';
                $status = 'Skip: akses lama nonaktif';
            }

            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'identity_type' => $user->identity_type,
                'identity_number' => $user->identity_number,
                'active' => (bool) $user->active,
                'student_number' => $user->student?->student_number,
                'lecturer_number' => $user->lecturer?->lecturer_number,
                'employee_number' => $user->employee?->employee_number,
                'same_app_other_role_count' => $otherRoleCount,
                'action' => $action,
                'status' => $status,
            ];
        });
    }

    protected function summarizeRows(Collection $rows): array
    {
        return [
            'matched_users' => $rows->count(),
            'planned_insert' => $rows->where('action', 'insert')->count(),
            'planned_reactivate' => $rows->where('action', 'reactivate')->count(),
            'skipped_existing' => $rows->where('action', 'skip_existing')->count(),
            'skipped_inactive_user' => $rows->where('action', 'skip_inactive_user')->count(),
            'skipped_inactive_access' => $rows->where('action', 'skip_inactive_access')->count(),
            'users_with_other_roles_same_app' => $rows->where('same_app_other_role_count', '>', 0)->count(),
        ];
    }

    protected function warnings(Collection $rows, array $filters): array
    {
        $warnings = [];

        if ($rows->isEmpty()) {
            $warnings[] = 'Tidak ada user yang cocok dengan filter target.';
        }

        if ($rows->where('same_app_other_role_count', '>', 0)->isNotEmpty()) {
            $warnings[] = 'Sebagian user sudah punya role lain pada aplikasi yang sama. Sistem tetap boleh menambah role baru jika dibutuhkan.';
        }

        if ($filters['include_inactive'] && $rows->where('action', 'skip_inactive_user')->isNotEmpty()) {
            $warnings[] = 'User tidak aktif ikut tampil di preview, tetapi tidak diberi akses.';
        }

        return $warnings;
    }

    protected function emptyResult(array $filters, array $blockers): array
    {
        return [
            'filters' => $filters,
            'blockers' => $blockers,
            'warnings' => [],
            'counts' => [
                'matched_users' => 0,
                'planned_insert' => 0,
                'planned_reactivate' => 0,
                'skipped_existing' => 0,
                'skipped_inactive_user' => 0,
                'skipped_inactive_access' => 0,
                'users_with_other_roles_same_app' => 0,
            ],
            'rows' => collect(),
            'samples' => [],
            'can_apply' => false,
        ];
    }

    protected function logAccessChange(UserAppAccess $access, string $action, array $filters, ?User $operator): void
    {
        UserActivityLog::query()->create([
            'user_id' => $access->user_id,
            'action' => $action,
            'meta' => [
                'operator_id' => $operator?->id,
                'app_code' => $access->app_code,
                'role_slug' => $access->role_slug,
                'target_scope' => $filters['target_scope'],
                'target_value' => $filters['target_value'],
            ],
        ]);
    }
}
