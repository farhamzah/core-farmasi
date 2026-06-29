<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\ExternalPerson;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

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
            'externalPerson',
        ]);

        $profiles = collect([
            $this->studentSummary($user->student),
            $this->lecturerSummary($user->lecturer),
            $this->employeeSummary($user->employee),
            $this->externalPersonSummary($user->externalPerson),
        ])->filter()->values()->all();

        return [
            'user' => [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'identity_type' => $user->identity_type,
                'identity_number_masked' => $this->maskIdentifier($user->identity_number),
                'profile_photo_path' => $this->valueIfColumnExists($user, 'profile_photo_path'),
                'profile_photo_url' => $this->profilePhotoUrl($user),
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

        foreach (['student', 'lecturer', 'employee', 'externalPerson'] as $relation) {
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

            if ($profile instanceof Lecturer
                && array_intersect(['front_title', 'back_title'], array_keys($changes))
                && Schema::hasColumn($profile->getTable(), 'title_updated_at')) {
                $changes['title_updated_at'] = now();
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
     * @return array{updated: array<int, string>}
     */
    public function storeProfilePhoto(User $user, UploadedFile $photo, Request $request): array
    {
        if (! Schema::hasColumn($user->getTable(), 'profile_photo_path')) {
            return ['updated' => []];
        }

        $oldPath = $user->profile_photo_path;
        $path = $photo->store('profile-photos', 'public');

        $user->forceFill(['profile_photo_path' => $path])->save();

        if ($oldPath && str_starts_with((string) $oldPath, 'profile-photos/')) {
            Storage::disk('public')->delete($oldPath);
        }

        UserActivityLog::create([
            'user_id' => $user->id,
            'action' => 'profile.photo_updated',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'meta' => [
                'profile_user_id' => $user->id,
                'changed_fields' => ['profile_photo_path'],
            ],
        ]);

        return ['updated' => ['profile_photo_path']];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function editableFieldsFor(User $user): array
    {
        $profileFields = [
            'student' => $user->student ? $this->existingColumns(Student::class, ['email', 'phone', 'address', 'birth_place', 'birth_date', 'enrolled_at']) : [],
            'lecturer' => $user->lecturer ? $this->existingColumns(Lecturer::class, ['email', 'front_title', 'back_title', 'phone', 'address', 'birth_place', 'birth_date', 'national_id_number', 'nip', 'nuptk', 'notes']) : [],
            'employee' => $user->employee ? $this->existingColumns(Employee::class, ['email', 'phone', 'address', 'birth_place', 'birth_date', 'gender', 'national_id_number', 'staff_type', 'position_title', 'notes']) : [],
            'externalPerson' => $user->externalPerson ? $this->existingColumns(ExternalPerson::class, ['email', 'phone', 'address', 'institution_name', 'institution_type', 'position_title', 'profession', 'notes']) : [],
        ];

        $hasEditableLinkedProfile = collect(['student', 'lecturer', 'employee', 'externalPerson'])
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

        foreach (['student', 'lecturer', 'employee', 'externalPerson'] as $relation) {
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
            'phone' => $user->student?->phone ?? $user->lecturer?->phone ?? $user->employee?->phone ?? $user->externalPerson?->phone ?? $this->valueIfColumnExists($user, 'phone'),
            'address' => $user->student?->address ?? $user->lecturer?->address ?? $user->employee?->address ?? $user->externalPerson?->address ?? $this->valueIfColumnExists($user, 'address'),
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
            $this->completionItem('linked_profile', 'Profil resmi tertaut', $profiles !== []),
            $this->completionItem('profile_photo', 'Foto profil tersedia', filled($this->valueIfColumnExists($user, 'profile_photo_path'))),
            $this->completionItem('email', 'Email utama tersedia', filled($user->email) || collect($profiles)->contains(fn (array $profile): bool => filled($profile['email'] ?? null))),
        ];

        if ($user->student) {
            $student = $user->student;
            $items = [
                ...$items,
                $this->completionItem('student_number', 'NIM tersedia', filled($student->student_number)),
                $this->completionItem('student_program', 'Program studi tersedia', filled($student->study_program_id)),
                $this->completionItem('student_status', 'Status mahasiswa tersedia', filled($student->status)),
                $this->completionItem('student_birth_place', 'Tempat lahir tersedia', filled($this->valueIfColumnExists($student, 'birth_place'))),
                $this->completionItem('student_birth_date', 'Tanggal lahir tersedia', filled($student->birth_date)),
                $this->completionItem('student_phone', 'Telepon mahasiswa tersedia', filled($student->phone)),
                $this->completionItem('student_address', 'Alamat mahasiswa tersedia', filled($student->address)),
            ];

            if (Schema::hasColumn($student->getTable(), 'enrolled_at')) {
                $items[] = $this->completionItem('student_enrolled_at', 'Tanggal masuk tersedia', filled($student->enrolled_at));
            }
        } elseif ($user->lecturer) {
            $lecturer = $user->lecturer;
            $items = [
                ...$items,
                $this->completionItem('lecturer_title', 'Gelar dosen tersedia', filled($this->valueIfColumnExists($lecturer, 'front_title')) || filled($this->valueIfColumnExists($lecturer, 'back_title'))),
                $this->completionItem('lecturer_number', 'Nomor utama dosen tersedia', filled($lecturer->lecturer_number)),
                $this->completionItem('lecturer_nidn_or_nidk', 'NIDN atau NIDK tersedia', filled($lecturer->nidn) || filled($lecturer->nidk)),
                $this->completionItem('lecturer_national_id', 'NIK / No. KTP tersedia', filled($lecturer->national_id_number)),
                $this->completionItem('lecturer_nip', 'NIP tersedia jika ada', filled($lecturer->nip)),
                $this->completionItem('lecturer_nuptk', 'NUPTK tersedia jika ada', filled($lecturer->nuptk)),
                $this->completionItem('lecturer_birth_place', 'Tempat lahir tersedia', filled($this->valueIfColumnExists($lecturer, 'birth_place'))),
                $this->completionItem('lecturer_birth_date', 'Tanggal lahir tersedia', filled($lecturer->birth_date)),
                $this->completionItem('lecturer_department', 'Departemen tersedia', filled($lecturer->department_id)),
                $this->completionItem('lecturer_phone', 'Telepon dosen tersedia', filled($lecturer->phone)),
                $this->completionItem('lecturer_address', 'Alamat dosen tersedia', filled($lecturer->address)),
            ];
        } elseif ($user->employee) {
            $employee = $user->employee;
            $items = [
                ...$items,
                $this->completionItem('employee_number', 'Nomor pegawai tersedia', filled($employee->employee_number)),
                $this->completionItem('employee_staff_type', 'Jenis tendik/staf tersedia', filled($employee->staff_type)),
                $this->completionItem('employee_position', 'Jabatan/posisi tersedia', filled($employee->position_title)),
                $this->completionItem('employee_national_id', 'NIK / No. KTP tersedia', filled($employee->national_id_number)),
                $this->completionItem('employee_birth_place', 'Tempat lahir tersedia', filled($this->valueIfColumnExists($employee, 'birth_place'))),
                $this->completionItem('employee_birth_date', 'Tanggal lahir tersedia', filled($employee->birth_date)),
                $this->completionItem('employee_gender', 'Jenis kelamin tersedia', filled($employee->gender)),
                $this->completionItem('employee_unit', 'Unit kerja tersedia', filled($employee->department_id) || filled($employee->study_program_id)),
                $this->completionItem('employee_phone', 'Telepon tendik tersedia', filled($employee->phone)),
                $this->completionItem('employee_address', 'Alamat tendik tersedia', filled($employee->address)),
            ];
        } elseif ($user->externalPerson) {
            $externalPerson = $user->externalPerson;
            $items = [
                ...$items,
                $this->completionItem('external_institution', 'Instansi mitra tersedia', filled($externalPerson->institution_name)),
                $this->completionItem('external_institution_type', 'Jenis instansi tersedia', filled($externalPerson->institution_type)),
                $this->completionItem('external_position', 'Jabatan/posisi tersedia', filled($externalPerson->position_title)),
                $this->completionItem('external_phone', 'Telepon mitra tersedia', filled($externalPerson->phone)),
                $this->completionItem('external_address', 'Alamat mitra tersedia', filled($externalPerson->address)),
            ];
        } else {
            $items = [
                ...$items,
                $this->completionItem('official_identifier', 'Nomor identitas resmi tersedia', filled($user->identity_number)),
                $this->completionItem('phone', 'Telepon tersedia', filled($contact['phone'])),
                $this->completionItem('address', 'Alamat tersedia', filled($contact['address'])),
            ];
        }

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
     * @return array{key: string, label: string, complete: bool, sensitive: bool}
     */
    private function completionItem(string $key, string $label, bool $complete, bool $sensitive = false): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'complete' => $complete,
            'sensitive' => $sensitive,
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
                    'Tempat Lahir' => $this->valueIfColumnExists($student, 'birth_place'),
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

        $displayNameWithTitle = app(CorePersonNameFormatter::class)->formatWithTitle(
            $this->valueIfColumnExists($lecturer, 'front_title'),
            $lecturer->name,
            $this->valueIfColumnExists($lecturer, 'back_title'),
        );

        return [
            'type' => 'lecturer',
            'label' => 'Dosen',
            'name' => $displayNameWithTitle,
            'name_without_title' => $lecturer->name,
            'display_name_with_title' => $displayNameWithTitle,
            'front_title' => $this->valueIfColumnExists($lecturer, 'front_title'),
            'back_title' => $this->valueIfColumnExists($lecturer, 'back_title'),
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
                    'Nama Dasar' => $lecturer->name,
                    'Gelar Depan' => $this->valueIfColumnExists($lecturer, 'front_title'),
                    'Gelar Belakang' => $this->valueIfColumnExists($lecturer, 'back_title'),
                    'Nama Resmi Bergelar' => $displayNameWithTitle,
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
                    'Tempat Lahir' => $this->valueIfColumnExists($lecturer, 'birth_place'),
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
                    'Tempat Lahir' => $this->valueIfColumnExists($employee, 'birth_place'),
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
     * @return array<string, mixed>|null
     */
    private function externalPersonSummary(?ExternalPerson $externalPerson): ?array
    {
        if (! $externalPerson) {
            return null;
        }

        return [
            'type' => 'external',
            'label' => 'Mitra Eksternal',
            'name' => $externalPerson->name,
            'identifier_label' => 'Email / Nomor Mitra',
            'identifier' => $externalPerson->external_number ?: $externalPerson->email,
            'email' => $externalPerson->email,
            'status' => $externalPerson->status,
            'unit' => $externalPerson->institution_name,
            'unit_secondary' => self::externalInstitutionTypeOptions()[$externalPerson->institution_type] ?? $externalPerson->institution_type,
            'position_title' => $externalPerson->position_title,
            'phone' => $externalPerson->phone,
            'address' => $externalPerson->address,
            'official_identifiers' => [
                ['label' => 'Nomor Mitra', 'value' => $externalPerson->external_number, 'sensitive' => false],
                ['label' => 'NIK / Identitas', 'value' => $this->maskIdentifier($externalPerson->identity_number), 'sensitive' => true],
            ],
            'profile_sections' => [
                'Mitra' => [
                    'Instansi / Perusahaan' => $externalPerson->institution_name,
                    'Jenis Instansi' => self::externalInstitutionTypeOptions()[$externalPerson->institution_type] ?? $externalPerson->institution_type,
                    'Jabatan/Posisi' => $externalPerson->position_title,
                    'Profesi' => $externalPerson->profession,
                    'Status' => $externalPerson->status,
                ],
                'Kontak' => [
                    'Email Profil' => $externalPerson->email,
                    'Telepon' => $externalPerson->phone,
                    'Alamat' => $externalPerson->address,
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
            'Mahasiswa' => ['NIM', 'nama resmi', 'program studi', 'tempat/tanggal lahir', 'kontak aktif', 'alamat', 'foto profil'],
            'Dosen' => ['gelar akademik/profesi', 'NIK/KTP', 'NIDN/NIDK', 'NIP bila ASN', 'NUPTK', 'homebase/unit', 'tempat/tanggal lahir', 'kontak aktif', 'foto profil'],
            'Tendik' => ['NIK/KTP', 'nomor pegawai', 'NUPTK bila ada', 'unit kerja', 'jabatan/posisi', 'tempat/tanggal lahir', 'kontak aktif', 'foto profil'],
            'Mitra Eksternal' => ['nama', 'email', 'telepon', 'instansi', 'jenis instansi', 'jabatan/posisi', 'alamat', 'foto profil'],
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
        foreach (['student', 'lecturer', 'employee', 'externalPerson'] as $relation) {
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

    private function profilePhotoUrl(User $user): ?string
    {
        if (! Schema::hasColumn($user->getTable(), 'profile_photo_path') || ! $user->profile_photo_path) {
            return null;
        }

        return '/storage/'.ltrim((string) $user->profile_photo_path, '/');
    }

    /**
     * @return array<string, string>
     */
    private static function externalInstitutionTypeOptions(): array
    {
        return [
            'industry' => 'Industri',
            'hospital' => 'Rumah Sakit',
            'pharmacy' => 'Apotek',
            'university' => 'Universitas / Kampus Lain',
            'clinic' => 'Klinik',
            'government' => 'Instansi Pemerintah',
            'other' => 'Lainnya',
        ];
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
