<?php

namespace App\Services;

use App\Mail\AccountRequestApprovedMail;
use App\Models\AccountRequest;
use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\Employee;
use App\Models\ExternalPerson;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Models\UserAppAccess;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Validation\ValidationException;

class CoreAccountRequestService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(array $data, Request $request): AccountRequest
    {
        $data = $this->normalizeSubmissionData($data);
        $this->ensureSubmissionIsUnique($data);

        return AccountRequest::create([
            ...$data,
            'status' => AccountRequest::STATUS_PENDING,
            'submitted_ip' => $request->ip(),
            'submitted_user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);
    }

    public function markInReview(AccountRequest $accountRequest, ?User $reviewer, ?string $adminNotes = null): AccountRequest
    {
        $accountRequest->forceFill([
            'status' => AccountRequest::STATUS_IN_REVIEW,
            'admin_notes' => $adminNotes ?? $accountRequest->admin_notes,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
        ])->save();

        return $accountRequest->fresh();
    }

    public function reject(AccountRequest $accountRequest, ?User $reviewer, ?string $adminNotes = null): AccountRequest
    {
        $accountRequest->forceFill([
            'status' => AccountRequest::STATUS_REJECTED,
            'admin_notes' => $adminNotes ?? $accountRequest->admin_notes,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
        ])->save();

        return $accountRequest->fresh();
    }

    public function approveSkeleton(AccountRequest $accountRequest, ?User $reviewer, ?string $adminNotes = null): AccountRequest
    {
        $accountRequest->forceFill([
            'status' => AccountRequest::STATUS_APPROVED,
            'admin_notes' => $adminNotes ?? $accountRequest->admin_notes,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
        ])->save();

        return $accountRequest->fresh();
    }

    public function approveAndProvision(AccountRequest $accountRequest, ?User $reviewer, ?string $adminNotes = null, bool $createRequestedAppAccess = false): AccountRequest
    {
        return DB::transaction(function () use ($accountRequest, $reviewer, $adminNotes, $createRequestedAppAccess): AccountRequest {
            $accountRequest->refresh();

            if ($accountRequest->isApproved() && $accountRequest->approved_user_id) {
                return $accountRequest;
            }

            $blockers = $this->approvalBlockers($accountRequest);

            if ($blockers !== []) {
                throw ValidationException::withMessages([
                    'account_request' => implode(' ', $blockers),
                ]);
            }

            if ($accountRequest->request_type === AccountRequest::TYPE_FIELD_SUPERVISOR) {
                $user = $this->createOrLinkFieldSupervisorUser($accountRequest);
                $profile = $user ? $this->createOrUpdateExternalPerson($accountRequest, $user) : null;
            } else {
                $profile = $this->createOrUpdateProfile($accountRequest);
                $user = app(CoreProfileUserProvisioningService::class)->provisionFor($profile);
            }

            if (! $user) {
                throw ValidationException::withMessages([
                    'account_request' => 'Akun tidak dapat dibuat atau ditautkan. Periksa email dan nomor identitas pemohon.',
                ]);
            }

            if ($profile) {
                if (! $profile instanceof ExternalPerson) {
                    $this->syncApprovedUserIdentity($profile, $user, $accountRequest);
                }
            }

            $this->assignDefaultGlobalRole($user, $accountRequest);
            $appAccess = $createRequestedAppAccess
                ? $this->createRequestedAppAccess($user, $accountRequest)
                : null;

            $accountRequest->forceFill([
                'status' => AccountRequest::STATUS_APPROVED,
                'admin_notes' => $adminNotes ?? $accountRequest->admin_notes,
                'reviewed_by' => $reviewer?->id,
                'reviewed_at' => now(),
                'approved_user_id' => $user->id,
            ])->save();

            UserActivityLog::create([
                'user_id' => $user->id,
                'action' => 'account_request.approved',
                'meta' => [
                    'account_request_id' => $accountRequest->id,
                    'request_type' => $accountRequest->request_type,
                    'profile_type' => $profile ? class_basename($profile) : 'CoreUserOnly',
                    'profile_id' => $profile?->getKey(),
                    'reviewed_by' => $reviewer?->id,
                    'app_access_created' => (bool) $appAccess,
                    'app_access_id' => $appAccess?->id,
                ],
            ]);

            $this->sendApprovalEmail($accountRequest->fresh(), $user, $appAccess);

            return $accountRequest->fresh();
        });
    }

    /**
     * @return array<string, bool>
     */
    public function duplicateSummary(AccountRequest $accountRequest): array
    {
        return [
            'email_exists' => filled($accountRequest->email) && User::where('email', $accountRequest->email)->exists(),
            'identity_number_exists' => filled($accountRequest->identity_number) && User::where('identity_number', $accountRequest->identity_number)->exists(),
            'student_number_exists' => filled($accountRequest->student_number) && Student::where('student_number', $accountRequest->student_number)->exists(),
            'lecturer_number_exists' => filled($accountRequest->lecturer_number) && Lecturer::where('lecturer_number', $accountRequest->lecturer_number)->exists(),
            'employee_number_exists' => filled($accountRequest->employee_number) && Employee::where('employee_number', $accountRequest->employee_number)->exists(),
        ];
    }

    /**
     * @return list<string>
     */
    public function approvalBlockers(AccountRequest $accountRequest): array
    {
        $blockers = [];

        if (! in_array($accountRequest->request_type, [
            AccountRequest::TYPE_STUDENT,
            AccountRequest::TYPE_LECTURER,
            AccountRequest::TYPE_EMPLOYEE,
            AccountRequest::TYPE_FIELD_SUPERVISOR,
        ], true)) {
            $blockers[] = 'Jenis pemohon belum didukung untuk approve otomatis.';
        }

        if (blank($accountRequest->name)) {
            $blockers[] = 'Nama wajib diisi.';
        }

        if (blank($accountRequest->email)) {
            $blockers[] = 'Email wajib diisi agar akun Core dapat dibuat.';
        }

        $existingUser = filled($accountRequest->email)
            ? User::query()->whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim((string) $accountRequest->email))])->first()
            : null;

        if ($accountRequest->request_type === AccountRequest::TYPE_STUDENT) {
            if (blank($accountRequest->student_number)) {
                $blockers[] = 'NIM wajib diisi untuk mahasiswa.';
            }

            if (blank($accountRequest->study_program_id)) {
                $blockers[] = 'Program studi wajib diisi untuk mahasiswa.';
            }

            $this->appendProfileConflictBlocker(
                $blockers,
                Student::query()->where('student_number', $accountRequest->student_number)->first(),
                $accountRequest,
                'NIM'
            );

            $this->appendProfileConflictBlocker(
                $blockers,
                Student::query()
                    ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim((string) $accountRequest->email))])
                    ->first(),
                $accountRequest,
                'email mahasiswa'
            );
        }

        if ($accountRequest->request_type === AccountRequest::TYPE_LECTURER) {
            if (blank($accountRequest->lecturer_number)) {
                $blockers[] = 'Nomor utama dosen wajib diisi.';
            }

            if (blank($accountRequest->department_id)) {
                $blockers[] = 'Departemen wajib diisi untuk dosen.';
            }

            $this->appendProfileConflictBlocker(
                $blockers,
                Lecturer::query()->where('lecturer_number', $accountRequest->lecturer_number)->first(),
                $accountRequest,
                'nomor dosen'
            );

            $this->appendProfileConflictBlocker(
                $blockers,
                Lecturer::query()
                    ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim((string) $accountRequest->email))])
                    ->first(),
                $accountRequest,
                'email dosen'
            );
        }

        if ($accountRequest->request_type === AccountRequest::TYPE_EMPLOYEE) {
            if (blank($accountRequest->employee_number)) {
                $blockers[] = 'Nomor pegawai wajib diisi untuk tendik/staf/laboran.';
            }

            if (blank($accountRequest->staff_type)) {
                $blockers[] = 'Jenis tendik/staf/laboran wajib diisi.';
            }

            $this->appendProfileConflictBlocker(
                $blockers,
                Employee::query()->where('employee_number', $accountRequest->employee_number)->first(),
                $accountRequest,
                'nomor pegawai'
            );
        }

        if ($accountRequest->request_type === AccountRequest::TYPE_FIELD_SUPERVISOR) {
            if (blank($accountRequest->phone)) {
                $blockers[] = 'Nomor telepon wajib diisi untuk mitra eksternal.';
            }

            if (blank($accountRequest->institution_name)) {
                $blockers[] = 'Instansi/perusahaan wajib diisi untuk mitra eksternal.';
            }

            if ($existingUser && ! in_array($existingUser->identity_type, ['external', 'field_supervisor'], true)) {
                $blockers[] = 'Email sudah terhubung ke user internal. Gunakan email eksternal berbeda atau tautkan manual oleh Admin Core.';
            }
        }

        if ($existingUser) {
            $existingUser->loadMissing(['student', 'lecturer', 'employee']);
            $expectedIdentifier = $this->identifierFor($accountRequest);
            $existingProfile = match ($accountRequest->request_type) {
                AccountRequest::TYPE_STUDENT => $existingUser->student,
                AccountRequest::TYPE_LECTURER => $existingUser->lecturer,
                AccountRequest::TYPE_EMPLOYEE => $existingUser->employee,
                AccountRequest::TYPE_FIELD_SUPERVISOR => null,
                default => null,
            };

            $existingIdentifier = match (true) {
                $existingProfile instanceof Student => $existingProfile->student_number,
                $existingProfile instanceof Lecturer => $existingProfile->lecturer_number,
                $existingProfile instanceof Employee => $existingProfile->employee_number,
                default => null,
            };

            if ($existingProfile && filled($expectedIdentifier) && $existingIdentifier !== $expectedIdentifier) {
                $blockers[] = 'Email sudah terhubung ke profil lain dengan nomor identitas berbeda.';
            }
        }

        return array_values(array_unique($blockers));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeSubmissionData(array $data): array
    {
        foreach ([
            'name',
            'email',
            'phone',
            'address',
            'gender',
            'identity_number',
            'student_number',
            'lecturer_number',
            'nip',
            'nidn',
            'nidk',
            'nuptk',
            'employee_number',
            'staff_type',
            'position_title',
            'institution_name',
            'institution_type',
            'profession',
            'requested_role',
            'requested_app_code',
        ] as $key) {
            if (array_key_exists($key, $data) && is_string($data[$key])) {
                $data[$key] = trim($data[$key]);
            }
        }

        if (filled($data['email'] ?? null)) {
            $data['email'] = strtolower((string) $data['email']);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function ensureSubmissionIsUnique(array $data): void
    {
        $errors = [];
        $activeRequestStatuses = [
            AccountRequest::STATUS_PENDING,
            AccountRequest::STATUS_IN_REVIEW,
        ];

        $email = strtolower(trim((string) ($data['email'] ?? '')));

        if (filled($email)) {
            $emailExists = User::query()
                ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
                ->exists()
                || Student::query()->whereRaw('LOWER(TRIM(email)) = ?', [$email])->exists()
                || Lecturer::query()->whereRaw('LOWER(TRIM(email)) = ?', [$email])->exists()
                || Employee::query()->whereRaw('LOWER(TRIM(email)) = ?', [$email])->exists()
                || AccountRequest::query()
                    ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
                    ->whereIn('status', $activeRequestStatuses)
                    ->exists();

            if ($emailExists) {
                $errors['email'] = 'Email sudah pernah terdaftar atau sedang menunggu review. Gunakan email lain atau hubungi Admin Core.';
            }
        }

        $identityNumber = trim((string) ($data['identity_number'] ?? ''));

        if (filled($identityNumber) && (
            User::query()->where('identity_number', $identityNumber)->exists()
            || AccountRequest::query()
                ->where('identity_number', $identityNumber)
                ->whereIn('status', $activeRequestStatuses)
                ->exists()
        )) {
            $errors['identity_number'] = 'NIK/nomor identitas sudah pernah terdaftar atau sedang menunggu review.';
        }

        $studentNumber = trim((string) ($data['student_number'] ?? ''));

        if (filled($studentNumber) && (
            Student::query()->where('student_number', $studentNumber)->exists()
            || AccountRequest::query()
                ->where('student_number', $studentNumber)
                ->whereIn('status', $activeRequestStatuses)
                ->exists()
        )) {
            $errors['student_number'] = 'NIM sudah pernah terdaftar atau sedang menunggu review.';
        }

        $lecturerNumber = trim((string) ($data['lecturer_number'] ?? ''));

        if (filled($lecturerNumber) && (
            Lecturer::query()->where('lecturer_number', $lecturerNumber)->exists()
            || AccountRequest::query()
                ->where('lecturer_number', $lecturerNumber)
                ->whereIn('status', $activeRequestStatuses)
                ->exists()
        )) {
            $errors['lecturer_number'] = 'Nomor utama dosen sudah pernah terdaftar atau sedang menunggu review.';
        }

        $employeeNumber = trim((string) ($data['employee_number'] ?? ''));

        if (filled($employeeNumber) && (
            Employee::query()->where('employee_number', $employeeNumber)->exists()
            || AccountRequest::query()
                ->where('employee_number', $employeeNumber)
                ->whereIn('status', $activeRequestStatuses)
                ->exists()
        )) {
            $errors['employee_number'] = 'Nomor pegawai sudah pernah terdaftar atau sedang menunggu review.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    protected function createOrUpdateProfile(AccountRequest $accountRequest): Student|Lecturer|Employee
    {
        return match ($accountRequest->request_type) {
            AccountRequest::TYPE_STUDENT => $this->createOrUpdateStudent($accountRequest),
            AccountRequest::TYPE_LECTURER => $this->createOrUpdateLecturer($accountRequest),
            AccountRequest::TYPE_EMPLOYEE => $this->createOrUpdateEmployee($accountRequest),
            default => throw ValidationException::withMessages([
                'account_request' => 'Jenis pemohon belum didukung untuk approve otomatis.',
            ]),
        };
    }

    protected function createOrLinkFieldSupervisorUser(AccountRequest $accountRequest): ?User
    {
        if (! config('core_identity.auto_user.enabled', true)) {
            return null;
        }

        $email = strtolower(trim((string) $accountRequest->email));
        $user = User::query()->whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();

        if ($user) {
            if (in_array($user->identity_type, [null, '', 'field_supervisor', 'external'], true)) {
                $user->forceFill([
                    'name' => $user->name ?: $accountRequest->name,
                    'phone' => $user->phone ?: $accountRequest->phone,
                    'address' => $user->address ?: $accountRequest->address,
                    'identity_type' => 'external',
                    'active' => true,
                ])->saveQuietly();
            }

            return $user;
        }

        return User::create([
            'name' => $accountRequest->name,
            'email' => $email,
            'phone' => $accountRequest->phone,
            'address' => $accountRequest->address,
            'username' => $email,
            'identity_type' => 'external',
            'identity_number' => null,
            'password' => Hash::make(app(CoreProfileUserProvisioningService::class)->generateInitialPassword($accountRequest->name, $email)),
            'active' => true,
            'must_change_password' => true,
            'password_changed_at' => null,
            'last_password_reset_at' => now(),
        ]);
    }

    protected function createOrUpdateExternalPerson(AccountRequest $accountRequest, User $user): ExternalPerson
    {
        $email = strtolower(trim((string) $accountRequest->email));
        $externalPerson = ExternalPerson::withTrashed()
            ->where(function ($query) use ($email, $user): void {
                $query->where('user_id', $user->id);

                if (filled($email)) {
                    $query->orWhereRaw('LOWER(TRIM(email)) = ?', [$email]);
                }
            })
            ->first();

        $externalPerson ??= new ExternalPerson;

        if ($externalPerson->trashed()) {
            $externalPerson->restore();
        }

        $externalPerson->fill([
            'user_id' => $user->id,
            'name' => $accountRequest->name,
            'email' => $email,
            'phone' => $accountRequest->phone,
            'institution_name' => $accountRequest->institution_name ?: $accountRequest->position_title,
            'institution_type' => $accountRequest->institution_type,
            'position_title' => $accountRequest->position_title ?: 'Mitra Eksternal',
            'profession' => $accountRequest->profession,
            'identity_number' => $accountRequest->identity_number,
            'address' => $accountRequest->address,
            'status' => 'active',
            'notes' => $accountRequest->notes,
        ])->save();

        return $externalPerson;
    }

    protected function createOrUpdateStudent(AccountRequest $accountRequest): Student
    {
        $student = Student::withTrashed()
            ->where('student_number', $accountRequest->student_number)
            ->first();

        if (! $student && filled($accountRequest->email)) {
            $student = Student::withTrashed()
                ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim((string) $accountRequest->email))])
                ->first();
        }

        if (! $student && $user = $this->matchingUserForRequest($accountRequest)) {
            $student = Student::withTrashed()
                ->where('user_id', $user->id)
                ->first();
        }

        $student ??= new Student(['student_number' => $accountRequest->student_number]);

        if ($student->trashed()) {
            $student->restore();
        }

        $this->fillProfile($student, [
            'student_number' => $accountRequest->student_number,
            'name' => $accountRequest->name,
            'email' => $accountRequest->email,
            'phone' => $accountRequest->phone,
            'address' => $accountRequest->address,
            'birth_date' => $accountRequest->birth_date,
            'study_program_id' => $accountRequest->study_program_id,
            'status' => 'active',
            'active' => true,
        ]);

        return $student;
    }

    protected function createOrUpdateLecturer(AccountRequest $accountRequest): Lecturer
    {
        $lecturer = Lecturer::withTrashed()
            ->where('lecturer_number', $accountRequest->lecturer_number)
            ->first();

        if (! $lecturer && filled($accountRequest->email)) {
            $lecturer = Lecturer::withTrashed()
                ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim((string) $accountRequest->email))])
                ->first();
        }

        if (! $lecturer && $user = $this->matchingUserForRequest($accountRequest)) {
            $lecturer = Lecturer::withTrashed()
                ->where('user_id', $user->id)
                ->first();
        }

        $lecturer ??= new Lecturer(['lecturer_number' => $accountRequest->lecturer_number]);

        if ($lecturer->trashed()) {
            $lecturer->restore();
        }

        $this->fillProfile($lecturer, [
            'lecturer_number' => $accountRequest->lecturer_number,
            'national_id_number' => $accountRequest->identity_number,
            'nip' => $accountRequest->nip,
            'nidn' => $accountRequest->nidn ?: $accountRequest->lecturer_number,
            'nidk' => $accountRequest->nidk,
            'nuptk' => $accountRequest->nuptk,
            'name' => $accountRequest->name,
            'email' => $accountRequest->email,
            'birth_date' => $accountRequest->birth_date,
            'department_id' => $accountRequest->department_id,
            'study_program_id' => $accountRequest->study_program_id,
            'phone' => $accountRequest->phone,
            'address' => $accountRequest->address,
            'notes' => $accountRequest->notes,
            'active' => true,
        ]);

        return $lecturer;
    }

    protected function createOrUpdateEmployee(AccountRequest $accountRequest): Employee
    {
        $employee = Employee::withTrashed()
            ->firstOrNew(['employee_number' => $accountRequest->employee_number]);

        if (! $employee->exists && $user = $this->matchingUserForRequest($accountRequest)) {
            $employee = Employee::withTrashed()
                ->where('user_id', $user->id)
                ->first() ?? $employee;
        }

        if ($employee->trashed()) {
            $employee->restore();
        }

        $this->fillProfile($employee, [
            'employee_number' => $accountRequest->employee_number,
            'national_id_number' => $accountRequest->identity_number,
            'name' => $accountRequest->name,
            'staff_type' => $accountRequest->staff_type,
            'department_id' => $accountRequest->department_id,
            'study_program_id' => $accountRequest->study_program_id,
            'position_title' => $accountRequest->position_title,
            'phone' => $accountRequest->phone,
            'email' => $accountRequest->email,
            'birth_date' => $accountRequest->birth_date,
            'gender' => $accountRequest->gender,
            'address' => $accountRequest->address,
            'status' => 'active',
            'notes' => $accountRequest->notes,
        ]);

        return $employee;
    }

    protected function matchingUserForRequest(AccountRequest $accountRequest): ?User
    {
        $email = strtolower(trim((string) $accountRequest->email));
        $identifier = $this->identifierFor($accountRequest);

        if (blank($email) && blank($identifier)) {
            return null;
        }

        $users = User::withTrashed()
            ->where(function ($query) use ($email, $identifier): void {
                if (filled($email)) {
                    $query->orWhereRaw('LOWER(TRIM(email)) = ?', [$email]);
                }

                if (filled($identifier)) {
                    $query
                        ->orWhere('username', $identifier)
                        ->orWhere('identity_number', $identifier);
                }
            })
            ->get();

        return $users->count() === 1 ? $users->first() : null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function fillProfile(Student|Lecturer|Employee $profile, array $attributes): void
    {
        $payload = $profile->exists
            ? array_filter($attributes, fn ($value): bool => filled($value) || is_bool($value))
            : $attributes;

        $profile->fill($payload);
        $profile->save();
    }

    protected function assignDefaultGlobalRole(User $user, AccountRequest $accountRequest): void
    {
        $roleName = match ($accountRequest->request_type) {
            AccountRequest::TYPE_STUDENT => 'mahasiswa',
            AccountRequest::TYPE_LECTURER => 'dosen',
            AccountRequest::TYPE_EMPLOYEE => 'tata-usaha',
            AccountRequest::TYPE_FIELD_SUPERVISOR => 'pembimbing-lapangan',
            default => null,
        };

        if (! $roleName) {
            return;
        }

        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            ['label' => str($roleName)->headline()->toString(), 'active' => true],
        );

        $user->roles()->syncWithoutDetaching([$role->id]);
    }

    protected function syncApprovedUserIdentity(Student|Lecturer|Employee $profile, User $user, AccountRequest $accountRequest): void
    {
        $identifier = $this->identifierFor($accountRequest);

        if (blank($identifier)) {
            return;
        }

        $user->forceFill([
            'name' => $profile->name,
            'email' => $profile->email,
            'username' => $identifier,
            'identity_type' => match (true) {
                $profile instanceof Student => 'student',
                $profile instanceof Lecturer => 'lecturer',
                $profile instanceof Employee => 'employee',
            },
            'identity_number' => $identifier,
            'active' => match (true) {
                $profile instanceof Student => (bool) ($profile->active ?? $profile->status !== 'inactive'),
                $profile instanceof Lecturer => (bool) ($profile->active ?? true),
                $profile instanceof Employee => ($profile->status ?? 'active') !== 'inactive',
            },
        ])->saveQuietly();
    }

    protected function createRequestedAppAccess(User $user, AccountRequest $accountRequest): ?UserAppAccess
    {
        $appCode = trim((string) $accountRequest->requested_app_code);
        $roleSlug = trim((string) $accountRequest->requested_role);

        if (blank($appCode) || blank($roleSlug)) {
            return null;
        }

        $application = CoreApplication::query()
            ->where('app_code', $appCode)
            ->where('is_active', true)
            ->first();

        if (! $application) {
            throw ValidationException::withMessages([
                'account_request' => "Aplikasi {$appCode} belum aktif di registry Core.",
            ]);
        }

        $role = CoreApplicationRole::query()
            ->where('app_code', $appCode)
            ->where('role_slug', $roleSlug)
            ->where('is_active', true)
            ->first();

        if (! $role) {
            throw ValidationException::withMessages([
                'account_request' => "Role aplikasi {$roleSlug} belum aktif untuk {$appCode}.",
            ]);
        }

        return UserAppAccess::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'app_code' => $appCode,
                'role_slug' => $roleSlug,
            ],
            [
                'permissions' => [],
                'is_active' => true,
                'activated_at' => now(),
                'deactivated_at' => null,
            ],
        );
    }

    protected function appendProfileConflictBlocker(array &$blockers, Student|Lecturer|Employee|null $profile, AccountRequest $accountRequest, string $label): void
    {
        if (! $profile) {
            return;
        }

        if (filled($profile->email) && filled($accountRequest->email) && strtolower(trim((string) $profile->email)) !== strtolower(trim((string) $accountRequest->email))) {
            $blockers[] = "{$label} sudah terdaftar dengan email berbeda.";
        }

        $expectedIdentifier = $this->identifierFor($accountRequest);
        $profileIdentifier = match (true) {
            $profile instanceof Student => $profile->student_number,
            $profile instanceof Lecturer => $profile->lecturer_number,
            $profile instanceof Employee => $profile->employee_number,
            default => null,
        };

        if (filled($expectedIdentifier) && filled($profileIdentifier) && $profileIdentifier !== $expectedIdentifier) {
            $blockers[] = "{$label} sudah terhubung ke nomor identitas berbeda.";
        }

        if (filled($profile->user_id)) {
            $profile->loadMissing('user');

            if ($profile->user && filled($accountRequest->email) && strtolower(trim((string) $profile->user->email)) !== strtolower(trim((string) $accountRequest->email))) {
                $blockers[] = "{$label} sudah terhubung ke user lain.";
            }
        }
    }

    protected function identifierFor(AccountRequest $accountRequest): ?string
    {
        return match ($accountRequest->request_type) {
            AccountRequest::TYPE_STUDENT => $accountRequest->student_number,
            AccountRequest::TYPE_LECTURER => $accountRequest->lecturer_number,
            AccountRequest::TYPE_EMPLOYEE => $accountRequest->employee_number,
            AccountRequest::TYPE_FIELD_SUPERVISOR => $accountRequest->email,
            default => null,
        };
    }

    protected function sendApprovalEmail(AccountRequest $accountRequest, User $user, ?UserAppAccess $appAccess): void
    {
        if (blank($user->email)) {
            return;
        }

        try {
            $passwordSetupExpiresInMinutes = (int) config('auth.passwords.users.expire', 60);
            $passwordSetupUrl = route('profile.password.reset.edit', [
                'token' => PasswordBroker::broker()->createToken($user),
                'email' => $user->email,
            ]);

            Mail::to($user->email)->send(new AccountRequestApprovedMail(
                $accountRequest,
                $user,
                $appAccess,
                $passwordSetupUrl,
                $passwordSetupExpiresInMinutes,
            ));

            UserActivityLog::create([
                'user_id' => $user->id,
                'action' => 'account_request.approval_email_sent',
                'meta' => [
                    'account_request_id' => $accountRequest->id,
                    'app_access_id' => $appAccess?->id,
                    'source' => 'core_account_request',
                    'password_setup_link' => 'created',
                    'password_setup_expires_in_minutes' => $passwordSetupExpiresInMinutes,
                ],
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            UserActivityLog::create([
                'user_id' => $user->id,
                'action' => 'account_request.approval_email_failed',
                'meta' => [
                    'account_request_id' => $accountRequest->id,
                    'app_access_id' => $appAccess?->id,
                    'source' => 'core_account_request',
                    'error_class' => $exception::class,
                ],
            ]);
        }
    }
}
