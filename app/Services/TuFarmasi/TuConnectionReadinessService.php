<?php

namespace App\Services\TuFarmasi;

use App\Models\CoreApiClient;
use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\UserAppAccess;
use Illuminate\Support\Facades\Route;

class TuConnectionReadinessService
{
    public function appCode(): string
    {
        return 'tu-farmasi';
    }

    public function requiredAbilities(): array
    {
        return [
            'read:users',
            'read:students',
            'read:lecturers',
            'read:employees',
            'read:study-programs',
            'read:departments',
            'read:app-access',
            'read:leadership',
            'verify:tu-portal-auth',
        ];
    }

    public function requiredRoleSlugs(): array
    {
        return [
            'admin-tu',
            'staf-tu',
            'tendik',
            'laboran',
            'dosen',
            'mahasiswa',
            'validator',
            'penandatangan',
        ];
    }

    public function requiredEndpoints(): array
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
            'POST api/v1/internal/apps/tu-farmasi/portal-auth/verify',
        ];
    }

    public function checkApplication(): array
    {
        $application = CoreApplication::query()
            ->where('app_code', $this->appCode())
            ->first();

        return [
            'exists' => $application !== null,
            'is_active' => (bool) $application?->is_active,
            'is_public_visible' => (bool) $application?->is_public_visible,
            'requires_login' => (bool) $application?->requires_login,
            'name' => $application?->name,
        ];
    }

    public function checkApplicationRoles(): array
    {
        $present = CoreApplicationRole::query()
            ->where('app_code', $this->appCode())
            ->where('is_active', true)
            ->whereIn('role_slug', $this->requiredRoleSlugs())
            ->pluck('role_slug')
            ->all();

        $missing = array_values(array_diff($this->requiredRoleSlugs(), $present));

        return [
            'required' => $this->requiredRoleSlugs(),
            'present' => array_values($present),
            'missing' => $missing,
            'complete' => $missing === [],
        ];
    }

    public function checkApiClients(): array
    {
        $clients = CoreApiClient::query()
            ->where('app_code', $this->appCode())
            ->active()
            ->get(['id', 'name', 'app_code', 'client_id', 'abilities', 'is_active', 'revoked_at']);

        $required = $this->requiredAbilities();

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

    public function checkUserAppAccessSummary(): array
    {
        return [
            'active_count' => UserAppAccess::query()
                ->where('app_code', $this->appCode())
                ->where('is_active', true)
                ->count(),
            'total_count' => UserAppAccess::query()
                ->where('app_code', $this->appCode())
                ->count(),
        ];
    }

    public function checkEndpointRegistry(): array
    {
        $registered = collect(Route::getRoutes())->map(function ($route): string {
            return implode('|', $route->methods()).' '.$route->uri();
        });

        $endpoints = collect($this->requiredEndpoints())->mapWithKeys(function (string $endpoint) use ($registered): array {
            [$method, $uri] = explode(' ', $endpoint, 2);
            $found = $registered->contains(fn (string $route): bool => str_contains($route, $method) && str_ends_with($route, $uri));

            return [$endpoint => $found];
        })->all();

        $profileRouteAvailable = Route::has('profile.show');

        return [
            'endpoints' => $endpoints,
            'missing' => array_keys(array_filter($endpoints, fn (bool $found): bool => ! $found)),
            'complete' => ! in_array(false, $endpoints, true),
            'portal_verify_endpoint_available' => (bool) ($endpoints['POST api/v1/internal/apps/tu-farmasi/portal-auth/verify'] ?? false),
            'profile_route_available' => $profileRouteAvailable,
            'profile_path' => $profileRouteAvailable ? '/profile' : null,
        ];
    }

    public function readinessSummary(): array
    {
        $application = $this->checkApplication();
        $roles = $this->checkApplicationRoles();
        $apiClients = $this->checkApiClients();
        $appAccess = $this->checkUserAppAccessSummary();
        $endpoints = $this->checkEndpointRegistry();

        return [
            'app_code' => $this->appCode(),
            'application' => $application,
            'roles' => $roles,
            'api_clients' => $apiClients,
            'user_app_access' => $appAccess,
            'endpoints' => $endpoints,
            'verdict' => $this->verdict($application, $roles, $apiClients, $endpoints),
        ];
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

    protected function maskClientId(string $clientId): string
    {
        if ($clientId === '') {
            return '';
        }

        return substr($clientId, 0, 8).'...'.substr($clientId, -4);
    }
}
