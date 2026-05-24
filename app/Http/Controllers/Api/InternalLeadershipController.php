<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CoreApiResponseSanitizer;
use App\Services\CoreLeadershipResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InternalLeadershipController extends Controller
{
    public function __construct(
        protected CoreLeadershipResolver $resolver,
        protected CoreApiResponseSanitizer $sanitizer,
    ) {
    }

    public function current(Request $request)
    {
        $data = $request->validate([
            'position_type' => ['required', 'string', Rule::in(array_keys(config('core_leadership.position_types', [])))],
            'unit_type' => ['nullable', 'string', Rule::in(array_keys(config('core_leadership.unit_types', [])))],
            'unit_id' => ['nullable', 'integer'],
            'date' => ['nullable', 'date'],
        ]);

        $assignment = $this->resolver->getCurrentPosition(
            $data['position_type'],
            $data['unit_type'] ?? null,
            $data['unit_id'] ?? null,
            $data['date'] ?? null,
        );

        return response()->json([
            'found' => $assignment !== null,
            'leadership' => $this->sanitizer->leadership($assignment),
        ]);
    }
}
