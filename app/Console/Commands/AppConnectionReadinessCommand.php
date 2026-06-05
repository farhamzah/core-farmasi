<?php

namespace App\Console\Commands;

use App\Services\AppConnectionReadinessService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class AppConnectionReadinessCommand extends Command
{
    protected $signature = 'core:app-connection-readiness {app_code : Consumer app code}';

    protected $description = 'Check Core readiness for a consumer app connection without exposing secrets';

    public function handle(AppConnectionReadinessService $readiness): int
    {
        $appCode = (string) $this->argument('app_code');

        try {
            $summary = $readiness->readinessSummary($appCode);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());
            $this->line('Supported app codes: '.implode(', ', $readiness->supportedAppCodes()));

            return self::FAILURE;
        }

        $this->line($readiness->appDisplayName($appCode).' connection readiness');
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

        $this->line('Next action: '.$this->nextAction($summary['verdict'], $appCode));

        return self::SUCCESS;
    }

    protected function yesNo(bool $value): string
    {
        return $value ? 'yes' : 'no';
    }

    protected function nextAction(string $verdict, string $appCode): string
    {
        return match ($verdict) {
            'ready_for_staging_config' => "Configure {$appCode} staging env securely, then run a future read-only smoke test.",
            'missing_api_client' => "Create {$appCode} app client with required abilities, store the one-time secret securely, then re-run readiness.",
            'missing_roles' => "Seed or create required {$appCode} app roles in Core app role catalog.",
            'missing_application' => "Register {$appCode} in Core applications and keep it non-public.",
            default => "Resolve {$appCode} readiness warnings, keep integration disabled, then re-run readiness.",
        };
    }
}
