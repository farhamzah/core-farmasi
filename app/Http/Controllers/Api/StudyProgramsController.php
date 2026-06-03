<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudyProgram;
use Illuminate\Http\Request;

class StudyProgramsController extends Controller
{
    public function index(Request $request)
    {
        return StudyProgram::with(['faculty', 'department'])
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (StudyProgram $program) => [
                'id' => $program->id,
                'code' => $program->code,
                'name' => $program->name,
                'faculty' => $program->faculty?->name,
                'department' => $program->department?->name,
            ]);
    }

    public function show(Request $request, int $id)
    {
        $program = StudyProgram::with(['faculty', 'department'])->findOrFail($id);

        return response()->json([
            'id' => $program->id,
            'code' => $program->code,
            'name' => $program->name,
            'description' => $program->description,
            'active' => $program->active,
            'faculty' => $program->faculty ? [
                'id' => $program->faculty->id,
                'code' => $program->faculty->code,
                'name' => $program->faculty->name,
            ] : null,
            'department' => $program->department ? [
                'id' => $program->department->id,
                'code' => $program->department->code,
                'name' => $program->department->name,
            ] : null,
        ]);
    }
}
