<?php

namespace App\Console\Commands;

use App\Models\CoreApiClient;
use App\Models\CoreApplication;
use App\Services\AppConnectionReadinessService;
use App\Services\CoreApiClientCredentialService;
use Illuminate\Console\Command;

class IssueKppspaApiClientCommand extends Command
{
    protected $signature = 'core:issue-kppspa-api-client
        {--apply : Create or rotate the MY PKPA API client}
        {--name=MY PKPA Staging Client : Name for a newly created API client}
        {--rotate-existing : Rotate an existing active MY PKPA API client instead of creating}
        {--force-rotate : Allow rotating multiple active MY PKPA API clients}
        {--show-env-template : Show MY PKPA staging env template without real secrets}';

    protected $description = 'Dry-run or safely issue MY PKPA app-client credentials';

    public function handle(
        CoreApiClientCredentialService $credentials,
        AppConnectionReadinessService $readiness,
    ): int {
        $appCode = 'kppspa-farmasi';
        $requiredAbilities = $readiness->requiredAbilities($appCode);
        $application = CoreApplication::query()->where('app_code', $appCode)->first();
        $activeClients = CoreApiClient::query()
            ->where('app_code', $appCode)
            ->active()
            ->orderBy('id')
            ->get();

        $this->line('MY PKPA API client issuance');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Mode', $this->option('apply') ? 'apply' : 'dry-run'],
                ['App code', $appCode],
                ['Application registered', $application ? 'yes' : 'no'],
                ['Application active', $application?->is_active ? 'yes' : 'no'],
                ['Active API clients', $activeClients->count()],
                ['Required abilities', implode(', ', $requiredAbilities)],
            ],
        );

        if ($this->option('show-env-template')) {
            $this->showEnvTemplate($activeClients->first()?->client_id);
        }

        if (! $application || ! $application->is_active) {
            $this->error('Cannot issue MY PKPA API client because kppspa-farmasi application is missing or inactive.');

            return self::FAILURE;
        }

        if (! $this->option('apply')) {
            $this->info('Dry-run only. No client was created, no secret was generated, and no database write was performed.');
            $this->line('Next action: review required abilities, then re-run with --apply only in the intended secure environment.');

            return self::SUCCESS;
        }

        if ($activeClients->isNotEmpty() && ! $this->option('rotate-existing')) {
            $this->warn('Active MY PKPA API client already exists. No duplicate client was created.');
            $this->line('Existing client id: '.$activeClients->first()->client_id);
            $this->line('Use --rotate-existing to rotate the existing client secret.');

            return self::SUCCESS;
        }

        if ($this->option('rotate-existing')) {
            return $this->rotateExisting($credentials, $activeClients);
        }

        [$client, $plainSecret] = $credentials->createClient([
            'app_code' => $appCode,
            'name' => (string) $this->option('name'),
            'abilities' => $requiredAbilities,
            'is_active' => true,
            'notes' => 'Created by core:issue-kppspa-api-client for MY PKPA staging read-only integration.',
        ]);

        $this->info('MY PKPA API client created. Copy the secret now; it will not be shown again.');
        $this->line('Client ID: '.$client->client_id);
        $this->line('Client Secret (shown once): '.$plainSecret);
        $this->warn('Store this secret in an approved secret manager or staging env. Do not commit it, paste it into reports, or put it in a URL.');
        $this->showEnvTemplate($client->client_id);

        return self::SUCCESS;
    }

    protected function rotateExisting(CoreApiClientCredentialService $credentials, $activeClients): int
    {
        if ($activeClients->isEmpty()) {
            $this->error('No active MY PKPA API client exists to rotate.');

            return self::FAILURE;
        }

        if ($activeClients->count() > 1 && ! $this->option('force-rotate')) {
            $this->error('Multiple active MY PKPA API clients exist. Re-run with --force-rotate to rotate all active clients.');

            return self::FAILURE;
        }

        foreach ($activeClients as $client) {
            $plainSecret = $credentials->rotateSecret($client);

            $this->info('MY PKPA API client rotated. Copy the secret now; it will not be shown again.');
            $this->line('Client ID: '.$client->client_id);
            $this->line('Client Secret (shown once): '.$plainSecret);
        }

        $this->warn('Store rotated secret values securely and clear MY PKPA config cache before smoke testing.');

        return self::SUCCESS;
    }

    protected function showEnvTemplate(?string $clientId = null): void
    {
        $this->line('MY PKPA staging env template (placeholders only):');
        $this->line('KP_AUTH_MODE=core_http');
        $this->line('CORE_FARMASI_ENABLED=true');
        $this->line('CORE_FARMASI_URL=https://core.safaubp.com');
        $this->line('CORE_FARMASI_APP_CODE=kppspa-farmasi');
        $this->line('CORE_FARMASI_CLIENT_ID='.($clientId ?: '<client_id>'));
        $this->line('CORE_FARMASI_CLIENT_SECRET=<copy-secret-once>');
        $this->line('CORE_FARMASI_TIMEOUT=10');
        $this->line('CORE_FARMASI_CONNECT_TIMEOUT=3');
        $this->line('CORE_FARMASI_VERIFY_SSL=true');
        $this->line('CORE_FARMASI_REGISTER_URL=https://core.safaubp.com/account-request');
        $this->line('CORE_FARMASI_FORGOT_PASSWORD_URL=https://core.safaubp.com/profile/forgot-password');
        $this->line('CORE_FARMASI_ACCOUNT_URL=https://core.safaubp.com/profile');
        $this->line('KP_CORE_READ_MODE=core_preferred');
        $this->line('KP_CORE_FAIL_SILENTLY=false');
    }
}
