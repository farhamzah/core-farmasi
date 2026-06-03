<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Lecturer;
use App\Models\Student;
use App\Services\CoreProfileUserProvisioningService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ProvisionMasterProfileUsersCommand extends Command
{
    protected $signature = 'core:provision-master-users
        {--apply : Write user links/created users}
        {--only= : students, lecturers, employees}
        {--identifier= : Restrict to NIM, lecturer number, or employee number}
        {--show-passwords : Show generated temporary passwords in command output}';

    protected $description = 'Dry-run or apply auto user provisioning for master student, lecturer, and employee profiles.';

    public function handle(CoreProfileUserProvisioningService $provisioning): int
    {
        $apply = (bool) $this->option('apply');
        $showPasswords = (bool) $this->option('show-passwords');
        $only = $this->only();
        $identifier = $this->option('identifier');

        $this->info('Core Master Profile User Provisioning');
        $this->line('Mode: '.($apply ? 'apply' : 'dry-run'));

        $rows = [];
        $summary = [
            'create' => 0,
            'link' => 0,
            'skip' => 0,
            'blocker' => 0,
            'created' => 0,
            'linked' => 0,
        ];

        foreach ($this->profiles($only, $identifier) as $type => $profiles) {
            foreach ($profiles as $profile) {
                $preview = $provisioning->previewFor($profile);
                $context = $preview['context'] ?? [];
                $action = $preview['action'];
                $summary[$action] = ($summary[$action] ?? 0) + 1;
                $result = $action;

                if ($apply && in_array($action, ['create', 'link'], true)) {
                    $user = $provisioning->provisionFor($profile);
                    $result = $user
                        ? ($action === 'create' ? 'created' : 'linked')
                        : 'not_changed';

                    if (isset($summary[$result])) {
                        $summary[$result]++;
                    }
                }

                $rows[] = [
                    'type' => $type,
                    'profile_id' => $profile->getKey(),
                    'identifier' => $context['identifier'] ?? '-',
                    'name' => $context['name'] ?? '-',
                    'email' => $context['email'] ?? '-',
                    'action' => $action,
                    'result' => $result,
                    'password' => $showPasswords ? ($preview['initial_password'] ?? '-') : '[hidden]',
                ];
            }
        }

        $this->table(
            ['Type', 'Profile ID', 'Identifier', 'Name', 'Email', 'Action', 'Result', 'Initial Password'],
            $rows,
        );

        $this->line('Summary: '.json_encode($summary));

        return $summary['blocker'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function profiles(array $only, mixed $identifier): array
    {
        return [
            'students' => in_array('students', $only, true) ? $this->query(Student::query(), 'student_number', $identifier)->get() : collect(),
            'lecturers' => in_array('lecturers', $only, true) ? $this->query(Lecturer::query(), 'lecturer_number', $identifier)->get() : collect(),
            'employees' => in_array('employees', $only, true) ? $this->query(Employee::query(), 'employee_number', $identifier)->get() : collect(),
        ];
    }

    protected function query(Builder $query, string $identifierColumn, mixed $identifier): Builder
    {
        return $query
            ->whereNull('user_id')
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
