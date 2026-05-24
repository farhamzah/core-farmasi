<?php

namespace App\Console\Commands;

use App\Models\CoreApiClient;
use App\Services\TuFarmasi\TuConnectionReadinessService;
use Illuminate\Console\Command;

class GrantTuApiClientAbilityCommand extends Command
{
    protected $signature = 'core:grant-tu-api-client-ability
        {--apply : Persist the ability update}
        {--ability=verify:tu-portal-auth : Single ability to grant}
        {--all-required : Grant all missing required TU abilities}
        {--client-id= : Target a specific active client id when needed}';

    protected $description = 'Dry-run or grant missing TU API client abilities without rotating secrets';

    public function handle(TuConnectionReadinessService $readiness): int
    {
        $client = $this->resolveClient($readiness);

        if (! $client) {
            $this->error('No active TU API client found for a safe ability grant.');

            return self::FAILURE;
        }

        $existing = $client->abilities ?: [];
        $targetAbilities = $this->targetAbilities($readiness);
        $missing = array_values(array_diff($targetAbilities, $existing));
        $after = array_values(array_unique([...$existing, ...$missing]));

        $this->line('TU API client ability grant');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Mode', $this->option('apply') ? 'apply' : 'dry-run'],
                ['App code', $readiness->appCode()],
                ['Client ID', $this->maskClientId((string) $client->client_id)],
                ['Existing abilities count', count($existing)],
                ['Missing abilities', $missing === [] ? '-' : implode(', ', $missing)],
                ['Secret rotation', 'no'],
            ],
        );

        if ($missing === []) {
            $this->info('No ability update needed. Existing client already has the requested ability set.');

            return self::SUCCESS;
        }

        if (! $this->option('apply')) {
            $this->info('Dry-run only. No database write was performed and no secret was read, shown, or rotated.');

            return self::SUCCESS;
        }

        $client->forceFill(['abilities' => $after])->save();

        $this->info('Ability update applied. Existing abilities were preserved and no secret was rotated.');

        return self::SUCCESS;
    }

    protected function resolveClient(TuConnectionReadinessService $readiness): ?CoreApiClient
    {
        $query = CoreApiClient::query()
            ->where('app_code', $readiness->appCode())
            ->active();

        if (filled($this->option('client-id'))) {
            return (clone $query)
                ->where('client_id', (string) $this->option('client-id'))
                ->first();
        }

        $clients = $query->orderByDesc('id')->get();

        if ($clients->count() > 1) {
            $this->warn('Multiple active TU clients found. Targeting the newest active client. Use --client-id to select explicitly.');
        }

        return $clients->first();
    }

    protected function targetAbilities(TuConnectionReadinessService $readiness): array
    {
        if ($this->option('all-required')) {
            return $readiness->requiredAbilities();
        }

        return [(string) $this->option('ability')];
    }

    protected function maskClientId(string $clientId): string
    {
        if ($clientId === '') {
            return '';
        }

        return substr($clientId, 0, 8).'...'.substr($clientId, -4);
    }
}
