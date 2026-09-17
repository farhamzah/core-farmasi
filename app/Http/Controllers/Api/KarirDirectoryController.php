<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class KarirDirectoryController extends Controller
{
    public function person(User $user): JsonResponse
    {
        return response()->json(['data' => [
            'core_user_id' => (string) $user->id,
            'display_name' => $user->name,
        ]]);
    }

    public function studyProgram(StudyProgram $studyProgram): JsonResponse
    {
        return response()->json(['data' => [
            'id' => (string) $studyProgram->id,
            'code' => $studyProgram->code,
            'name' => $studyProgram->name,
        ]]);
    }
}
