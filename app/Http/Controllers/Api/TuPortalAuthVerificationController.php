<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TuFarmasi\TuPortalPasswordVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TuPortalAuthVerificationController extends Controller
{
    public function verify(Request $request, TuPortalPasswordVerificationService $verification): JsonResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'context' => ['nullable', 'array'],
        ]);

        return response()->json($verification->verify(
            $validated['login'],
            $validated['password'],
            'tu-farmasi',
        ));
    }
}
