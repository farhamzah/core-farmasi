<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CoreApiResponseSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(protected CoreApiResponseSanitizer $sanitizer)
    {
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->where('active', true)->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        return response()->json([
            'token' => $user->generateApiToken(),
            'user' => $this->sanitizer->user($user->loadMissing('roles')),
        ]);
    }

    public function validateToken(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'valid' => true,
            'user' => $this->sanitizer->user($user->loadMissing('roles')),
        ]);
    }
}
