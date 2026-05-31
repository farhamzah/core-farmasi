<?php

namespace App\Console\Commands;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\UserAppAccess;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TaAppReadinessCommand extends Command
{
    protected $signature = 'core:ta-app-readiness';

    protected $description = 'Check Core readiness for TA Farmasi app registry and role catalog without creating access.';

    /**
     * @var array<int, string>
     */
    private array $requiredRoles = [
        'mahasiswa',
        'dosen',
        'dosen-pembimbing',
        'penguji',
        'koordinator-ta',
        'admin-ta',
        'kaprodi',
        'dekan',
        'validator',
    ];

    public function handle(): int
    {
        $appCode = 'ta-farmasi';
        $applications = CoreApplication::query()->where('app_code', $appCode)->get();
        $application = $applications->first();
        $roles = CoreApplicationRole::query()
            ->where('app_code', $appCode)
            ->where('is_active', true)
            ->pluck('role_slug')
            ->all();
        $missingRoles = array_values(array_diff($this->requiredRoles, $roles));
        $activeAccessCount = UserAppAccess::query()
            ->where('app_code', $appCode)
            ->where('is_active', true)
            ->count();
        $unsafeUrl = $this->hasUnsafeUrl($application?->base_url) || $this->hasUnsafeUrl($application?->admin_url);
        $verdict = $this->verdict(
            applicationCount: $applications->count(),
            applicationActive: (bool) $application?->is_active,
            missingRoles: $missingRoles,
            unsafeUrl: $unsafeUrl,
        );

        $this->line('Core TA Farmasi app readiness');
        $this->table(
            ['Metric', 'Value'],
            [
                ['App code', $appCode],
                ['Application registered', $this->yesNo($application !== null)],
                ['Duplicate app code count', (string) max(0, $applications->count() - 1)],
                ['Application active', $this->yesNo((bool) $application?->is_active)],
                ['Application public visible', $this->yesNo((bool) $application?->is_public_visible)],
                ['Requires login', $this->yesNo((bool) $application?->requires_login)],
                ['Base URL configured', $this->yesNo(filled($application?->base_url))],
                ['Admin URL configured', $this->yesNo(filled($application?->admin_url))],
                ['Unsafe token/autologin URL', $this->yesNo($unsafeUrl)],
                ['Required roles missing', $missingRoles === [] ? '-' : implode(', ', $missingRoles)],
                ['Active user app access count', (string) $activeAccessCount],
                ['Readiness verdict', $verdict],
            ],
        );

        $this->line('Guardrails: registry and role catalog only; no user access auto-grant, no SSO, no token URL, no credential output.');

        return self::SUCCESS;
    }

    protected function yesNo(bool $value): string
    {
        return $value ? 'yes' : 'no';
    }

    protected function hasUnsafeUrl(?string $url): bool
    {
        if (blank($url)) {
            return false;
        }

        return Str::contains(Str::lower($url), [
            'token=',
            'client_secret',
            'password=',
            'autologin',
            'auto-login',
            'sso_token',
        ]);
    }

    /**
     * @param  array<int, string>  $missingRoles
     */
    protected function verdict(int $applicationCount, bool $applicationActive, array $missingRoles, bool $unsafeUrl): string
    {
        if ($applicationCount !== 1) {
            return 'warning_duplicate_or_missing_application';
        }

        if (! $applicationActive) {
            return 'warning_inactive_application';
        }

        if ($missingRoles !== []) {
            return 'warning_missing_roles';
        }

        if ($unsafeUrl) {
            return 'warning_unsafe_url';
        }

        return 'ready';
    }
}
