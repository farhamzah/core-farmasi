<?php

namespace App\Console\Commands;

use App\Models\AccountRequest;
use App\Models\Employee;
use App\Models\ExternalPerson;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\User;
use App\Services\CorePersonNameFormatter;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class NormalizePersonNamesCommand extends Command
{
    protected $signature = 'core:normalize-person-names
        {--execute : Apply normalized names to the database}
        {--only= : Limit to users,students,lecturers,employees,external_people,account_requests}
        {--limit= : Limit rows per model}
        {--show-samples : Show sample changes}';

    protected $description = 'Normalize existing person name casing safely. Defaults to dry-run.';

    /**
     * @var array<string, class-string<Model>>
     */
    private array $models = [
        'users' => User::class,
        'students' => Student::class,
        'lecturers' => Lecturer::class,
        'employees' => Employee::class,
        'external_people' => ExternalPerson::class,
        'account_requests' => AccountRequest::class,
    ];

    public function handle(CorePersonNameFormatter $formatter): int
    {
        $execute = (bool) $this->option('execute');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $targets = $this->resolveTargets();

        if ($targets === []) {
            $this->error('Target tidak valid. Gunakan: '.implode(', ', array_keys($this->models)));

            return self::FAILURE;
        }

        $this->info($execute ? 'Mode execute: nama akan diperbarui.' : 'Mode dry-run: tidak ada data yang diubah.');

        $totalChanged = 0;
        $summary = [];

        foreach ($targets as $key => $modelClass) {
            $changed = 0;
            $scanned = 0;
            $samples = [];

            $query = $modelClass::query()->whereNotNull('name')->orderBy('id');

            if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass), true)) {
                $query->withTrashed();
            }

            if ($limit !== null && $limit > 0) {
                $query->limit($limit);
            }

            foreach ($query->get() as $model) {
                $scanned++;
                $current = (string) $model->getAttribute('name');
                $normalized = $formatter->normalizePersonName($current);

                if ($normalized === null || $normalized === $current) {
                    continue;
                }

                $changed++;

                if (count($samples) < 5) {
                    $samples[] = [
                        'id' => $model->getKey(),
                        'before' => $current,
                        'after' => $normalized,
                    ];
                }

                if ($execute) {
                    $model->forceFill(['name' => $normalized])->save();
                }
            }

            $totalChanged += $changed;
            $summary[] = [$key, $scanned, $changed];

            if ($this->option('show-samples') && $samples !== []) {
                $this->line('');
                $this->line("Sample {$key}:");
                $this->table(['ID', 'Sebelum', 'Sesudah'], array_map(
                    fn (array $sample): array => [$sample['id'], $sample['before'], $sample['after']],
                    $samples
                ));
            }
        }

        $this->line('');
        $this->table(['Target', 'Dicek', $execute ? 'Diubah' : 'Akan Diubah'], $summary);
        $this->info(($execute ? 'Total diubah: ' : 'Total akan diubah: ').$totalChanged);

        return self::SUCCESS;
    }

    /**
     * @return array<string, class-string<Model>>
     */
    private function resolveTargets(): array
    {
        $only = trim((string) $this->option('only'));

        if ($only === '') {
            return $this->models;
        }

        $requested = array_filter(array_map('trim', explode(',', $only)));

        return array_intersect_key($this->models, array_flip($requested));
    }
}
