<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CoreProfilePortalService
{
    /**
     * @return array<string, mixed>
     */
    public function summaryFor(User $user): array
    {
        $user->loadMissing([
            'student.studyProgram.department',
            'lecturer.department',
            'lecturer.studyProgram',
            'employee.department',
            'employee.studyProgram',
        ]);

        $profiles = collect([
            $this->studentSummary($user->student),
            $this->lecturerSummary($user->lecturer),
            $this->employeeSummary($user->employee),
        ])->filter()->values()->all();

        return [
            'user' => [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'identity_type' => $user->identity_type,
                'identity_number_masked' => $this->maskIdentifier($user->identity_number),
                'active' => $user->active,
            ],
            'profiles' => $profiles,
            'editable_fields' => $this->editableFieldsFor($user),
            'contact_values' => $this->contactValuesFor($user),
            'completion' => $this->completionFor($user, $profiles),
        ];
    }

    /**
     * @return array{updated: array<int, string>}
     */
    public function updateSafeContactFields(User $user, array $data, Request $request): array
    {
        $editableFields = $this->editableFieldsFor($user);
        $updated = [];

        foreach (['student', 'lecturer', 'employee'] as $relation) {
            $profile = $user->{$relation};

            if (! $profile) {
                continue;
            }

            $changes = [];

            foreach ($editableFields[$relation] ?? [] as $field) {
                if (! array_key_exists($field, $data)) {
                    continue;
                }

                $changes[$field] = $data[$field];
            }

            if ($changes === []) {
                continue;
            }

            $profile->fill($changes);

            if ($profile->isDirty()) {
                $profile->save();
                $updated = array_values(array_unique([...$updated, ...array_keys($changes)]));
            }
        }

        if ($updated !== []) {
            UserActivityLog::create([
                'user_id' => $user->id,
                'action' => 'profile.updated',
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'meta' => [
                    'profile_user_id' => $user->id,
                    'changed_fields' => $updated,
                ],
            ]);
        }

        return ['updated' => $updated];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function editableFieldsFor(User $user): array
    {
        return [
            'student' => $this->existingColumns(Student::class, ['phone', 'address', 'alternate_email']),
            'lecturer' => $this->existingColumns(Lecturer::class, ['phone', 'address', 'alternate_email']),
            'employee' => $this->existingColumns(Employee::class, ['phone', 'address', 'alternate_email']),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function contactValuesFor(User $user): array
    {
        return [
            'phone' => $user->student?->phone ?? $user->lecturer?->phone ?? $user->employee?->phone,
            'address' => $user->student?->address ?? $user->lecturer?->address ?? $user->employee?->address,
            'alternate_email' => $this->valueFromFirstAvailableColumn($user, 'alternate_email'),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $profiles
     * @return array<string, mixed>
     */
    private function completionFor(User $user, array $profiles): array
    {
        $contact = $this->contactValuesFor($user);
        $hasBirthDate = (bool) ($user->student?->birth_date ?? $user->lecturer?->birth_date ?? $user->employee?->birth_date);

        $items = [
            [
                'key' => 'linked_profile',
                'label' => 'Profil tertaut',
                'complete' => $profiles !== [],
                'sensitive' => false,
            ],
            [
                'key' => 'email',
                'label' => 'Email tersedia',
                'complete' => filled($user->email) || collect($profiles)->contains(fn (array $profile): bool => filled($profile['email'] ?? null)),
                'sensitive' => false,
            ],
            [
                'key' => 'phone',
                'label' => 'Telepon tersedia',
                'complete' => filled($contact['phone']),
                'sensitive' => false,
            ],
            [
                'key' => 'address',
                'label' => 'Alamat tersedia',
                'complete' => filled($contact['address']),
                'sensitive' => false,
            ],
            [
                'key' => 'birth_date',
                'label' => 'Tanggal lahir tercatat',
                'complete' => $hasBirthDate,
                'sensitive' => true,
            ],
        ];

        $completed = collect($items)->where('complete', true)->count();
        $total = count($items);

        return [
            'percentage' => (int) round(($completed / $total) * 100),
            'completed' => $completed,
            'total' => $total,
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function studentSummary(?Student $student): ?array
    {
        if (! $student) {
            return null;
        }

        return [
            'type' => 'student',
            'label' => 'Mahasiswa',
            'name' => $student->name,
            'identifier_label' => 'NIM',
            'identifier' => $student->student_number,
            'email' => $student->email,
            'status' => $student->status,
            'unit' => $student->studyProgram?->name,
            'unit_secondary' => $student->studyProgram?->department?->name,
            'phone' => $this->valueIfColumnExists($student, 'phone'),
            'address' => $this->valueIfColumnExists($student, 'address'),
            'birth_date_recorded' => filled($student->birth_date),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function lecturerSummary(?Lecturer $lecturer): ?array
    {
        if (! $lecturer) {
            return null;
        }

        return [
            'type' => 'lecturer',
            'label' => 'Dosen',
            'name' => $lecturer->name,
            'identifier_label' => 'NIDN/NIP',
            'identifier' => $lecturer->lecturer_number,
            'email' => $lecturer->email,
            'status' => $lecturer->active ? 'active' : 'inactive',
            'unit' => $lecturer->studyProgram?->name,
            'unit_secondary' => $lecturer->department?->name,
            'phone' => $lecturer->phone,
            'address' => $lecturer->address ?? null,
            'birth_date_recorded' => filled($lecturer->birth_date),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function employeeSummary(?Employee $employee): ?array
    {
        if (! $employee) {
            return null;
        }

        return [
            'type' => 'employee',
            'label' => 'Tendik / Staf / Laboran',
            'name' => $employee->name,
            'identifier_label' => 'Nomor Pegawai',
            'identifier' => $employee->employee_number,
            'email' => $employee->email,
            'status' => $employee->status,
            'unit' => $employee->studyProgram?->name,
            'unit_secondary' => $employee->department?->name,
            'position_title' => $employee->position_title,
            'staff_type' => $employee->staff_type,
            'phone' => $employee->phone,
            'address' => $employee->address,
            'birth_date_recorded' => filled($employee->birth_date),
        ];
    }

    /**
     * @param  class-string  $modelClass
     * @param  array<int, string>  $columns
     * @return array<int, string>
     */
    private function existingColumns(string $modelClass, array $columns): array
    {
        $model = new $modelClass();
        $table = $model->getTable();

        return array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn($table, $column),
        ));
    }

    private function valueIfColumnExists(object $model, string $column): ?string
    {
        if (! Schema::hasColumn($model->getTable(), $column)) {
            return null;
        }

        return $model->{$column};
    }

    private function valueFromFirstAvailableColumn(User $user, string $column): ?string
    {
        foreach (['student', 'lecturer', 'employee'] as $relation) {
            $profile = $user->{$relation};

            if ($profile && Schema::hasColumn($profile->getTable(), $column)) {
                return $profile->{$column};
            }
        }

        return null;
    }

    private function maskIdentifier(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $length = strlen($value);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 2).str_repeat('*', max(0, $length - 4)).substr($value, -2);
    }
}
