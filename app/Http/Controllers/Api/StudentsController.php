<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\CoreApiResponseSanitizer;
use Illuminate\Http\Request;

class StudentsController extends Controller
{
    public function __construct(protected CoreApiResponseSanitizer $sanitizer)
    {
    }

    public function show(Request $request, int $id)
    {
        $student = Student::with('studyProgram.department')->findOrFail($id);

        return response()->json($this->sanitizer->student($student));
    }
}
