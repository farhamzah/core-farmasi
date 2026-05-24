<?php

namespace App\Console\Commands;

use App\Services\TuFarmasi\TuConnectionReadinessService;
use Illuminate\Console\Command;

class TuConnectionReadinessCommand extends Command
{
    protected $signature = 'core:tu-connection-readiness';

    protected $description = 'Check Core readiness for TU Farmasi read-only connection without exposing secrets';

    public function handle(TuConnectionReadinessService $readiness): int
    {
        $summary = $readiness->readinessSummary();

        $this->line('Core to TU connection readiness');
        $this->table(
            ['Metric', 'Value'],
            [
                ['App code', $summary['app_code']],
                ['App registered', $this->yesNo($summary['application']['exists'])],
                ['App active', $this->yesNo($summary['application']['is_active'])],
                ['App public visible', $this->yesNo($summary['application']['is_public_visible'])],
                ['Required roles missing', $summary['roles']['missing'] === [] ? '-' : implode(', ', $summary['roles']['missing'])],
                ['Active API client count', $summary['api_clients']['active_count']],
                ['Active user app access count', $summary['user_app_access']['active_count']],
                ['Endpoints available', $this->yesNo($summary['endpoints']['complete'])],
                ['Portal verify endpoint available', $this->yesNo($summary['endpoints']['portal_verify_endpoint_available'])],
                ['Profile route available', $this->yesNo($summary['endpoints']['profile_route_available'])],
                ['Readiness verdict', $summary['verdict']],
            ],
        );

        if ($summary['api_clients']['clients'] !== []) {
            $this->line('Active API client ability readiness');
            $this->table(
                ['Client', 'Abilities count', 'Missing abilities'],
                collect($summary['api_clients']['clients'])->map(fn (array $client): array => [
                    $client['client_id_hint'],
                    $client['abilities_count'],
                    $client['missing_abilities'] === [] ? '-' : implode(', ', $client['missing_abilities']),
                ])->all(),
            );
        }

        if ($summary['endpoints']['missing'] !== []) {
            $this->warn('Missing endpoints: '.implode(', ', $summary['endpoints']['missing']));
        }

        $this->line('Next action: '.$this->nextAction($summary['verdict']));

        return self::SUCCESS;
    }

    protected function yesNo(bool $value): string
    {
        return $value ? 'yes' : 'no';
    }

    protected function nextAction(string $verdict): string
    {
        return match ($verdict) {
            'ready_for_staging_config' => 'Configure TU staging env securely, then run TU smoke test.',
            'missing_api_client' => 'Create or update TU app client with all required abilities, store any one-time secret securely, then re-run readiness.',
            'missing_roles' => 'Seed or create required TU app roles in Core app role catalog.',
            'missing_application' => 'Register tu-farmasi in Core applications and keep it non-public.',
            default => 'Resolve readiness warnings, keep integration disabled, then re-run readiness.',
        };
    }
}
