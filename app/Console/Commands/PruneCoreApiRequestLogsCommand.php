<?php

namespace App\Console\Commands;

use App\Services\CoreApiLogPruningService;
use Illuminate\Console\Command;

class PruneCoreApiRequestLogsCommand extends Command
{
    protected $signature = 'core:prune-api-request-logs
        {--dry-run : Preview eligible API request logs without deleting}
        {--force : Delete eligible API request logs}
        {--days= : Override successful request retention days}
        {--chunk= : Override delete chunk size}
        {--include-failed : Prune failed requests using the normal retention window}';

    protected $description = 'Dry-run or prune old Core internal API request logs safely';

    public function handle(CoreApiLogPruningService $pruningService): int
    {
        $dryRun = ! $this->option('force') || $this->option('dry-run') || (bool) config('core_api.audit_logs.dry_run_default', true);

        if ($this->option('force')) {
            $dryRun = false;
        }

        $summary = $pruningService->prune([
            'dry_run' => $dryRun,
            'retention_days' => $this->option('days') !== null ? (int) $this->option('days') : null,
            'chunk_size' => $this->option('chunk') !== null ? (int) $this->option('chunk') : null,
            'include_failed' => (bool) $this->option('include-failed'),
        ]);

        $this->line('Core API request log pruning summary');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Mode', $summary['dry_run'] ? 'dry-run' : 'force'],
                ['Retention days', $summary['retention_days']],
                ['Failed retention days', $summary['keep_failed_requests_days']],
                ['Cutoff date', $summary['cutoff_date'] ?? '-'],
                ['Failed cutoff date', $summary['failed_cutoff_date'] ?? '-'],
                ['Total logs', $summary['total_logs']],
                ['Keep recent minimum', $summary['keep_recent_minimum']],
                ['Eligible logs', $summary['total_eligible']],
                ['Deleted logs', $summary['deleted_count']],
                ['Failed deletes', $summary['failed_count']],
            ],
        );

        if ($summary['error'] !== null) {
            $this->error($summary['error']);

            return self::FAILURE;
        }

        if ($summary['dry_run']) {
            $this->info('Dry-run only. Re-run with --force to delete eligible old logs.');
        } else {
            $this->info('Pruning completed.');
        }

        return self::SUCCESS;
    }
}
