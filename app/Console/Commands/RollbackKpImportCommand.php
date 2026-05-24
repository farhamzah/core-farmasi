<?php

namespace App\Console\Commands;

use App\Models\CoreImportBatch;
use Illuminate\Console\Command;

class RollbackKpImportCommand extends Command
{
    protected $signature = 'core:rollback-kp-import
        {batch_id : Core import batch ID}
        {--dry-run : Preview rollback targets without writing}
        {--confirm-rollback : Explicitly confirm rollback intent}';

    protected $description = 'Preview rollback readiness for a KP import batch';

    public function handle(): int
    {
        if (! $this->option('confirm-rollback')) {
            $this->error('Rollback refused: missing --confirm-rollback.');

            return self::FAILURE;
        }

        $batch = CoreImportBatch::withCount('records')->find($this->argument('batch_id'));

        if (! $batch) {
            $this->error('Rollback refused: import batch not found.');

            return self::FAILURE;
        }

        $this->info('Rollback dry-run summary');
        $this->line("Batch: {$batch->id}");
        $this->line("Source: {$batch->source}");
        $this->line("Status: {$batch->status}");
        $this->line("Records: {$batch->records_count}");
        $this->line('D3A does not perform destructive rollback. Review batch records before D3B/D4 rollback implementation.');

        return self::SUCCESS;
    }
}
