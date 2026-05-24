<?php

namespace App\Console\Commands;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\User;
use App\Models\UserAppAccess;
use Illuminate\Console\Command;

class LabAccessDryRunCommand extends Command
{
    protected $signature = 'core:lab-access-dry-run
        {core_user_id : Core user id to check}
        {role : Lab role slug to assign or revoke}
        {--apply : Create or reactivate one Lab app access}
        {--revoke : Deactivate one Lab app access}';

    protected $description = 'Dry-run, assign, or revoke one explicit Lab Farmasi app access without mass grants.';

    public function handle(): int
    {
        $appCode = 'lab-farmasi';
        $coreUserId = (int) $this->argument('core_user_id');
        $role = (string) $this->argument('role');

        if ($this->option('apply') && $this->option('revoke')) {
            $this->error('Choose only one action: --apply or --revoke.');

            return self::FAILURE;
        }

        $application = CoreApplication::query()
            ->where('app_code', $appCode)
            ->where('is_active', true)
            ->first();

        if (! $application) {
            $this->error('Lab application is missing or inactive in Core registry.');

            return self::FAILURE;
        }

        $roleExists = CoreApplicationRole::query()
            ->where('app_code', $appCode)
            ->where('role_slug', $role)
            ->where('is_active', true)
            ->exists();

        if (! $roleExists) {
            $this->error("Lab role '{$role}' does not exist or is inactive.");

            return self::FAILURE;
        }

        $user = User::query()->where('active', true)->find($coreUserId);

        if (! $user) {
            $this->error('Core user not found or inactive. This command never creates users.');

            return self::FAILURE;
        }

        $existing = UserAppAccess::query()
            ->where('user_id', $user->id)
            ->where('app_code', $appCode)
            ->where('role_slug', $role)
            ->first();

        $mode = $this->option('apply') ? 'apply' : ($this->option('revoke') ? 'revoke' : 'dry-run');
        $status = $existing
            ? ($existing->is_active ? 'existing-active' : 'existing-inactive')
            : 'would-create';

        $this->table(
            ['Metric', 'Value'],
            [
                ['Mode', $mode],
                ['User ID', (string) $user->id],
                ['Display name', $user->name],
                ['Username', $user->username ?: '-'],
                ['App code', $appCode],
                ['Role slug', $role],
                ['Access status', $status],
            ],
        );

        if (! $this->option('apply') && ! $this->option('revoke')) {
            $this->info('Dry-run only. Re-run with --apply for this one user/role, or --revoke to deactivate this one access.');
            $this->line('Guardrails: no mass grant, no credential change, no SSO, no token URL, no credential output.');

            return self::SUCCESS;
        }

        if ($this->option('revoke')) {
            if (! $existing) {
                $this->warn('No matching Lab app access found to revoke.');
                $this->line('Guardrails: no mass revoke, no user deletion, no credential output.');

                return self::SUCCESS;
            }

            $existing->update([
                'is_active' => false,
                'deactivated_at' => now(),
            ]);

            $this->info('Lab app access deactivated.');
            $this->line('Guardrails: one explicit access only, no user deletion, no credential output.');

            return self::SUCCESS;
        }

        $access = UserAppAccess::updateOrCreate(
            [
                'user_id' => $user->id,
                'app_code' => $appCode,
                'role_slug' => $role,
            ],
            [
                'is_active' => true,
                'activated_at' => now(),
                'deactivated_at' => null,
            ],
        );

        $this->info($access->wasRecentlyCreated ? 'Lab app access created.' : 'Lab app access activated.');
        $this->line('Guardrails: one explicit access only, no credential change, no SSO, no token URL, no credential output.');

        return self::SUCCESS;
    }
}
