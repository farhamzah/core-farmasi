<?php

namespace App\Services;

use App\Models\LeadershipAssignment;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class CoreLeadershipResolver
{
    public function getCurrentDean(mixed $date = null): ?LeadershipAssignment
    {
        return $this->getCurrentPosition('dekan', 'faculty', null, $date);
    }

    public function getCurrentViceDean(mixed $date = null): ?LeadershipAssignment
    {
        return $this->getCurrentPosition('wakil_dekan', 'faculty', null, $date);
    }

    public function getCurrentHeadOfStudyProgram(int|string $studyProgramId, mixed $date = null): ?LeadershipAssignment
    {
        return $this->getCurrentPosition('kaprodi', 'study_program', $studyProgramId, $date);
    }

    public function getCurrentPosition(
        string $positionType,
        ?string $unitType = null,
        int|string|null $unitId = null,
        mixed $date = null,
    ): ?LeadershipAssignment {
        $date = $this->normalizeDate($date);

        return LeadershipAssignment::query()
            ->active()
            ->current($date)
            ->forPosition($positionType)
            ->when($unitType !== null, fn ($query) => $query->forUnit($unitType, $unitId))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();
    }

    private function normalizeDate(mixed $date): string
    {
        if ($date instanceof CarbonInterface) {
            return $date->toDateString();
        }

        return $date ? Carbon::parse($date)->toDateString() : now()->toDateString();
    }
}
