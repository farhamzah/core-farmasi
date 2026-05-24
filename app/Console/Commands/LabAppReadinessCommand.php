<?php

namespace App\Console\Commands;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\UserAppAccess;
use Illuminate\Console\Command;

class LabAppReadinessCommand extends Command
{
    protected $signature = 'core:lab-app-readiness';

    protected $description = 'Check Core readiness for Lab Farmasi app registry and role catalog without exposing secrets.';

    public function handle(): int
    {
        $appCode = 'lab-farmasi';
        $requiredRoles = [
            'lab-admin',
            'lab-koordinator',
            'lab-kepala-lab',
            'lab-laboran',
            'lab-dosen',
            'lab-asisten',
            'lab-mahasiswa',
            'lab-teknisi',
            'lab-viewer',
        ];

        $applications = CoreApplication::query()->where('app_code', $appCode)->get();
        $application = $applications->first();
        $roles = CoreApplicationRole::query()
            ->where('app_code', $appCode)
            ->where('is_active', true)
            ->pluck('role_slug')
            ->all();
        $missingRoles = array_values(array_diff($requiredRoles, $roles));
        $activeAccessCount = UserAppAccess::query()
            ->where('app_code', $appCode)
            ->where('is_active', true)
            ->count();

        $verdict = $this->verdict(
            duplicateCount: $applications->count(),
            applicationActive: (bool) $application?->is_active,
            missingRoles: $missingRoles,
        );

        $this->line('Core Lab Farmasi app readiness');
        $this->table(
            ['Metric', 'Value'],
            [
                ['App code', $appCode],
                ['Application registered', $this->yesNo($application !== null)],
                ['Duplicate app code count', (string) max(0, $applications->count() - 1)],
                ['Application active', $this->yesNo((bool) $application?->is_active)],
                ['Application public visible', $this->yesNo((bool) $application?->is_public_visible)],
                ['Requires login', $this->yesNo((bool) $application?->requires_login)],
                ['Required roles missing', $missingRoles === [] ? '-' : implode(', ', $missingRoles)],
                ['Active user app access count', (string) $activeAccessCount],
                ['Readiness verdict', $verdict],
            ],
        );

        $this->line('Guardrails: no user access auto-grant, no SSO, no token URL, no credential output.');

        return self::SUCCESS;
    }

    protected function yesNo(bool $value): string
    {
        return $value ? 'yes' : 'no';
    }

    /**
     * @param  array<int, string>  $missingRoles
     */
    protected function verdict(int $duplicateCount, bool $applicationActive, array $missingRoles): string
    {
        if ($duplicateCount !== 1) {
            return 'warning_duplicate_or_missing_application';
        }

        if (! $applicationActive) {
            return 'warning_inactive_application';
        }

        if ($missingRoles !== []) {
            return 'warning_missing_roles';
        }

        return 'ready';
    }
}
