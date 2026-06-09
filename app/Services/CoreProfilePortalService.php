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
            'edit_values' => $this->editableValuesFor($user),
            'completion' => $this->completionFor($user, $profiles),
            'profile_standards' => $this->profileStandards(),
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

        $userChanges = [];

        foreach ($editableFields['user'] ?? [] as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $userChanges[$field] = $data[$field];
        }

        if ($userChanges !== []) {
            $user->fill($userChanges);

            if ($user->isDirty()) {
                $user->save();
                $updated = array_values(array_unique([...$updated, ...array_keys($userChanges)]));
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
        $profileFields = [
            'student' => $this->existingColumns(Student::class, ['email', 'phone', 'address', 'birth_date', 'enrolled_at']),
            'lecturer' => $this->existingColumns(Lecturer::class, ['email', 'phone', 'address', 'birth_date', 'national_id_number', 'nip', 'nuptk', 'notes']),
            'employee' => $this->existingColumns(Employee::class, ['email', 'phone', 'address', 'birth_date', 'gender', 'national_id_number', 'staff_type', 'position_title', 'notes']),
        ];

        $hasEditableLinkedProfile = collect(['student', 'lecturer', 'employee'])
            ->contains(fn (string $relation): bool => (bool) $user->{$relation} && ($profileFields[$relation] ?? []) !== []);

        return [
            ...$profileFields,
            'user' => $hasEditableLinkedProfile ? [] : $this->existingColumns(User::class, ['phone', 'address', 'alternate_email']),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function editableValuesFor(User $user): array
    {
        $values = $this->contactValuesFor($user);

        foreach (['student', 'lecturer', 'employee'] as $relation) {
            $profile = $user->{$relation};

            if (! $profile) {
                continue;
            }

            foreach ($this->editableFieldsFor($user)[$relation] ?? [] as $field) {
                $values[$field] = $this->editableValue($profile, $field);
            }
        }

        foreach ($this->editableFieldsFor($user)['user'] ?? [] as $field) {
            $values[$field] = $this->editableValue($user, $field);
        }

        return $values;
    }

    public function isComplete(User $user): bool
    {
        $summary = $this->summaryFor($user);

        return (bool) ($summary['completion']['is_complete'] ?? false);
    }

    /**
     * @return array<string, string|null>
     */
    private function contactValuesFor(User $user): array
    {
        return [
            'phone' => $user->student?->phone ?? $user->lecturer?->phone ?? $user->employee?->phone ?? $this->valueIfColumnExists($user, 'phone'),
            'address' => $user->student?->address ?? $user->lecturer?->address ?? $user->employee?->address ?? $this->valueIfColumnExists($user, 'address'),
            'alternate_email' => $this->valueFromFirstAvailableColumn($user, 'alternate_email') ?? $this->valueIfColumnExists($user, 'alternate_email'),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $profiles
     * @return array<string, mixed>
     */
    private function completionFor(User $user, array $profiles): array
    {
        $contact = $this->contactValuesFor($user);

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
                'key' => 'official_identifier',
                'label' => 'Nomor identitas resmi tersedia',
                'complete' => filled($user->identity_number) || collect($profiles)->contains(fn (array $profile): bool => filled($profile['identifier'] ?? null)),
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
        ];

        $completed = collect($items)->where('complete', true)->count();
        $total = count($items);

        return [
            'percentage' => (int) round(($completed / $total) * 100),
            'completed' => $completed,
            'total' => $total,
            'items' => $items,
            'is_complete' => $completed === $total,
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
            'official_identifiers' => [
                ['label' => 'NIM', 'value' => $student->student_number, 'sensitive' => false],
            ],
            'profile_sections' => [
                'Akademik' => [
                    'Program Studi' => $student->studyProgram?->name,
                    'Fakultas/Departemen' => $student->studyProgram?->department?->name,
                    'Status Mahasiswa' => $student->status,
                    'Tanggal Lahir' => filled($student->birth_date) ? 'Tercatat' : 'Belum tercatat',
                ],
                'Kontak' => [
                    'Email Profil' => $student->email,
                    'Telepon' => $this->valueIfColumnExists($student, 'phone'),
                    'Alamat' => $this->valueIfColumnExists($student, 'address'),
                ],
            ],
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
            'identifier_label' => 'Nomor Utama Dosen',
            'identifier' => $lecturer->lecturer_number,
            'email' => $lecturer->email,
            'status' => $lecturer->active ? 'active' : 'inactive',
            'unit' => $lecturer->studyProgram?->name,
            'unit_secondary' => $lecturer->department?->name,
            'phone' => $lecturer->phone,
            'address' => $lecturer->address ?? null,
            'birth_date_recorded' => filled($lecturer->birth_date),
            'official_identifiers' => [
                ['label' => 'Nomor Utama', 'value' => $lecturer->lecturer_number, 'sensitive' => false],
                ['label' => 'NIDN', 'value' => $this->valueIfColumnExists($lecturer, 'nidn'), 'sensitive' => false],
                ['label' => 'NIDK', 'value' => $this->valueIfColumnExists($lecturer, 'nidk'), 'sensitive' => false],
                ['label' => 'NIP', 'value' => $this->valueIfColumnExists($lecturer, 'nip'), 'sensitive' => false],
                ['label' => 'NUPTK', 'value' => $this->valueIfColumnExists($lecturer, 'nuptk'), 'sensitive' => false],
                ['label' => 'NIK / No. KTP', 'value' => $this->maskIdentifier($this->valueIfColumnExists($lecturer, 'national_id_number')), 'sensitive' => true],
            ],
            'profile_sections' => [
                'Identitas Resmi' => [
                    'Nomor Utama' => $lecturer->lecturer_number,
                    'NIDN' => $this->valueIfColumnExists($lecturer, 'nidn'),
                    'NIDK' => $this->valueIfColumnExists($lecturer, 'nidk'),
                    'NIP' => $this->valueIfColumnExists($lecturer, 'nip'),
                    'NUPTK' => $this->valueIfColumnExists($lecturer, 'nuptk'),
                    'NIK / No. KTP' => $this->maskIdentifier($this->valueIfColumnExists($lecturer, 'national_id_number')),
                ],
                'Penempatan' => [
                    'Program Studi' => $lecturer->studyProgram?->name,
                    'Departemen' => $lecturer->department?->name,
                    'Status Dosen' => $lecturer->active ? 'active' : 'inactive',
                    'Tanggal Lahir' => filled($lecturer->birth_date) ? 'Tercatat' : 'Belum tercatat',
                ],
                'Kontak' => [
                    'Email Profil' => $lecturer->email,
                    'Telepon' => $lecturer->phone,
                    'Alamat' => $lecturer->address ?? null,
                ],
            ],
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
            'official_identifiers' => [
                ['label' => 'Nomor Pegawai', 'value' => $employee->employee_number, 'sensitive' => false],
                ['label' => 'NIK / No. KTP', 'value' => $this->maskIdentifier($employee->national_id_number), 'sensitive' => true],
            ],
            'profile_sections' => [
                'Kepegawaian' => [
                    'Nomor Pegawai' => $employee->employee_number,
                    'Jenis Staf' => $employee->staff_type,
                    'Jabatan/Posisi' => $employee->position_title,
                    'NIK / No. KTP' => $this->maskIdentifier($employee->national_id_number),
                    'Status' => $employee->status,
                ],
                'Unit Kerja' => [
                    'Program Studi' => $employee->studyProgram?->name,
                    'Departemen' => $employee->department?->name,
                ],
                'Kontak' => [
                    'Email Profil' => $employee->email,
                    'Telepon' => $employee->phone,
                    'Alamat' => $employee->address,
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function profileStandards(): array
    {
        return [
            'Mahasiswa' => ['NIM', 'nama resmi', 'program studi', 'status akademik', 'kontak aktif', 'alamat'],
            'Dosen' => ['NIK/KTP', 'NIDN/NIDK', 'NIP bila ASN', 'NUPTK', 'homebase/unit', 'jabatan/status', 'kontak aktif'],
            'Tendik' => ['NIK/KTP', 'nomor pegawai', 'NUPTK bila ada', 'unit kerja', 'jabatan/posisi', 'kontak aktif'],
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

    private function editableValue(object $model, string $column): ?string
    {
        if (! Schema::hasColumn($model->getTable(), $column)) {
            return null;
        }

        $value = $model->{$column};

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return $value === null ? null : (string) $value;
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
