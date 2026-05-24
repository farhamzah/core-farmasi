<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CoreApiAccessService;
use Illuminate\Http\Request;

class InternalAppAccessController extends Controller
{
    public function __construct(protected CoreApiAccessService $accessService)
    {
    }

    public function show(Request $request, string $appCode, User $user)
    {
        return response()->json($this->accessService->userAccessForApp($user, $appCode));
    }
}
