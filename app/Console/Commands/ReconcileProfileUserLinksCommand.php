<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Lecturer;
use App\Models\Student;
use App\Services\CoreProfileLinkReconciliationService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ReconcileProfileUserLinksCommand extends Command
{
    protected $signature = 'core:reconcile-profile-user-links
        {--apply : Apply safe link/update actions}
        {--only= : students, lecturers, employees}
        {--email= : Restrict by profile email}
        {--identifier= : Restrict by NIM, lecturer number, or employee number}
        {--backfill-identifiers : Backfill lecturer NIDN/NIP/NUPTK from unambiguous lecturer_number}';

    protected $description = 'Dry-run or apply safe reconciliation between master profiles and canonical Core users.';

    public function handle(CoreProfileLinkReconciliationService $reconciliation): int
    {
        $apply = (bool) $this->option('apply');
        $backfillIdentifiers = (bool) $this->option('backfill-identifiers');
        $only = $this->only();
        $email = $this->option('email');
        $identifier = $this->option('identifier');

        $this->info('Core Profile User Link Reconciliation');
        $this->line('Mode: '.($apply ? 'apply' : 'dry-run'));

        $rows = [];
        $summary = [
            'relink' => 0,
            'link' => 0,
            'update' => 0,
            'create' => 0,
            'skip' => 0,
            'blocker' => 0,
            'applied' => 0,
        ];

        foreach ($this->profiles($only, $email, $identifier) as $type => $profiles) {
            foreach ($profiles as $profile) {
                $result = $apply
                    ? $reconciliation->apply($profile, $backfillIdentifiers)
                    : $reconciliation->preview($profile, $backfillIdentifiers);

                $summary[$result['action']] = ($summary[$result['action']] ?? 0) + 1;
                $summary['applied'] += ($result['applied'] ?? false) ? 1 : 0;

                $rows[] = [
                    'type' => $type,
                    'profile_id' => $result['profile_id'],
                    'identifier' => $result['identifier'] ?? '-',
                    'email' => $result['email'] ?? '-',
                    'current_user_id' => $result['current_user_id'] ?? '-',
                    'target_user_id' => $result['target_user_id'] ?? '-',
                    'action' => $result['action'],
                    'reason' => $result['reason'],
                    'updates' => implode(',', array_keys($result['updates'] ?? [])) ?: '-',
                ];
            }
        }

        $this->table(
            ['Type', 'Profile ID', 'Identifier', 'Email', 'Current User', 'Target User', 'Action', 'Reason', 'Updates'],
            $rows,
        );

        $this->line('Summary: '.json_encode($summary));

        if (! $apply) {
            $this->line('Dry-run only. Re-run with --apply to write safe changes.');
        }

        return $summary['blocker'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function profiles(array $only, mixed $email, mixed $identifier): array
    {
        return [
            'students' => in_array('students', $only, true) ? $this->query(Student::query(), 'student_number', $email, $identifier)->get() : collect(),
            'lecturers' => in_array('lecturers', $only, true) ? $this->query(Lecturer::query(), 'lecturer_number', $email, $identifier)->get() : collect(),
            'employees' => in_array('employees', $only, true) ? $this->query(Employee::query(), 'employee_number', $email, $identifier)->get() : collect(),
        ];
    }

    protected function query(Builder $query, string $identifierColumn, mixed $email, mixed $identifier): Builder
    {
        return $query
            ->when(filled($email), fn (Builder $query): Builder => $query->where('email', $email))
            ->when(filled($identifier), fn (Builder $query): Builder => $query->where($identifierColumn, $identifier))
            ->orderBy($identifierColumn);
    }

    protected function only(): array
    {
        $only = collect(explode(',', (string) ($this->option('only') ?: 'students,lecturers,employees')))
            ->map(fn (string $type): string => trim($type))
            ->filter()
            ->values()
            ->all();

        return array_values(array_intersect($only, ['students', 'lecturers', 'employees']));
    }
}
