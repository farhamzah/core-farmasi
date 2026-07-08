<?php

namespace App\Console\Commands;

use App\Models\CoreApplicationRole;
use App\Models\UserAppAccess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeLabFarmasiRolesCommand extends Command
{
    protected $signature = 'core:normalize-lab-farmasi-roles {--apply : Apply changes. Without this flag, only shows the dry-run summary.}';

    protected $description = 'Normalize Lab Farmasi app access roles to the canonical role catalog.';

    /**
     * @var array<string, string>
     */
    protected array $roleMap = [
        'admin-lab' => 'admin_lab',
        'lab-admin' => 'admin_lab',
        'kepala-lab' => 'koordinator_lab',
        'lab-kepala-lab' => 'koordinator_lab',
        'lab-koordinator' => 'koordinator_lab',
        'lab-laboran' => 'laboran',
        'lab-teknisi' => 'teknisi',
        'lab-dosen' => 'dosen',
        'pengguna-lab' => 'mahasiswa',
        'peminjam-alat' => 'mahasiswa',
        'lab-asisten' => 'mahasiswa',
        'lab-mahasiswa' => 'mahasiswa',
        'lab-viewer' => 'viewer',
    ];

    /**
     * @var array<int, string>
     */
    protected array $canonicalRoles = [
        'admin_lab',
        'koordinator_lab',
        'laboran',
        'teknisi',
        'dosen',
        'mahasiswa',
        'viewer',
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->line('Core Lab Farmasi role normalization');
        $this->line('Mode: '.($apply ? 'APPLY' : 'DRY-RUN'));

        $rows = UserAppAccess::query()
            ->where('app_code', 'lab-farmasi')
            ->whereIn('role_slug', array_keys($this->roleMap))
            ->select('role_slug', DB::raw('COUNT(*) as total'))
            ->groupBy('role_slug')
            ->orderBy('role_slug')
            ->get();

        $this->table(
            ['Legacy role', 'Canonical role', 'User app access rows'],
            $rows->map(fn ($row): array => [
                $row->role_slug,
                $this->roleMap[$row->role_slug],
                (string) $row->total,
            ])->values()->all(),
        );

        $legacyCatalogRows = CoreApplicationRole::query()
            ->where('app_code', 'lab-farmasi')
            ->whereNotIn('role_slug', $this->canonicalRoles)
            ->where('is_active', true)
            ->count();

        $this->line('Active legacy catalog roles to deactivate: '.$legacyCatalogRows);

        if (! $apply) {
            $this->line('No data changed. Re-run with --apply after backup/review.');

            return self::SUCCESS;
        }

        DB::transaction(function (): void {
            UserAppAccess::query()
                ->where('app_code', 'lab-farmasi')
                ->whereIn('role_slug', array_keys($this->roleMap))
                ->orderBy('id')
                ->get()
                ->each(fn (UserAppAccess $access): mixed => $this->migrateAccess($access));

            CoreApplicationRole::query()
                ->where('app_code', 'lab-farmasi')
                ->whereNotIn('role_slug', $this->canonicalRoles)
                ->update(['is_active' => false]);
        });

        $this->line('Lab Farmasi roles normalized to canonical catalog.');
        $this->line('Guardrails: no password change, no SSO, no token URL, no user deletion.');

        return self::SUCCESS;
    }

    protected function migrateAccess(UserAppAccess $access): void
    {
        $canonical = $this->roleMap[$access->role_slug] ?? null;

        if (! $canonical) {
            return;
        }

        $existingCanonical = UserAppAccess::query()
            ->where('user_id', $access->user_id)
            ->where('app_code', 'lab-farmasi')
            ->where('role_slug', $canonical)
            ->whereKeyNot($access->id)
            ->first();

        if (! $existingCanonical) {
            $access->forceFill(['role_slug' => $canonical])->save();

            return;
        }

        $existingCanonical->forceFill([
            'is_active' => $existingCanonical->is_active || $access->is_active,
            'activated_at' => $existingCanonical->activated_at ?? $access->activated_at ?? now(),
            'deactivated_at' => ($existingCanonical->is_active || $access->is_active) ? null : $existingCanonical->deactivated_at,
        ])->save();

        $access->forceFill([
            'is_active' => false,
            'deactivated_at' => $access->deactivated_at ?? now(),
        ])->save();
    }
}
