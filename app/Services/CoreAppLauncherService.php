<?php

namespace App\Services;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\User;
use App\Models\UserAppAccess;
use Illuminate\Support\Collection;

class CoreAppLauncherService
{
    public function appsForUser(User $user): array
    {
        $accesses = UserAppAccess::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->where('app_code', '!=', 'core-farmasi')
            ->get();

        if ($accesses->isEmpty()) {
            return [];
        }

        $appCodes = $accesses->pluck('app_code')->filter()->unique()->values();
        $applications = CoreApplication::query()
            ->active()
            ->whereIn('app_code', $appCodes)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->keyBy('app_code');

        if ($applications->isEmpty()) {
            return [];
        }

        $roles = CoreApplicationRole::query()
            ->active()
            ->whereIn('app_code', $applications->keys())
            ->whereIn('role_slug', $accesses->pluck('role_slug')->filter()->unique())
            ->get()
            ->keyBy(fn (CoreApplicationRole $role): string => $role->app_code . '|' . $role->role_slug);

        return $applications
            ->map(fn (CoreApplication $application): array => $this->cardForApplication(
                $application,
                $accesses->where('app_code', $application->app_code),
                $roles,
            ))
            ->values()
            ->all();
    }

    protected function cardForApplication(CoreApplication $application, Collection $accesses, Collection $roles): array
    {
        $url = $this->resolveUrl($application);

        return [
            'app_code' => $application->app_code,
            'name' => $application->name,
            'description' => $application->description,
            'url' => $url,
            'roles' => $this->resolveRoles($accesses, $roles),
            'icon' => $application->icon,
            'color' => $application->color,
            'is_sensitive' => $application->is_sensitive,
            'requires_login' => $application->requires_login,
            'is_disabled' => blank($url),
            'disabled_reason' => blank($url) ? 'URL aplikasi belum dikonfigurasi.' : null,
        ];
    }

    protected function resolveUrl(CoreApplication $application): ?string
    {
        return filled($application->admin_url)
            ? $application->admin_url
            : ($application->base_url ?: null);
    }

    protected function resolveRoles(Collection $accesses, Collection $roles): array
    {
        return $accesses
            ->map(function (UserAppAccess $access) use ($roles): array {
                $role = filled($access->role_slug)
                    ? $roles->get($access->app_code . '|' . $access->role_slug)
                    : null;

                return [
                    'slug' => $access->role_slug,
                    'name' => $role?->role_name ?: $access->role_slug,
                ];
            })
            ->filter(fn (array $role): bool => filled($role['slug']))
            ->unique('slug')
            ->values()
            ->all();
    }
}
