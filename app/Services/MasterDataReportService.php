<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use App\Models\ExternalPerson;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\StudyProgram;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class MasterDataReportService
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function reportTypes(): array
    {
        return [
            'students' => [
                'label' => 'Laporan Mahasiswa',
                'resource_label' => 'Mahasiswa',
                'route_key' => 'students',
                'columns' => ['NIM', 'Kelas', 'Nama', 'Email', 'Program Studi', 'Telepon', 'Status', 'Aktif'],
                'query' => fn (): Builder => Student::query()->with(['studyProgram:id,name', 'user:id,email']),
                'filters' => [
                    'search' => ['type' => 'text', 'label' => 'Cari data'],
                    'nim_prefix' => ['type' => 'text', 'label' => '2 digit awal NIM', 'maxlength' => 2],
                    'student_class' => ['type' => 'select', 'label' => 'Kelas', 'options' => $this->studentClassOptions()],
                    'study_program_id' => ['type' => 'select', 'label' => 'Program studi', 'options' => $this->studyProgramOptions()],
                    'status' => ['type' => 'select', 'label' => 'Status', 'options' => $this->studentStatusOptions()],
                    'active' => ['type' => 'select', 'label' => 'Aktif', 'options' => $this->booleanOptions()],
                ],
                'apply_filters' => function (Builder $query, array $filters): Builder {
                    return $query
                        ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                            $term = trim((string) $filters['search']);
                            $query->where(function (Builder $inner) use ($term): void {
                                $inner->where('student_number', 'like', "%{$term}%")
                                    ->orWhere('name', 'like', "%{$term}%")
                                    ->orWhere('email', 'like', "%{$term}%")
                                    ->orWhere('phone', 'like', "%{$term}%");
                            });
                        })
                        ->when(filled($filters['nim_prefix'] ?? null), fn (Builder $query) => $query->where('student_number', 'like', trim((string) $filters['nim_prefix']) . '%'))
                        ->when(filled($filters['student_class'] ?? null), fn (Builder $query) => $query->where('student_class', $filters['student_class']))
                        ->when(filled($filters['study_program_id'] ?? null), fn (Builder $query) => $query->where('study_program_id', $filters['study_program_id']))
                        ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
                        ->when($this->hasBoolean($filters, 'active'), fn (Builder $query) => $query->where('active', $this->toBoolean($filters['active'])));
                },
                'map_row' => fn (Student $student): array => [
                    $student->student_number,
                    $student->student_class ?: '-',
                    $student->name,
                    $student->email,
                    $student->studyProgram?->name ?: '-',
                    $student->phone ?: '-',
                    $student->status ?: '-',
                    $student->active ? 'Ya' : 'Tidak',
                ],
            ],
            'lecturers' => [
                'label' => 'Laporan Dosen',
                'resource_label' => 'Dosen',
                'route_key' => 'lecturers',
                'columns' => ['No. Dosen', 'Nama', 'Email', 'NIDN', 'NIP', 'NUPTK', 'Departemen', 'Program Studi', 'Aktif'],
                'query' => fn (): Builder => Lecturer::query()->with(['department:id,name', 'studyProgram:id,name', 'user:id,email']),
                'filters' => [
                    'search' => ['type' => 'text', 'label' => 'Cari data'],
                    'lecturer_prefix' => ['type' => 'text', 'label' => 'Prefix nomor dosen'],
                    'nidn_prefix' => ['type' => 'text', 'label' => 'Prefix NIDN'],
                    'department_id' => ['type' => 'select', 'label' => 'Departemen', 'options' => $this->departmentOptions()],
                    'study_program_id' => ['type' => 'select', 'label' => 'Program studi', 'options' => $this->studyProgramOptions()],
                    'active' => ['type' => 'select', 'label' => 'Aktif', 'options' => $this->booleanOptions()],
                ],
                'apply_filters' => function (Builder $query, array $filters): Builder {
                    return $query
                        ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                            $term = trim((string) $filters['search']);
                            $query->where(function (Builder $inner) use ($term): void {
                                $inner->where('lecturer_number', 'like', "%{$term}%")
                                    ->orWhere('name', 'like', "%{$term}%")
                                    ->orWhere('email', 'like', "%{$term}%")
                                    ->orWhere('nidn', 'like', "%{$term}%")
                                    ->orWhere('nip', 'like', "%{$term}%")
                                    ->orWhere('nuptk', 'like', "%{$term}%");
                            });
                        })
                        ->when(filled($filters['lecturer_prefix'] ?? null), fn (Builder $query) => $query->where('lecturer_number', 'like', trim((string) $filters['lecturer_prefix']) . '%'))
                        ->when(filled($filters['nidn_prefix'] ?? null), fn (Builder $query) => $query->where('nidn', 'like', trim((string) $filters['nidn_prefix']) . '%'))
                        ->when(filled($filters['department_id'] ?? null), fn (Builder $query) => $query->where('department_id', $filters['department_id']))
                        ->when(filled($filters['study_program_id'] ?? null), fn (Builder $query) => $query->where('study_program_id', $filters['study_program_id']))
                        ->when($this->hasBoolean($filters, 'active'), fn (Builder $query) => $query->where('active', $this->toBoolean($filters['active'])));
                },
                'map_row' => fn (Lecturer $lecturer): array => [
                    $lecturer->lecturer_number,
                    $lecturer->display_name_with_title,
                    $lecturer->email,
                    $lecturer->nidn ?: '-',
                    $lecturer->nip ?: '-',
                    $lecturer->nuptk ?: '-',
                    $lecturer->department?->name ?: '-',
                    $lecturer->studyProgram?->name ?: '-',
                    $lecturer->active ? 'Ya' : 'Tidak',
                ],
            ],
            'employees' => [
                'label' => 'Laporan Tendik / Staff',
                'resource_label' => 'Tendik / Staff',
                'route_key' => 'employees',
                'columns' => ['No. Pegawai', 'Nama', 'Jenis Staff', 'Departemen', 'Program Studi', 'Posisi', 'Email', 'Telepon', 'Status'],
                'query' => fn (): Builder => Employee::query()->with(['department:id,name', 'studyProgram:id,name', 'user:id,email']),
                'filters' => [
                    'search' => ['type' => 'text', 'label' => 'Cari data'],
                    'employee_prefix' => ['type' => 'text', 'label' => 'Prefix no. pegawai'],
                    'staff_type' => ['type' => 'select', 'label' => 'Jenis staff', 'options' => $this->employeeStaffTypeOptions()],
                    'department_id' => ['type' => 'select', 'label' => 'Departemen', 'options' => $this->departmentOptions()],
                    'study_program_id' => ['type' => 'select', 'label' => 'Program studi', 'options' => $this->studyProgramOptions()],
                    'status' => ['type' => 'select', 'label' => 'Status', 'options' => $this->employeeStatusOptions()],
                ],
                'apply_filters' => function (Builder $query, array $filters): Builder {
                    return $query
                        ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                            $term = trim((string) $filters['search']);
                            $query->where(function (Builder $inner) use ($term): void {
                                $inner->where('employee_number', 'like', "%{$term}%")
                                    ->orWhere('name', 'like', "%{$term}%")
                                    ->orWhere('email', 'like', "%{$term}%")
                                    ->orWhere('position_title', 'like', "%{$term}%");
                            });
                        })
                        ->when(filled($filters['employee_prefix'] ?? null), fn (Builder $query) => $query->where('employee_number', 'like', trim((string) $filters['employee_prefix']) . '%'))
                        ->when(filled($filters['staff_type'] ?? null), fn (Builder $query) => $query->where('staff_type', $filters['staff_type']))
                        ->when(filled($filters['department_id'] ?? null), fn (Builder $query) => $query->where('department_id', $filters['department_id']))
                        ->when(filled($filters['study_program_id'] ?? null), fn (Builder $query) => $query->where('study_program_id', $filters['study_program_id']))
                        ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']));
                },
                'map_row' => fn (Employee $employee): array => [
                    $employee->employee_number,
                    $employee->name,
                    $employee->staff_type ?: '-',
                    $employee->department?->name ?: '-',
                    $employee->studyProgram?->name ?: '-',
                    $employee->position_title ?: '-',
                    $employee->email,
                    $employee->phone ?: '-',
                    $employee->status ?: '-',
                ],
            ],
            'external-people' => [
                'label' => 'Laporan Mitra Eksternal',
                'resource_label' => 'Mitra Eksternal',
                'route_key' => 'external-people',
                'columns' => ['Nama', 'Instansi', 'Tipe Instansi', 'Posisi', 'Email', 'Telepon', 'Status'],
                'query' => fn (): Builder => ExternalPerson::query()->with('user:id,email'),
                'filters' => [
                    'search' => ['type' => 'text', 'label' => 'Cari data'],
                    'institution_type' => ['type' => 'select', 'label' => 'Tipe instansi', 'options' => $this->externalInstitutionTypeOptions()],
                    'status' => ['type' => 'select', 'label' => 'Status', 'options' => $this->externalStatusOptions()],
                ],
                'apply_filters' => function (Builder $query, array $filters): Builder {
                    return $query
                        ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                            $term = trim((string) $filters['search']);
                            $query->where(function (Builder $inner) use ($term): void {
                                $inner->where('name', 'like', "%{$term}%")
                                    ->orWhere('email', 'like', "%{$term}%")
                                    ->orWhere('institution_name', 'like', "%{$term}%")
                                    ->orWhere('position_title', 'like', "%{$term}%");
                            });
                        })
                        ->when(filled($filters['institution_type'] ?? null), fn (Builder $query) => $query->where('institution_type', $filters['institution_type']))
                        ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']));
                },
                'map_row' => fn (ExternalPerson $person): array => [
                    $person->display_name_with_title,
                    $person->institution_name ?: '-',
                    $person->institution_type ?: '-',
                    $person->position_title ?: '-',
                    $person->email,
                    $person->phone ?: '-',
                    $person->status ?: '-',
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $rawFilters
     * @return array{
     *   config: array<string, mixed>,
     *   rows: array<int, array<int, string>>,
     *   columns: array<int, string>,
     *   filters: array<string, mixed>,
     *   active_filters: array<string, string>,
     *   total: int
     * }
     */
    public function build(string $type, array $rawFilters): array
    {
        $config = $this->reportTypes()[$type] ?? null;

        abort_if($config === null, 404);

        $filters = $this->sanitizeFilters($config, $rawFilters);
        $query = $config['query']();
        $query = $config['apply_filters']($query, $filters);

        $records = $query
            ->orderByDesc('created_at')
            ->get();

        $rows = $records
            ->map(fn ($record): array => array_map(
                fn ($value): string => $this->stringify($value),
                $config['map_row']($record),
            ))
            ->all();

        return [
            'config' => $config,
            'rows' => $rows,
            'columns' => $config['columns'],
            'filters' => $filters,
            'active_filters' => $this->activeFilterLabels($config, $filters),
            'total' => $records->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $rawFilters
     * @return array<string, mixed>
     */
    protected function sanitizeFilters(array $config, array $rawFilters): array
    {
        $allowed = array_keys($config['filters']);
        $filters = Arr::only($rawFilters, $allowed);

        foreach ($filters as $key => $value) {
            if (is_string($value)) {
                $filters[$key] = trim($value);
            }
        }

        return $filters;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $filters
     * @return array<string, string>
     */
    protected function activeFilterLabels(array $config, array $filters): array
    {
        $labels = [];

        foreach ($config['filters'] as $key => $definition) {
            $value = $filters[$key] ?? null;

            if (! filled($value) && ! ($definition['type'] === 'select' && in_array($value, ['0', 0], true))) {
                continue;
            }

            $label = $definition['label'];

            if (($definition['type'] ?? null) === 'select') {
                $labels[$label] = $definition['options'][(string) $value] ?? (string) $value;
                continue;
            }

            $labels[$label] = (string) $value;
        }

        return $labels;
    }

    protected function hasBoolean(array $filters, string $key): bool
    {
        return array_key_exists($key, $filters) && in_array($filters[$key], ['0', '1', 0, 1], true);
    }

    protected function toBoolean(string|int $value): bool
    {
        return (string) $value === '1';
    }

    protected function stringify(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }

        if ($value === null || $value === '') {
            return '-';
        }

        return (string) $value;
    }

    /**
     * @return array<string, string>
     */
    protected function studentClassOptions(): array
    {
        return Student::query()
            ->whereNotNull('student_class')
            ->where('student_class', '!=', '')
            ->orderBy('student_class')
            ->distinct()
            ->pluck('student_class', 'student_class')
            ->mapWithKeys(fn ($value, $key): array => [(string) $key => (string) $value])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function studyProgramOptions(): array
    {
        return StudyProgram::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->mapWithKeys(fn ($value, $key): array => [(string) $key => (string) $value])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function departmentOptions(): array
    {
        return Department::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->mapWithKeys(fn ($value, $key): array => [(string) $key => (string) $value])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function studentStatusOptions(): array
    {
        return Student::query()
            ->whereNotNull('status')
            ->where('status', '!=', '')
            ->orderBy('status')
            ->distinct()
            ->pluck('status', 'status')
            ->mapWithKeys(fn ($value, $key): array => [(string) $key => (string) $value])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function employeeStatusOptions(): array
    {
        return Employee::query()
            ->whereNotNull('status')
            ->where('status', '!=', '')
            ->orderBy('status')
            ->distinct()
            ->pluck('status', 'status')
            ->mapWithKeys(fn ($value, $key): array => [(string) $key => (string) $value])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function employeeStaffTypeOptions(): array
    {
        return Employee::query()
            ->whereNotNull('staff_type')
            ->where('staff_type', '!=', '')
            ->orderBy('staff_type')
            ->distinct()
            ->pluck('staff_type', 'staff_type')
            ->mapWithKeys(fn ($value, $key): array => [(string) $key => (string) $value])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function externalInstitutionTypeOptions(): array
    {
        return ExternalPerson::query()
            ->whereNotNull('institution_type')
            ->where('institution_type', '!=', '')
            ->orderBy('institution_type')
            ->distinct()
            ->pluck('institution_type', 'institution_type')
            ->mapWithKeys(fn ($value, $key): array => [(string) $key => (string) $value])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function externalStatusOptions(): array
    {
        return ExternalPerson::query()
            ->whereNotNull('status')
            ->where('status', '!=', '')
            ->orderBy('status')
            ->distinct()
            ->pluck('status', 'status')
            ->mapWithKeys(fn ($value, $key): array => [(string) $key => (string) $value])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function booleanOptions(): array
    {
        return [
            '1' => 'Ya',
            '0' => 'Tidak',
        ];
    }
}
