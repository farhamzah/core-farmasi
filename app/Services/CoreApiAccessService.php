<?php

namespace App\Services;

use App\Models\CoreApplication;
use App\Models\User;

class CoreApiAccessService
{
    public function canInspectUserAccess(User $requestingUser, User $targetUser): bool
    {
        return $requestingUser->is($targetUser)
            || $requestingUser->roles()
                ->whereIn('name', ['super-admin', 'admin-core'])
                ->where('active', true)
                ->exists();
    }

    public function userAccessForApp(User $targetUser, string $appCode): array
    {
        $application = CoreApplication::query()
            ->active()
            ->where('app_code', $appCode)
            ->first();

        if (! $application) {
            return [
                'has_access' => false,
                'app_code' => $appCode,
                'user_id' => $targetUser->id,
                'roles' => [],
            ];
        }

        $accesses = $targetUser->appAccesses()
            ->where('app_code', $appCode)
            ->where('is_active', true)
            ->get();

        return [
            'has_access' => $accesses->isNotEmpty(),
            'app_code' => $appCode,
            'user_id' => $targetUser->id,
            'roles' => $accesses
                ->map(fn ($access): array => [
                    'slug' => $access->role_slug,
                    'name' => $access->applicationRoleName ?: $access->role_slug,
                ])
                ->filter(fn (array $role): bool => filled($role['slug']))
                ->values()
                ->all(),
        ];
    }
}
