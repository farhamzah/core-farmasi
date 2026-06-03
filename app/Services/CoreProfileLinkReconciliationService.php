<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CoreProfileLinkReconciliationService
{
    public function __construct(
        protected CoreProfileUserProvisioningService $provisioning,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function preview(Model $profile, bool $backfillIdentifiers = false): array
    {
        $context = $this->context($profile);

        if (! $context) {
            return $this->result($profile, 'skip', 'unsupported_profile', null, null, [], []);
        }

        $messages = [];
        $updates = [];
        $targetUser = $this->canonicalUserFor($profile, $context);
        $currentUser = $profile->getAttribute('user_id') ? User::find($profile->getAttribute('user_id')) : null;

        if ($backfillIdentifiers && $profile instanceof Lecturer) {
            $identifierUpdates = $this->lecturerIdentifierBackfill($profile);
            $updates = [...$updates, ...$identifierUpdates['updates']];
            $messages = [...$messages, ...$identifierUpdates['messages']];
        }

        if ($targetUser && $this->targetHasDifferentProfile($profile, $targetUser)) {
            return $this->result($profile, 'blocker', 'target_user_already_has_profile', $currentUser?->id, $targetUser->id, $updates, $messages);
        }

        if ($currentUser && $targetUser && $currentUser->is($targetUser)) {
            return $this->result($profile, $updates === [] ? 'skip' : 'update', 'already_aligned', $currentUser->id, $targetUser->id, $updates, $messages);
        }

        if ($currentUser && $targetUser && ! $currentUser->is($targetUser)) {
            return $this->result($profile, 'relink', 'profile_email_matches_different_user', $currentUser->id, $targetUser->id, ['user_id' => $targetUser->id, ...$updates], $messages);
        }

        if (! $currentUser && $targetUser) {
            return $this->result($profile, 'link', 'matching_user_found', null, $targetUser->id, ['user_id' => $targetUser->id, ...$updates], $messages);
        }

        if (! $currentUser) {
            $preview = $this->provisioning->previewFor($profile);

            return $this->result($profile, $preview['action'], $preview['reason'], null, $preview['user_id'], $updates, $messages);
        }

        return $this->result($profile, $updates === [] ? 'skip' : 'update', 'linked_user_has_no_better_canonical_match', $currentUser->id, null, $updates, $messages);
    }

    /**
     * @return array<string, mixed>
     */
    public function apply(Model $profile, bool $backfillIdentifiers = false): array
    {
        $preview = $this->preview($profile, $backfillIdentifiers);

        if (! in_array($preview['action'], ['relink', 'link', 'update', 'create'], true)) {
            return $preview;
        }

        if ($preview['action'] === 'create') {
            $user = $this->provisioning->provisionFor($profile);
            $preview['target_user_id'] = $user?->id;
            $preview['applied'] = (bool) $user;

            return $preview;
        }

        DB::transaction(function () use ($profile, &$preview): void {
            $profile->forceFill($preview['updates'])->saveQuietly();

            UserActivityLog::create([
                'user_id' => $preview['target_user_id'] ?? $preview['current_user_id'],
                'action' => 'profile.user_link_reconciled',
                'meta' => [
                    'source' => 'core_profile_link_reconciliation',
                    'profile_type' => $preview['type'],
                    'profile_id' => $preview['profile_id'],
                    'action' => $preview['action'],
                    'reason' => $preview['reason'],
                    'current_user_id' => $preview['current_user_id'],
                    'target_user_id' => $preview['target_user_id'],
                    'updated_fields' => array_keys($preview['updates']),
                ],
            ]);
        });

        $preview['applied'] = true;

        return $preview;
    }

    /**
     * @return array<string, string|null>|null
     */
    protected function context(Model $profile): ?array
    {
        if ($profile instanceof Student) {
            return [
                'type' => 'students',
                'identifier_column' => 'student_number',
                'identifier' => $profile->student_number,
                'name' => $profile->name,
                'email' => $profile->email,
            ];
        }

        if ($profile instanceof Lecturer) {
            return [
                'type' => 'lecturers',
                'identifier_column' => 'lecturer_number',
                'identifier' => $profile->lecturer_number,
                'name' => $profile->name,
                'email' => $profile->email,
            ];
        }

        if ($profile instanceof Employee) {
            return [
                'type' => 'employees',
                'identifier_column' => 'employee_number',
                'identifier' => $profile->employee_number,
                'name' => $profile->name,
                'email' => $profile->email,
            ];
        }

        return null;
    }

    protected function canonicalUserFor(Model $profile, array $context): ?User
    {
        if (filled($context['email'] ?? null)) {
            $user = User::where('email', $context['email'])->first();

            if ($user) {
                return $user;
            }
        }

        if (filled($context['identifier'] ?? null)) {
            $user = User::query()
                ->where('username', $context['identifier'])
                ->orWhere(function ($query) use ($profile, $context): void {
                    $query
                        ->where('identity_type', $this->identityTypeFor($profile))
                        ->where('identity_number', $context['identifier']);
                })
                ->first();

            if ($user) {
                return $user;
            }
        }

        return null;
    }

    protected function targetHasDifferentProfile(Model $profile, User $targetUser): bool
    {
        $relation = match (true) {
            $profile instanceof Student => 'student',
            $profile instanceof Lecturer => 'lecturer',
            $profile instanceof Employee => 'employee',
            default => null,
        };

        if (! $relation) {
            return false;
        }

        $targetUser->loadMissing($relation);
        $existing = $targetUser->{$relation};

        return $existing && ! $existing->is($profile);
    }

    /**
     * @return array{updates: array<string, string>, messages: array<int, string>}
     */
    protected function lecturerIdentifierBackfill(Lecturer $lecturer): array
    {
        $updates = [];
        $messages = [];
        $number = preg_replace('/[^0-9]/', '', (string) $lecturer->lecturer_number);

        if ($number === '') {
            return ['updates' => [], 'messages' => ['lecturer_number tidak bisa dibaca sebagai angka.']];
        }

        if (blank($lecturer->nidn) && strlen($number) === 10) {
            $updates['nidn'] = $lecturer->lecturer_number;
        } elseif (blank($lecturer->nip) && strlen($number) === 18) {
            $updates['nip'] = $lecturer->lecturer_number;
        } elseif (blank($lecturer->nuptk) && strlen($number) === 16) {
            $updates['nuptk'] = $lecturer->lecturer_number;
        } elseif (blank($lecturer->nidn) && blank($lecturer->nip) && blank($lecturer->nuptk)) {
            $messages[] = 'lecturer_number tidak cukup jelas untuk otomatis dipetakan ke NIDN/NIP/NUPTK.';
        }

        return ['updates' => $updates, 'messages' => $messages];
    }

    protected function identityTypeFor(Model $profile): string
    {
        return match (true) {
            $profile instanceof Student => 'student',
            $profile instanceof Lecturer => 'lecturer',
            $profile instanceof Employee => 'employee',
            default => 'internal',
        };
    }

    /**
     * @param  array<string, mixed>  $updates
     * @param  array<int, string>  $messages
     * @return array<string, mixed>
     */
    protected function result(Model $profile, string $action, string $reason, ?int $currentUserId, ?int $targetUserId, array $updates, array $messages): array
    {
        $context = $this->context($profile) ?? [];

        return [
            'type' => $context['type'] ?? class_basename($profile),
            'profile_id' => $profile->getKey(),
            'identifier' => $context['identifier'] ?? null,
            'name' => $context['name'] ?? null,
            'email' => $context['email'] ?? null,
            'action' => $action,
            'reason' => $reason,
            'current_user_id' => $currentUserId,
            'target_user_id' => $targetUserId,
            'updates' => $updates,
            'messages' => $messages,
            'applied' => false,
        ];
    }
}
