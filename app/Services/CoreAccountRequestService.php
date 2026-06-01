<?php

namespace App\Services;

use App\Models\AccountRequest;
use App\Models\Employee;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

class CoreAccountRequestService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(array $data, Request $request): AccountRequest
    {
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
}
