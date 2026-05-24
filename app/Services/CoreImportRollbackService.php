<?php

namespace App\Services;

use App\Models\CoreImportBatch;
use App\Models\CoreImportRecord;
use App\Models\Employee;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Models\UserAppAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Throwable;

class CoreImportRollbackService
{
    protected const SUPPORTED_TYPES = ['students', 'lecturers', 'employees', 'users', 'user_role_assignments', 'user_app_accesses'];

    public function rollback(CoreImportBatch $batch, ?User $actor = null): array
    {
        if (! in_array($batch->source, self::SUPPORTED_TYPES, true)) {
            return $this->finalizeBatch($batch, $actor, [
                'total_records' => 0,
                'rolled_back_count' => 0,
                'skipped_count' => 0,
                'failed_count' => 0,
                'manual_review_count' => 0,
                'already_rolled_back_count' => 0,
            ], 'not_supported');
        }

        if (! in_array($batch->status, ['executed', 'partially_failed'], true)) {
            return $this->finalizeBatch($batch, $actor, [
                'total_records' => 0,
                'rolled_back_count' => 0,
                'skipped_count' => 0,
                'failed_count' => 0,
                'manual_review_count' => 0,
                'already_rolled_back_count' => 0,
            ], 'not_supported');
        }

        $records = $batch->records()->orderByRaw('CAST(source_id AS INTEGER)')->get();
        $summary = [
            'total_records' => $records->count(),
            'rolled_back_count' => 0,
            'skipped_count' => 0,
            'failed_count' => 0,
            'manual_review_count' => 0,
            'already_rolled_back_count' => 0,
        ];

        $this->logActivity($actor, 'import.rollback_started', [
            'import_batch_id' => $batch->id,
            'import_type' => $batch->source,
        ]);

        foreach ($records as $record) {
            $this->rollbackRecordSafely($record, $actor, $summary);
        }

        $status = $summary['failed_count'] > 0
            ? 'partial_failed'
            : ($summary['manual_review_count'] > 0 ? 'manual_review' : 'rolled_back');

        return $this->finalizeBatch($batch->fresh(), $actor, $summary, $status);
    }

    protected function rollbackRecordSafely(CoreImportRecord $record, ?User $actor, array &$summary): void
    {
        if ($record->rollback_status === 'rolled_back') {
            $record->forceFill(['rollback_status' => 'already_rolled_back'])->save();
            $summary['already_rolled_back_count']++;

            return;
        }

        if (in_array($record->execution_status, ['skipped', 'ignored_invalid'], true)) {
            $record->forceFill([
                'rollback_status' => 'skipped',
                'rollback_note' => 'Record tidak dieksekusi saat import.',
                'rolled_back_by' => $actor?->id,
                'rolled_back_at' => now(),
            ])->save();
            $summary['skipped_count']++;

            return;
        }

        if ($record->execution_status !== 'executed') {
            $record->forceFill([
                'rollback_status' => 'manual_review',
                'rollback_note' => 'Record belum memiliki execution_status executed.',
                'rolled_back_by' => $actor?->id,
                'rolled_back_at' => now(),
            ])->save();
            $summary['manual_review_count']++;

            return;
        }

        try {
            DB::transaction(function () use ($record, $actor, &$summary) {
                if ($record->target_table === 'user_roles' && $record->executed_action === 'assign') {
                    $result = $this->rollbackUserRoleAssignment($record);
                } elseif ($record->target_table === 'user_app_accesses') {
                    $result = $this->rollbackUserAppAccess($record);
                } elseif ($record->executed_action === 'create_new') {
                    $result = $this->rollbackCreateNew($record);
                } elseif ($record->executed_action === 'update_existing') {
                    $result = $this->rollbackUpdateExisting($record);
                } else {
                    $result = [
                        'status' => 'manual_review',
                        'note' => 'executed_action tidak didukung untuk rollback otomatis.',
                    ];
                }

                $record->forceFill([
                    'rollback_status' => $result['status'],
                    'rollback_note' => $result['note'] ?? null,
                    'rollback_result' => $result,
                    'rolled_back_by' => $actor?->id,
                    'rolled_back_at' => now(),
                ])->save();

                match ($result['status']) {
                    'rolled_back' => $summary['rolled_back_count']++,
                    'manual_review' => $summary['manual_review_count']++,
                    'skipped' => $summary['skipped_count']++,
                    default => $summary['failed_count']++,
                };

                $this->logActivity($actor, 'import.rollback_row_result', [
                    'import_batch_id' => $record->core_import_batch_id,
                    'import_record_id' => $record->id,
                    'target_type' => $record->target_type,
                    'target_id' => $record->target_id,
                    'rollback_status' => $result['status'],
                ]);
            });
        } catch (Throwable $exception) {
            $record->forceFill([
                'rollback_status' => 'failed',
                'rollback_note' => $exception->getMessage(),
                'rolled_back_by' => $actor?->id,
                'rolled_back_at' => now(),
            ])->save();

            $summary['failed_count']++;
        }
    }

    protected function rollbackCreateNew(CoreImportRecord $record): array
    {
        $target = $this->targetModel($record);

        if (! $target) {
            return ['status' => 'manual_review', 'note' => 'Target record tidak ditemukan.'];
        }

        if (! $this->usesSoftDeletes($target)) {
            return ['status' => 'manual_review', 'note' => 'Target model tidak mendukung soft delete.'];
        }

        if ($target instanceof User && ! $this->isUserSafeToRollback($target, $record)) {
            return ['status' => 'manual_review', 'note' => 'User masih dipakai data lain atau akses aplikasi.'];
        }

        $target->delete();
        $userResult = $this->rollbackCreatedUser($record);

        return [
            'status' => $userResult['status'] === 'failed' ? 'manual_review' : 'rolled_back',
            'note' => $userResult['note'] ?? 'Created target soft deleted.',
            'target_soft_deleted' => true,
            'user' => $userResult,
        ];
    }

