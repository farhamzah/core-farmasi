<?php

namespace App\Services;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CoreApiAccessService
{
    public function __construct(private readonly CoreApiResponseSanitizer $sanitizer)
    {
    }

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

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function usersForApp(string $appCode, array $filters = []): array
    {
        $limit = $this->limit($filters['limit'] ?? null);
        $page = max(1, (int) ($filters['page'] ?? 1));
        $role = filled($filters['role'] ?? null) ? (string) $filters['role'] : null;

        $application = CoreApplication::query()
            ->active()
            ->where('app_code', $appCode)
            ->first();

        if (! $application) {
            return $this->emptyUserList($appCode, $limit, $page);
        }

        $roleNames = CoreApplicationRole::query()
            ->where('app_code', $appCode)
            ->pluck('role_name', 'role_slug');

        $query = User::query()
            ->with([
                'roles',
                'student.studyProgram.department',
                'student.studyProgram.faculty',
                'lecturer.department',
                'lecturer.studyProgram',
                'externalPerson',
                'appAccesses' => fn ($query) => $query
                    ->where('app_code', $appCode)
                    ->where('is_active', true)
                    ->when($role, fn ($query) => $query->where('role_slug', $role)),
            ])
            ->whereHas('appAccesses', function (Builder $query) use ($appCode, $role): void {
                $query
                    ->where('app_code', $appCode)
                    ->where('is_active', true)
                    ->when($role, fn (Builder $query) => $query->where('role_slug', $role));
            })
            ->when($filters['user_id'] ?? null, fn (Builder $query, int|string $userId) => $query->whereKey((int) $userId))
            ->when($filters['q'] ?? null, fn (Builder $query, string $q) => $query->where(function (Builder $query) use ($q): void {
                $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($q)) . '%';
                $query
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('username', 'like', $like)
                    ->orWhere('identity_number', 'like', $like);
            }))
            ->when(array_key_exists('active', $filters), fn (Builder $query) => $query->where('active', filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN)))
            ->orderBy('name');

        $total = (clone $query)->count();
        $users = $query
            ->forPage($page, $limit)
            ->get()
            ->map(fn (User $user): array => $this->appUserPayload($user, $appCode, $roleNames))
            ->values()
            ->all();

        return [
            'data' => $users,
            'meta' => [
                'app_code' => $appCode,
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'has_more' => ($page * $limit) < $total,
            ],
        ];
    }

    private function appUserPayload(User $user, string $appCode, $roleNames): array
    {
        $accessRoles = $user->appAccesses
            ->map(fn ($access): array => [
                'slug' => $access->role_slug,
                'name' => $roleNames[$access->role_slug] ?? $access->role_slug,
            ])
            ->filter(fn (array $role): bool => filled($role['slug']))
            ->values()
            ->all();

        return [
            'user_id' => $user->id,
            'app_code' => $appCode,
            'roles' => $accessRoles,
            'user' => $this->sanitizer->user($user, includeAppAccesses: false),
            'profiles' => [
                'student' => $user->student ? $this->sanitizer->student($user->student) : null,
                'lecturer' => $user->lecturer ? $this->sanitizer->lecturer($user->lecturer) : null,
                'external_person' => $user->externalPerson ? $this->sanitizer->externalPerson($user->externalPerson) : null,
            ],
        ];
    }

    private function limit(mixed $value): int
    {
        $default = max(1, (int) config('core_api.directory_default_limit', 25));
        $max = max($default, (int) config('core_api.directory_max_limit', 100));

        return min(max(1, (int) ($value ?: $default)), $max);
    }

    private function emptyUserList(string $appCode, int $limit, int $page): array
    {
        return [
            'data' => [],
            'meta' => [
                'app_code' => $appCode,
                'page' => $page,
                'limit' => $limit,
                'total' => 0,
                'has_more' => false,
            ],
        ];
    }
}
