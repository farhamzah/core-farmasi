<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = User::verifyApiToken($token);

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        Auth::setUser($user);

        return $next($request);
    }
}