    protected function rollbackUpdateExisting(CoreImportRecord $record): array
    {
        $target = $this->targetModel($record);

        if (! $target) {
            return ['status' => 'manual_review', 'note' => 'Target record tidak ditemukan.'];
        }

        if (blank($record->previous_snapshot)) {
            return ['status' => 'manual_review', 'note' => 'previous_snapshot tidak tersedia.'];
        }

        $target->forceFill($record->previous_snapshot)->save();

        return [
            'status' => 'rolled_back',
            'note' => 'Previous snapshot restored.',
            'restored_fields' => array_keys($record->previous_snapshot),
        ];
    }

    protected function rollbackUserRoleAssignment(CoreImportRecord $record): array
    {
        $snapshot = $record->previous_snapshot ?? [];
        $userId = $snapshot['user_id'] ?? null;
        $roleId = $snapshot['role_id'] ?? $record->target_id;

        if (! $userId || ! $roleId) {
            return ['status' => 'manual_review', 'note' => 'Metadata user role assignment tidak lengkap.'];
        }

        if (($snapshot['assignment_existed'] ?? true) === true) {
            return ['status' => 'skipped', 'note' => 'Role assignment sudah ada sebelum import sehingga tidak dihapus.'];
        }

        $user = User::find($userId);

        if (! $user) {
            return ['status' => 'manual_review', 'note' => 'User untuk rollback role assignment tidak ditemukan.'];
        }

        $user->roles()->detach($roleId);

        return [
            'status' => 'rolled_back',
            'note' => 'Role assignment import-created removed.',
            'user_id' => $userId,
            'role_id' => $roleId,
        ];
    }

    protected function rollbackUserAppAccess(CoreImportRecord $record): array
    {
        $access = UserAppAccess::find($record->target_id);

        if (! $access) {
            return ['status' => 'manual_review', 'note' => 'UserAppAccess target tidak ditemukan.'];
        }

        $snapshot = $record->previous_snapshot ?? [];

        if (($snapshot['created_by_import'] ?? false) === true) {
            $access->forceFill([
                'is_active' => false,
                'deactivated_at' => now(),
            ])->save();

            return [
                'status' => 'rolled_back',
                'note' => 'Import-created app access deactivated.',
                'target_id' => $access->id,
            ];
        }

        if ($snapshot === []) {
            return ['status' => 'manual_review', 'note' => 'previous_snapshot tidak tersedia untuk app access.'];
        }

        $access->forceFill($snapshot)->save();

        return [
            'status' => 'rolled_back',
            'note' => 'App access previous snapshot restored.',
            'restored_fields' => array_keys($snapshot),
        ];
    }

    protected function rollbackCreatedUser(CoreImportRecord $record): array
    {
        if (! $record->created_user_id) {
            return ['status' => 'skipped', 'note' => 'Tidak ada user baru dari row ini.'];
        }

        $user = User::withTrashed()->find($record->created_user_id);

        if (! $user) {
            return ['status' => 'manual_review', 'note' => 'User import-created tidak ditemukan.'];
        }

        if ($user->trashed()) {
            return ['status' => 'already_rolled_back', 'note' => 'User sudah soft deleted.'];
        }

        if (! $this->isUserSafeToRollback($user, $record)) {
            return ['status' => 'failed', 'note' => 'User masih dipakai data lain atau akses aplikasi.'];
        }

        $user->delete();

        return ['status' => 'rolled_back', 'note' => 'Import-created user soft deleted.'];
    }

    protected function isUserSafeToRollback(User $user, CoreImportRecord $record): bool
    {
        if ($user->roles()->exists() || $user->appAccesses()->exists()) {
            return false;
        }

        foreach ([Student::class, Lecturer::class, Employee::class] as $modelClass) {
            $query = in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)
                ? $modelClass::withTrashed()
                : $modelClass::query();
            $query->where('user_id', $user->id);

            if ($record->target_type === $modelClass) {
                $query->whereKeyNot($record->target_id);
            }

            if ($query->exists()) {
                return false;
            }
        }

        return true;
    }

    protected function targetModel(CoreImportRecord $record): ?Model
    {
        $modelClass = $record->target_type ?: match ($record->target_table) {
            'users' => User::class,
            'students' => Student::class,
            'lecturers' => Lecturer::class,
            'employees' => Employee::class,
            'user_app_accesses' => UserAppAccess::class,
            'user_roles' => Role::class,
            default => null,
        };

        if (! $modelClass || ! class_exists($modelClass)) {
            return null;
        }

        $query = in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)
            ? $modelClass::withTrashed()
            : $modelClass::query();

        return $query->find($record->target_id);
    }

    protected function usesSoftDeletes(Model $model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model::class), true);
    }

    protected function finalizeBatch(CoreImportBatch $batch, ?User $actor, array $summary, string $status): array
    {
        $batchSummary = $batch->summary ?? [];
        $batchSummary['rollback'] = $summary;

        $batch->forceFill([
            'rollback_status' => $status,
            'rolled_back_rows_count' => $summary['rolled_back_count'],
            'rollback_failed_rows_count' => $summary['failed_count'],
            'rollback_skipped_rows_count' => $summary['skipped_count'],
            'rolled_back_by' => $actor?->id,
            'rolled_back_at' => now(),
            'summary' => $batchSummary,
        ])->save();

        $this->logActivity($actor, 'import.rollback_completed', [
            'import_batch_id' => $batch->id,
            'rollback_status' => $status,
            'summary' => $summary,
        ]);

        return $summary + ['status' => $status];
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
