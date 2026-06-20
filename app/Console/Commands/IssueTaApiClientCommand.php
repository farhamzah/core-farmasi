<?php

namespace App\Console\Commands;

use App\Models\CoreApiClient;
use App\Models\CoreApplication;
use App\Services\AppConnectionReadinessService;
use App\Services\CoreApiClientCredentialService;
use Illuminate\Console\Command;

class IssueTaApiClientCommand extends Command
{
    protected $signature = 'core:issue-ta-api-client
        {--apply : Create or rotate the TA API client}
        {--name=TA Farmasi Staging Client : Name for a newly created API client}
        {--rotate-existing : Rotate an existing active TA API client instead of creating}
        {--force-rotate : Allow rotating multiple active TA API clients}
        {--show-env-template : Show TA staging env template without real secrets}';

    protected $description = 'Dry-run or safely issue TA Farmasi app-client credentials';

    public function handle(
        CoreApiClientCredentialService $credentials,
        AppConnectionReadinessService $readiness,
    ): int {
        $appCode = 'ta-farmasi';
        $requiredAbilities = $readiness->requiredAbilities($appCode);
        $application = CoreApplication::query()->where('app_code', $appCode)->first();
        $activeClients = CoreApiClient::query()
            ->where('app_code', $appCode)
            ->active()
            ->orderBy('id')
            ->get();

        $this->line('TA API client issuance');
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
            $this->error('Cannot issue TA API client because ta-farmasi application is missing or inactive.');

            return self::FAILURE;
        }

        if (! $this->option('apply')) {
            $this->info('Dry-run only. No client was created, no secret was generated, and no database write was performed.');
            $this->line('Next action: review required abilities, then re-run with --apply only in the intended secure environment.');

            return self::SUCCESS;
        }

        if ($activeClients->isNotEmpty() && ! $this->option('rotate-existing')) {
            $this->warn('Active TA API client already exists. No duplicate client was created.');
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
            'notes' => 'Created by core:issue-ta-api-client for TA staging read-only integration.',
        ]);

        $this->info('TA API client created. Copy the secret now; it will not be shown again.');
        $this->line('Client ID: '.$client->client_id);
        $this->line('Client Secret (shown once): '.$plainSecret);
        $this->warn('Store this secret in an approved secret manager or staging env. Do not commit it, paste it into reports, or put it in a URL.');
        $this->showEnvTemplate($client->client_id);

        return self::SUCCESS;
    }

    protected function rotateExisting(CoreApiClientCredentialService $credentials, $activeClients): int
    {
        if ($activeClients->isEmpty()) {
            $this->error('No active TA API client exists to rotate.');

            return self::FAILURE;
        }

        if ($activeClients->count() > 1 && ! $this->option('force-rotate')) {
            $this->error('Multiple active TA API clients exist. Re-run with --force-rotate to rotate all active clients.');

            return self::FAILURE;
        }

        foreach ($activeClients as $client) {
            $plainSecret = $credentials->rotateSecret($client);

            $this->info('TA API client rotated. Copy the secret now; it will not be shown again.');
            $this->line('Client ID: '.$client->client_id);
            $this->line('Client Secret (shown once): '.$plainSecret);
        }

        $this->warn('Store rotated secret values securely and clear TA config cache before smoke testing.');

        return self::SUCCESS;
    }

    protected function showEnvTemplate(?string $clientId = null): void
    {
        $this->line('TA staging env template (placeholders only):');
        $this->line('TA_CORE_HTTP_ENABLED=true');
        $this->line('TA_CORE_READ_MODE=http');
        $this->line('TA_CORE_BASE_URL=https://core-staging.example.test');
        $this->line('TA_CORE_PROFILE_URL=https://core-staging.example.test/profile');
        $this->line('TA_CORE_APP_CODE=ta-farmasi');
        $this->line('TA_CORE_CLIENT_ID='.($clientId ?: '<client_id>'));
        $this->line('TA_CORE_CLIENT_SECRET=<copy-secret-once>');
        $this->line('TA_CORE_TIMEOUT=5');
        $this->line('TA_CORE_CONNECT_TIMEOUT=3');
        $this->line('TA_CORE_VERIFY_SSL=true');
        $this->line('TA_CORE_FAIL_SILENTLY=false');
        $this->line('TA_CORE_ACCESS_ENFORCEMENT_ENABLED=true');
        $this->line('TA_CORE_ACCESS_FAIL_CLOSED=true');
        $this->line('TA_CORE_ACCESS_ALLOW_LOCAL_PREVIEW=false');
    }
}
