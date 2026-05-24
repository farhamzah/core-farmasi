<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CoreApiResponseSanitizer;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function __construct(protected CoreApiResponseSanitizer $sanitizer)
    {
    }

    public function show(Request $request, int $id)
    {
        $user = User::with(['roles', 'appAccesses'])->findOrFail($id);

        return response()->json($this->sanitizer->user($user));
    }
}
