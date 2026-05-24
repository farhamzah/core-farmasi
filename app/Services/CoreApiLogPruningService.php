<?php

namespace App\Services;

use App\Models\CoreApiRequestLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CoreApiLogPruningService
{
    public function summarize(array $options = []): array
    {
        return $this->prune([...$options, 'dry_run' => true]);
    }

    public function prune(array $options = []): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? true);
        $retentionDays = (int) ($options['retention_days'] ?? config('core_api.audit_logs.retention_days', 90));
        $failedRetentionDays = (int) ($options['keep_failed_requests_days'] ?? config('core_api.audit_logs.keep_failed_requests_days', 180));
        $chunkSize = max(1, (int) ($options['chunk_size'] ?? config('core_api.audit_logs.prune_chunk_size', 1000)));
        $keepRecentMinimum = max(0, (int) ($options['keep_recent_minimum'] ?? config('core_api.audit_logs.keep_recent_minimum', 1000)));
        $includeFailed = (bool) ($options['include_failed'] ?? false);
        $now = CarbonImmutable::parse($options['now'] ?? now());

        $summary = [
            'retention_days' => $retentionDays,
            'keep_failed_requests_days' => $failedRetentionDays,
            'cutoff_date' => null,
            'failed_cutoff_date' => null,
            'total_logs' => CoreApiRequestLog::query()->count(),
            'keep_recent_minimum' => $keepRecentMinimum,
            'total_eligible' => 0,
            'deleted_count' => 0,
            'failed_count' => 0,
            'dry_run' => $dryRun,
            'include_failed' => $includeFailed,
            'prune_enabled' => (bool) config('core_api.audit_logs.prune_enabled', true),
            'error' => null,
        ];

        if (! $summary['prune_enabled']) {
            $summary['error'] = 'API request log pruning is disabled.';

            return $summary;
        }

        if ($retentionDays <= 0) {
            $summary['error'] = 'Retention days must be greater than zero.';

            return $summary;
        }

        if (! $includeFailed && $failedRetentionDays <= 0) {
            $summary['error'] = 'Failed request retention days must be greater than zero.';

            return $summary;
        }

        $cutoff = $now->subDays($retentionDays);
        $failedCutoff = $includeFailed ? $cutoff : $now->subDays($failedRetentionDays);
        $summary['cutoff_date'] = $cutoff->toDateTimeString();
        $summary['failed_cutoff_date'] = $failedCutoff->toDateTimeString();

        $eligibleCount = $this->eligibleQuery($cutoff, $failedCutoff, $includeFailed)->count();
        $maxDeletable = max(0, $summary['total_logs'] - $keepRecentMinimum);
        $summary['total_eligible'] = min($eligibleCount, $maxDeletable);

        if ($dryRun || $summary['total_eligible'] === 0) {
            return $summary;
        }

        $remaining = $summary['total_eligible'];

        while ($remaining > 0) {
            $ids = $this->eligibleQuery($cutoff, $failedCutoff, $includeFailed)
                ->orderBy('created_at')
                ->orderBy('id')
                ->limit(min($chunkSize, $remaining))
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            try {
                $deleted = DB::transaction(fn (): int => CoreApiRequestLog::query()
                    ->whereIn('id', $ids->all())
                    ->delete());

                $summary['deleted_count'] += $deleted;
                $remaining -= $deleted;

                if ($deleted === 0) {
                    break;
                }
            } catch (\Throwable) {
                $summary['failed_count'] += $ids->count();
                break;
            }
        }

        return $summary;
    }

    private function eligibleQuery(CarbonImmutable $cutoff, CarbonImmutable $failedCutoff, bool $includeFailed)
    {
        return CoreApiRequestLog::query()
            ->where(function ($query) use ($cutoff, $failedCutoff, $includeFailed): void {
                $query->where(function ($successQuery) use ($cutoff): void {
                    $successQuery
                        ->where('is_success', true)
                        ->where('created_at', '<', $cutoff);
                });

                $query->orWhere(function ($failedQuery) use ($cutoff, $failedCutoff, $includeFailed): void {
                    $failedQuery
                        ->where('is_success', false)
                        ->where('created_at', '<', $includeFailed ? $cutoff : $failedCutoff);
                });
            });
    }
}
