<?php

namespace App\Services\Karir;

use App\Models\CareerAlumniRegistration;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KarirAlumniRejectionService
{
    public function __construct(private readonly KarirAlumniApprovalService $approval) {}

    public function reject(CareerAlumniRegistration $registration, User $approver, string $reason): CareerAlumniRegistration
    {
        if (! $this->approval->canDecide($approver)) {
            throw new AuthorizationException('Only an active Karir or Core administrator may reject alumni.');
        }

        return DB::transaction(function () use ($registration, $approver, $reason): CareerAlumniRegistration {
            $registration = CareerAlumniRegistration::query()->lockForUpdate()->findOrFail($registration->id);

            if ($registration->status === 'rejected') {
                return $registration;
            }

            if ($registration->status === CareerAlumniRegistration::STATUS_APPROVED) {
                throw ValidationException::withMessages(['registration' => 'Approved registration cannot be rejected by this operation.']);
            }

            $registration->forceFill([
                'status' => 'rejected',
                'password_hash' => null,
                'review_note' => trim($reason),
                'reviewed_by' => $approver->id,
                'reviewed_at' => now(),
            ])->save();

            UserActivityLog::create([
                'user_id' => $approver->id,
                'action' => 'karir.alumni.rejected',
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'meta' => ['registration_reference' => $registration->reference],
            ]);

            return $registration;
        });
    }
}
