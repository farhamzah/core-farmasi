<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CareerAlumniRegistration;
use App\Models\User;
use App\Services\Karir\KarirAlumniApprovalService;
use App\Services\Karir\KarirAlumniRejectionService;
use App\Services\Karir\KarirIdentityVerificationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KarirAlumniReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'manual_review', 'approved', 'rejected'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $registrations = CareerAlumniRegistration::query()
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'data' => $registrations->getCollection()->map(fn (CareerAlumniRegistration $registration) => $this->reviewPayload($registration)),
            'meta' => [
                'current_page' => $registrations->currentPage(),
                'last_page' => $registrations->lastPage(),
                'per_page' => $registrations->perPage(),
                'total' => $registrations->total(),
            ],
        ]);
    }

    public function show(string $reference): JsonResponse
    {
        return response()->json(['data' => $this->reviewPayload($this->find($reference))]);
    }

    public function status(string $reference): JsonResponse
    {
        $registration = $this->find($reference);

        return response()->json(['data' => [
            'reference' => $registration->reference,
            'status' => $registration->status,
            'account_resolution' => $registration->status === 'approved' ? $registration->account_resolution : null,
        ]]);
    }

    public function approve(Request $request, string $reference, KarirAlumniApprovalService $service): JsonResponse
    {
        $validated = $request->validate(['approver_core_user_id' => ['required', 'integer', 'min:1']]);
        $registration = $this->find($reference);
        $approver = $this->operationalApprover((int) $validated['approver_core_user_id']);
        $user = $service->approve($registration, $approver);

        return response()->json(['data' => [
            'reference' => $registration->reference,
            'status' => 'approved',
            'core_user_id' => (string) $user->id,
            'account_resolution' => $registration->fresh()->account_resolution,
        ]]);
    }

    public function reject(Request $request, string $reference, KarirAlumniRejectionService $service): JsonResponse
    {
        $validated = $request->validate([
            'approver_core_user_id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);
        $registration = $this->find($reference);
        $approver = $this->operationalApprover((int) $validated['approver_core_user_id']);
        $registration = $service->reject($registration, $approver, $validated['reason']);

        return response()->json(['data' => [
            'reference' => $registration->reference,
            'status' => $registration->status,
        ]]);
    }

    private function find(string $reference): CareerAlumniRegistration
    {
        return CareerAlumniRegistration::query()->where('reference', $reference)->firstOrFail();
    }

    private function operationalApprover(int $userId): User
    {
        $user = User::findOrFail($userId);
        $hasAccess = app(KarirIdentityVerificationService::class)
            ->activeRoleSlugs($user)->contains('admin-karir');

        if (! $hasAccess) {
            throw new AuthorizationException('An active admin-karir access is required.');
        }

        return $user;
    }

    private function reviewPayload(CareerAlumniRegistration $registration): array
    {
        return [
            'reference' => $registration->reference,
            'student_number' => $registration->student_number,
            'full_name' => $registration->full_name,
            'claimed_program' => $registration->claimed_program,
            'graduation_year' => $registration->graduation_year,
            'personal_email' => $registration->personal_email,
            'whatsapp' => $registration->whatsapp,
            'status' => $registration->status,
            'conflict_code' => $registration->conflict_code,
            'account_resolution' => $registration->account_resolution,
            'review_note' => $registration->review_note,
            'created_at' => $registration->created_at?->toIso8601String(),
            'reviewed_at' => $registration->reviewed_at?->toIso8601String(),
        ];
    }
}
