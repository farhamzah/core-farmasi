<?php

namespace App\Services;

use App\Models\CoreApiClient;
use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\UserAppAccess;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;

class AppConnectionReadinessService
{
    public function supportedAppCodes(): array
    {
        return array_keys($this->appConfigs());
    }

    public function requiredAbilities(string $appCode): array
    {
        return $this->configFor($appCode)['abilities'];
    }

    public function requiredRoleSlugs(string $appCode): array
    {
        return $this->configFor($appCode)['roles'];
    }

    public function requiredEndpoints(string $appCode): array
    {
        return [
            'GET api/v1/internal/directory/users',
            'GET api/v1/internal/directory/students',
            'GET api/v1/internal/directory/lecturers',
            'GET api/v1/internal/directory/employees',
            'GET api/v1/internal/directory/study-programs',
            'GET api/v1/internal/directory/departments',
            'GET api/v1/internal/leadership/current',
            'GET api/v1/internal/apps/{app_code}/users/{user}/access',
        ];
    }

    public function checkApplication(string $appCode): array
    {
        $this->configFor($appCode);

        $application = CoreApplication::query()
            ->where('app_code', $appCode)
            ->first();

        return [
            'exists' => $application !== null,
            'is_active' => (bool) $application?->is_active,
            'is_public_visible' => (bool) $application?->is_public_visible,
            'requires_login' => (bool) $application?->requires_login,
            'name' => $application?->name,
        ];
    }

    public function checkApplicationRoles(string $appCode): array
    {
        $required = $this->requiredRoleSlugs($appCode);
        $present = CoreApplicationRole::query()
            ->where('app_code', $appCode)
            ->where('is_active', true)
            ->whereIn('role_slug', $required)
            ->pluck('role_slug')
            ->all();

        $missing = array_values(array_diff($required, $present));

        return [
            'required' => $required,
            'present' => array_values($present),
            'missing' => $missing,
            'complete' => $missing === [],
        ];
    }

    public function checkApiClients(string $appCode): array
    {
        $required = $this->requiredAbilities($appCode);
        $clients = CoreApiClient::query()
            ->where('app_code', $appCode)
            ->active()
            ->get(['id', 'name', 'app_code', 'client_id', 'abilities', 'is_active', 'revoked_at']);

        $summaries = $clients->map(function (CoreApiClient $client) use ($required): array {
            $abilities = $client->abilities ?: [];
            $hasWildcard = in_array('*', $abilities, true);
            $missing = $hasWildcard ? [] : array_values(array_diff($required, $abilities));

            return [
                'name' => $client->name,
                'client_id_hint' => $this->maskClientId((string) $client->client_id),
                'abilities_count' => count($abilities),
                'has_wildcard' => $hasWildcard,
                'missing_abilities' => $missing,
                'complete' => $missing === [],
            ];
        })->values()->all();

        return [
            'active_count' => $clients->count(),
            'required_abilities' => $required,
            'clients' => $summaries,
            'has_complete_client' => collect($summaries)->contains(fn (array $client): bool => $client['complete']),
        ];
    }

    public function checkUserAppAccessSummary(string $appCode): array
    {
        $this->configFor($appCode);

        return [
            'active_count' => UserAppAccess::query()
                ->where('app_code', $appCode)
                ->where('is_active', true)
                ->count(),
            'total_count' => UserAppAccess::query()
                ->where('app_code', $appCode)
                ->count(),
        ];
    }

    public function checkEndpointRegistry(string $appCode): array
    {
        $registered = collect(Route::getRoutes())->map(function ($route): string {
            return implode('|', $route->methods()).' '.$route->uri();
        });

        $endpoints = collect($this->requiredEndpoints($appCode))->mapWithKeys(function (string $endpoint) use ($registered): array {
            [$method, $uri] = explode(' ', $endpoint, 2);
            $found = $registered->contains(fn (string $route): bool => str_contains($route, $method) && str_ends_with($route, $uri));

            return [$endpoint => $found];
        })->all();

        $profileRouteAvailable = Route::has('profile.show');

        return [
            'endpoints' => $endpoints,
            'missing' => array_keys(array_filter($endpoints, fn (bool $found): bool => ! $found)),
            'complete' => ! in_array(false, $endpoints, true),
            'profile_route_available' => $profileRouteAvailable,
            'profile_path' => $profileRouteAvailable ? '/profile' : null,
        ];
    }

    public function readinessSummary(string $appCode): array
    {
        $application = $this->checkApplication($appCode);
        $roles = $this->checkApplicationRoles($appCode);
        $apiClients = $this->checkApiClients($appCode);
        $appAccess = $this->checkUserAppAccessSummary($appCode);
        $endpoints = $this->checkEndpointRegistry($appCode);

        return [
            'app_code' => $appCode,
            'application' => $application,
            'roles' => $roles,
            'api_clients' => $apiClients,
            'user_app_access' => $appAccess,
            'endpoints' => $endpoints,
            'verdict' => $this->verdict($application, $roles, $apiClients, $endpoints),
        ];
    }

    public function appDisplayName(string $appCode): string
    {
        return $this->configFor($appCode)['name'];
    }

    protected function verdict(array $application, array $roles, array $apiClients, array $endpoints): string
    {
        if (! $application['exists']) {
            return 'missing_application';
        }

        if (! $roles['complete']) {
            return 'missing_roles';
        }

        if ($apiClients['active_count'] < 1 || ! $apiClients['has_complete_client']) {
            return 'missing_api_client';
        }

        if (! $application['is_active'] || $application['is_public_visible'] || ! $endpoints['complete'] || ! $endpoints['profile_route_available']) {
            return 'not_ready';
        }

        return 'ready_for_staging_config';
    }

    protected function configFor(string $appCode): array
    {
        $configs = $this->appConfigs();

        if (! isset($configs[$appCode])) {
            throw new InvalidArgumentException("Unsupported app code [{$appCode}].");
        }

        return $configs[$appCode];
    }

    protected function appConfigs(): array
    {
        $standardReadAbilities = [
            'read:users',
            'read:students',
            'read:lecturers',
            'read:employees',
            'read:study-programs',
            'read:departments',
            'read:app-access',
            'read:leadership',
        ];

        return [
            'ta-farmasi' => [
                'name' => 'TA Farmasi',
                'abilities' => $standardReadAbilities,
                'roles' => [
                    'mahasiswa',
                    'dosen',
                    'dosen-pembimbing',
                    'penguji',
                    'koordinator-ta',
                    'admin-ta',
                    'kaprodi',
                    'dekan',
                    'validator',
                ],
            ],
            'lab-farmasi' => [
                'name' => 'Lab Farmasi',
                'abilities' => $standardReadAbilities,
                'roles' => [
                    'mahasiswa',
                    'dosen',
                    'laboran',
                    'kepala-lab',
                    'admin-lab',
                    'pengguna-lab',
                    'peminjam-alat',
                    'teknisi',
                    'viewer',
                ],
            ],
            'helpdesk-farmasi' => [
                'name' => 'Helpdesk Farmasi',
                'abilities' => $standardReadAbilities,
                'roles' => [
                    'requester',
                    'agent',
                    'admin-helpdesk',
                    'teknisi',
                    'supervisor',
                    'viewer',
                ],
            ],
        ];
    }

    protected function maskClientId(string $clientId): string
    {
        if ($clientId === '') {
            return '';
        }

        return substr($clientId, 0, 8).'...'.substr($clientId, -4);
    }
}
