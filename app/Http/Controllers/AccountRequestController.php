<?php

namespace App\Http\Controllers;

use App\Models\AccountRequest;
use App\Models\CoreApplication;
use App\Models\Department;
use App\Models\StudyProgram;
use App\Services\CoreAccountRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'applications' => CoreApplication::query()->active()->orderBy('name')->pluck('name', 'app_code')->all(),
        ]);
    }

    public function store(Request $request, CoreAccountRequestService $requests): RedirectResponse
    {
        abort_unless(config('core_account.public_account_request_enabled', false), 403);

        $validated = $request->validate([
            'request_type' => ['required', 'string', Rule::in(array_keys(AccountRequest::typeOptions()))],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'identity_number' => ['nullable', 'string', 'max:255'],
            'student_number' => ['nullable', 'string', 'max:255'],
            'lecturer_number' => ['nullable', 'string', 'max:255'],
            'employee_number' => ['nullable', 'string', 'max:255'],
            'study_program_id' => ['nullable', 'integer', 'exists:study_programs,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'requested_role' => ['nullable', 'string', 'max:255'],
            'requested_app_code' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $requests->submit($validated, $request);

        return redirect()->route('account-request.success');
    }

    public function success(): View
    {
        return view('account-request.success');
    }
}
