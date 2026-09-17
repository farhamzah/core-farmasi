<?php

namespace App\Services\Karir;

use App\Models\Alumni;
use App\Models\CareerAlumniGrant;
use App\Models\CareerAlumniRegistration;
use App\Models\CareerIdentitySubject;
use App\Models\CoreApplicationRole;
use App\Models\Student;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Models\UserAppAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KarirAlumniApprovalService
{
    public function __construct(protected KarirEligibilityService $eligibility) {}

    public function approve(CareerAlumniRegistration $registration, User $approver): User
    {
        if (! $this->canDecide($approver)) {
            throw new AuthorizationException('Only an active Karir or Core administrator may approve alumni.');
        }

        return DB::transaction(function () use ($registration, $approver): User {
            $registration = CareerAlumniRegistration::query()->lockForUpdate()->findOrFail($registration->id);

            if ($registration->status === CareerAlumniRegistration::STATUS_APPROVED && $registration->matched_user_id) {
                return User::findOrFail($registration->matched_user_id);
            }

            if ($registration->status !== CareerAlumniRegistration::STATUS_PENDING || $registration->conflict_code) {
                throw ValidationException::withMessages(['registration' => 'Registration requires manual conflict review.']);
            }

            $user = $registration->matched_user_id ? User::find($registration->matched_user_id) : null;

            if ($user && ! $user->active) {
                throw ValidationException::withMessages(['registration' => 'Matched Core user is inactive.']);
            }

            $accountResolution = $user ? 'existing_core_user' : 'minimal_core_user';

            if (! $user) {
                if (blank($registration->password_hash)) {
                    throw ValidationException::withMessages(['registration' => 'Pending credential is unavailable.']);
                }

                $user = User::create([
                    'name' => $registration->full_name,
                    'email' => $registration->personal_email,
                    'phone' => $registration->whatsapp,
                    'username' => $registration->student_number,
                    'identity_type' => 'alumni',
                    'identity_number' => $registration->student_number,
                    'password' => $registration->password_hash,
                    'active' => true,
                    'must_change_password' => false,
                ]);
            }

            if (! CoreApplicationRole::query()->where('app_code', 'karir-farmasi')->where('role_slug', 'kandidat-karir')->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['registration' => 'Karir candidate role is not registered.']);
            }

            $source = $this->eligibility->source($user) ?: 'alumni_admin_approval';

            CareerAlumniGrant::query()->updateOrCreate(
                ['user_id' => $user->id, 'career_scope' => 'farmasi'],
                ['eligibility_source' => $source, 'is_active' => true, 'approved_by' => $approver->id,
                    'approved_at' => now(), 'approval_reference' => $registration->reference],
            );

            UserAppAccess::query()->updateOrCreate(
                ['user_id' => $user->id, 'app_code' => 'karir-farmasi', 'role_slug' => 'kandidat-karir'],
                ['permissions' => null, 'is_active' => true, 'activated_at' => now(), 'deactivated_at' => null],
            );

            CareerIdentitySubject::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['subject' => (string) Str::uuid()],
            );

            $student = Student::query()
                ->with('studyProgram')
                ->where(function ($query) use ($user, $registration): void {
                    $query->where('user_id', $user->id)
                        ->orWhere('student_number', $registration->student_number);
                })
                ->first();

            $alumni = Alumni::query()
                ->where('user_id', $user->id)
                ->orWhere('student_number', $registration->student_number)
                ->first() ?? new Alumni;

            $alumni->fill([
                'user_id' => $user->id,
                'student_id' => $student?->id,
                'study_program_id' => $student?->study_program_id,
                'student_number' => $registration->student_number,
                'name' => $registration->full_name,
                'personal_email' => $registration->personal_email,
                'whatsapp' => $registration->whatsapp,
                'program_name_snapshot' => $student?->studyProgram?->name ?: $registration->claimed_program,
                'graduation_year' => $registration->graduation_year,
                'status' => 'verified',
                'source' => 'karir_approval',
                'active' => true,
            ])->save();

            $registration->forceFill([
                'status' => CareerAlumniRegistration::STATUS_APPROVED,
                'matched_user_id' => $user->id,
                'account_resolution' => $accountResolution,
                'password_hash' => null,
                'reviewed_by' => $approver->id,
                'reviewed_at' => now(),
            ])->save();

            UserActivityLog::create([
                'user_id' => $approver->id,
                'action' => 'karir.alumni.approved',
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'meta' => ['registration_reference' => $registration->reference, 'approved_user_id' => $user->id],
            ]);

            return $user;
        });
    }

    public function canDecide(User $user): bool
    {
        if (! $user->active) {
            return false;
        }

        $isKarirAdmin = $user->appAccesses()
            ->where('app_code', 'karir-farmasi')
            ->where('role_slug', 'admin-karir')
            ->where('is_active', true)
            ->exists();

        return $isKarirAdmin || $user->roles()
            ->whereIn('name', ['super-admin', 'admin-core'])
            ->where('active', true)
            ->exists();
    }
}
