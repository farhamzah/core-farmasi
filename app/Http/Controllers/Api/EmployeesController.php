<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\CoreApiResponseSanitizer;
use Illuminate\Http\Request;

class EmployeesController extends Controller
{
    public function __construct(protected CoreApiResponseSanitizer $sanitizer)
    {
    }

    public function show(Request $request, int $id)
    {
        $employee = Employee::with(['department', 'studyProgram'])->findOrFail($id);

        return response()->json($this->sanitizer->employee($employee));
    }
}
