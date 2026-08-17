<?php

namespace App\Http\Controllers;

use App\Models\AccountRequest;
use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\Department;
use App\Models\StudyProgram;
use App\Services\CoreAccountRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountRequestController extends Controller
{
    public function create(): View
    {
        if (! config('core_account.public_account_request_enabled', false)) {
            return view('account-request.disabled');
        }

        return view('account-request.create', [
            'requestTypes' => AccountRequest::typeOptions(),
            'studyPrograms' => StudyProgram::query()->where('active', true)->orderBy('name')->pluck('name', 'id')->all(),
            'departments' => Department::query()->where('active', true)->orderBy('name')->pluck('name', 'id')->all(),
            'applications' => CoreApplication::query()
                ->active()
                ->orderBy('name')
                ->get(['app_code', 'name', 'description'])
                ->mapWithKeys(fn (CoreApplication $application): array => [
                    $application->app_code => $this->accountRequestApplicationLabel($application),
                ])
                ->all(),
            'applicationRoles' => CoreApplicationRole::query()
                ->active()
                ->orderBy('app_code')
                ->orderBy('sort_order')
                ->orderBy('role_name')
                ->get(['app_code', 'role_slug', 'role_name'])
                ->groupBy('app_code')
                ->map(fn ($roles) => $roles->map(fn (CoreApplicationRole $role): array => [
                    'slug' => $role->role_slug,
                    'name' => $role->role_name,
                ])->values()->all())
                ->all(),
        ]);
    }

    public function store(Request $request, CoreAccountRequestService $requests): RedirectResponse
    {
        abort_unless(config('core_account.public_account_request_enabled', false), 403);

        $validated = $request->validate([
            'request_type' => ['required', 'string', Rule::in(array_keys(AccountRequest::typeOptions()))],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'required_if:request_type,'.AccountRequest::TYPE_FIELD_SUPERVISOR, 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:5000'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:50'],
            'identity_number' => ['nullable', 'string', 'max:255'],
            'student_number' => ['nullable', 'required_if:request_type,'.AccountRequest::TYPE_STUDENT, 'string', 'max:255'],
            'lecturer_number' => ['nullable', 'required_if:request_type,'.AccountRequest::TYPE_LECTURER, 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:255'],
            'nidn' => ['nullable', 'string', 'max:255'],
            'nidk' => ['nullable', 'string', 'max:255'],
            'nuptk' => ['nullable', 'string', 'max:255'],
            'employee_number' => ['nullable', 'required_if:request_type,'.AccountRequest::TYPE_EMPLOYEE, 'string', 'max:255'],
            'staff_type' => ['nullable', 'required_if:request_type,'.AccountRequest::TYPE_EMPLOYEE, 'string', 'max:255'],
            'position_title' => ['nullable', 'string', 'max:255'],
            'institution_name' => ['nullable', 'required_if:request_type,'.AccountRequest::TYPE_FIELD_SUPERVISOR, 'string', 'max:255'],
            'institution_type' => ['nullable', 'string', Rule::in(array_keys(AccountRequest::externalInstitutionTypeOptions()))],
            'profession' => ['nullable', 'string', 'max:255'],
            'study_program_id' => ['nullable', 'integer', 'exists:study_programs,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'requested_role' => ['nullable', 'string', 'max:255'],
            'requested_app_code' => ['required', 'string', 'max:255', 'exists:core_applications,app_code'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $requests->submit($validated, $request);

        return redirect()->route('account-request.success');
    }

    public function success(): View
    {
        return view('account-request.success');
    }

    private function accountRequestApplicationLabel(CoreApplication $application): string
    {
        return match ($application->app_code) {
            'core-farmasi' => 'Core Farmasi - Profil dan akun pusat',
            'helpdesk-farmasi' => 'Helpdesk Farmasi - Bantuan layanan',
            'kp-farmasi' => 'KP Farmasi - Kerja praktek S1 Farmasi',
            'kppspa-farmasi' => 'MY PKPA - Praktik Kerja Profesi Apoteker',
            'lab-farmasi' => 'Lab Farmasi - Praktikum dan laboratorium',
            'obe-farmasi' => 'OBE Farmasi - Kurikulum dan capaian pembelajaran',
            'safa-ubp' => 'SAFA UBP - Portal satu akses Farmasi',
            'ta-farmasi' => 'TA Farmasi - Tugas akhir',
            'tu-farmasi' => 'TU Farmasi - Layanan tata usaha',
            default => $application->name.($application->description
                ? ' - '.Str::limit(strip_tags($application->description), 70, '')
                : ''),
        };
    }
}
