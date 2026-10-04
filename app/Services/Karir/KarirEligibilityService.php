<?php

namespace App\Services\Karir;

use App\Models\CareerAlumniGrant;
use App\Models\User;

class KarirEligibilityService
{
    public function source(User $user): ?string
    {
        $programCode = $user->student?->studyProgram?->code;

        if ($programCode && in_array($programCode, config('core_karir.pharmacy_program_codes', []), true)) {
            return 'core_program';
        }

        return CareerAlumniGrant::query()
            ->where('user_id', $user->id)
            ->where('career_scope', 'farmasi')
            ->where('is_active', true)
            ->value('eligibility_source');
    }

    public function programIds(User $user): array
    {
        $id = $user->student?->study_program_id;

        return $id ? [(string) $id] : [];
    }
}
