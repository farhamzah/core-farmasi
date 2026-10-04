<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Karir\KarirIdentityVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KarirIdentityVerificationController extends Controller
{
    public function verify(Request $request, KarirIdentityVerificationService $service): JsonResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $user = $service->findUser(trim($data['identifier']));

        if (! $service->credentialsAreValid($user, $data['password'])) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $principal = $service->principal($user);
        } catch (\RuntimeException) {
            return response()->json(['message' => 'Identity service unavailable'], 503);
        }

        if ($principal === null) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(['principal' => $principal]);
    }
}
