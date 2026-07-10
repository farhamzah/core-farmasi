<?php

namespace App\Console\Commands;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\User;
use App\Models\UserAppAccess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LabFarmasiUatTestUserCommand extends Command
{
    private const APP_CODE = 'lab-farmasi';

    private const ROLES = [
        'admin_lab',
        'koordinator_lab',
        'laboran',
        'teknisi',
        'dosen',
        'mahasiswa',
        'viewer',
    ];

    protected $signature = 'core:lab-farmasi-uat-test-user
        {--email=farhamzah@ubpkarawang.ac.id : Core user email for the persistent Lab UAT test account}
        {--name=Farhamzah : Display name to use when the Core user does not exist yet}
        {--password-env=CORE_LAB_UAT_PASSWORD : Environment variable that contains the password when --set-password is used}
        {--set-password : Set/reset the user password from --password-env}
        {--apply : Apply changes. Without this flag, only shows the dry-run summary}
        {--allow-production : Allow applying in APP_ENV=production after explicit operator decision}';

    protected $description = 'Prepare one persistent Core user with all canonical Lab Farmasi roles for UAT/testing.';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->option('email')));
        $name = trim((string) $this->option('name')) ?: 'Lab Farmasi UAT User';
        $passwordEnv = trim((string) $this->option('password-env')) ?: 'CORE_LAB_UAT_PASSWORD';
        $apply = (bool) $this->option('apply');
        $setPassword = (bool) $this->option('set-password');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email.');

            return self::FAILURE;
        }

        if ($apply && app()->environment('production') && ! $this->option('allow-production')) {
            $this->error('Refusing to apply in production without --allow-production.');
            $this->line('Recommended: use this command only for local/staging/UAT accounts.');

            return self::FAILURE;
        }

        $application = CoreApplication::query()
            ->where('app_code', self::APP_CODE)
            ->where('is_active', true)
            ->first();

        if (! $application) {
            $this->error('Lab Farmasi application is missing or inactive in Core registry.');

            return self::FAILURE;
        }

        $activeRoles = CoreApplicationRole::query()
            ->where('app_code', self::APP_CODE)
            ->whereIn('role_slug', self::ROLES)
            ->where('is_active', true)
            ->pluck('role_slug')
            ->all();

        $missingRoles = array_values(array_diff(self::ROLES, $activeRoles));

        if ($missingRoles !== []) {
            $this->error('Missing active Lab Farmasi role(s): '.implode(', ', $missingRoles));
            $this->line('Run CoreApplicationSeeder or fix the Core app role catalog first.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();
        $existingRoleRows = $user
            ? UserAppAccess::query()
                ->where('user_id', $user->id)
                ->where('app_code', self::APP_CODE)
                ->whereIn('role_slug', self::ROLES)
                ->get()
                ->keyBy('role_slug')
            : collect();

        $rolePlan = collect(self::ROLES)->map(fn (string $role): array => [
            'role' => $role,
            'current' => $existingRoleRows->has($role)
                ? ($existingRoleRows[$role]->is_active ? 'active' : 'inactive')
                : 'missing',
            'target' => 'active',
        ]);

        $this->info('Core Lab Farmasi UAT test user');
        $this->line('Mode: '.($apply ? 'APPLY' : 'DRY-RUN'));
        $this->table(
            ['Metric', 'Value'],
            [
                ['Email', $email],
                ['User status', $user ? 'existing' : 'will-create'],
                ['Name', $user?->name ?: $name],
                ['App code', self::APP_CODE],
                ['Password action', $setPassword ? "set from {$passwordEnv}" : 'unchanged'],
            ],
        );
        $this->table(['Role', 'Current', 'Target'], $rolePlan->all());

        if (! $apply) {
            $this->line('Dry-run only. Re-run with --apply after review.');
            $this->line('Guardrails: one explicit UAT user only, no mass grant, no SSO, no token URL, no password output.');

            return self::SUCCESS;
        }

        if ($setPassword && blank(env($passwordEnv))) {
            $this->error("Password env {$passwordEnv} is empty or missing.");
            $this->line('Set it in the shell for this command only. Do not commit passwords to files.');

            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $user->exists ? $user->name : $name,
            'username' => $user->username ?: Str::before($email, '@'),
            'identity_type' => $user->identity_type ?: 'uat',
            'identity_number' => $user->identity_number ?: 'LAB-UAT-'.Str::upper(Str::before($email, '@')),
            'active' => true,
            'email_verified_at' => $user->email_verified_at ?: now(),
        ]);

        if (! $user->exists && ! $setPassword) {
            $user->password = Str::random(48);
            $user->must_change_password = true;
        }

        if ($setPassword) {
            $user->password = Hash::make((string) env($passwordEnv));
            $user->must_change_password = false;
            $user->password_changed_at = now();
        }

        $user->save();

        foreach (self::ROLES as $role) {
            UserAppAccess::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'app_code' => self::APP_CODE,
                    'role_slug' => $role,
                ],
                [
                    'is_active' => true,
                    'activated_at' => now(),
                    'deactivated_at' => null,
                ],
            );
        }

        $this->info('Lab Farmasi UAT test user is ready.');
        $this->line('Granted active roles: '.implode(', ', self::ROLES));
        $this->line('Guardrails: one explicit UAT user only, password not printed, no SSO, no token URL, no Core write-back from Lab.');

        return self::SUCCESS;
    }
}
