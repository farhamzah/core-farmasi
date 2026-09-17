<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Karir\KarirAlumniRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KarirAlumniRegistrationController extends Controller
{
    public function store(Request $request, KarirAlumniRegistrationService $service): JsonResponse
    {
        $data = $request->validate([
            'student_number' => ['required', 'string', 'max:50'],
            'full_name' => ['required', 'string', 'max:255'],
            'claimed_program' => ['required', 'string', 'max:255'],
            'graduation_year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'personal_email' => ['required', 'email', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $registration = $service->register($data);

        return response()->json([
            'data' => [
                'reference' => $registration->reference,
                'status' => $registration->status,
            ],
        ], $registration->wasRecentlyCreated ? 201 : 200);
    }
}
