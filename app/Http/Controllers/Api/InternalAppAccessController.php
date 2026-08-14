<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CoreApiAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InternalAppAccessController extends Controller
{
    public function __construct(protected CoreApiAccessService $accessService)
    {
    }

    public function index(Request $request, string $appCode)
    {
        $filters = Validator::make($request->query(), [
            'q' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'role' => ['nullable', 'string', 'max:100'],
            'active' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ])->validate();

        return response()->json($this->accessService->usersForApp($appCode, $filters));
    }

    public function show(Request $request, string $appCode, User $user)
    {
        return response()->json($this->accessService->userAccessForApp($user, $appCode));
    }
}
