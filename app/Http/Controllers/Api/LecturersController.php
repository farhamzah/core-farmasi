<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lecturer;
use App\Services\CoreApiResponseSanitizer;
use Illuminate\Http\Request;

class LecturersController extends Controller
{
    public function __construct(protected CoreApiResponseSanitizer $sanitizer)
    {
    }

    public function show(Request $request, int $id)
    {
        $lecturer = Lecturer::with(['department', 'studyProgram'])->findOrFail($id);

        return response()->json($this->sanitizer->lecturer($lecturer));
    }
}
