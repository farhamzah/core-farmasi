<?php

namespace App\Console\Commands;

use App\Models\CoreApiClient;
use App\Models\CoreApplication;
use App\Services\CoreApiClientCredentialService;
use Dotenv\Dotenv;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Throwable;

class IssueKarirApiClientCommand extends Command
{
    protected $signature = 'core:issue-karir-api-client
        {--env-path= : Existing .env file in the separate Alumni application}
        {--apply : Create the dedicated client and write credentials directly to that file}';

    protected $description = 'Issue Alumni Farmasi credentials without displaying the secret';

    public function handle(CoreApiClientCredentialService $credentials): int
    {
        $path = realpath((string) $this->option('env-path'));
        if (! $path || ! is_file($path) || basename($path) !== '.env'
            || $path === realpath(base_path('.env')) || ! is_readable($path) || ! is_writable($path)) {
            $this->error('Provide a readable, writable Alumni .env file outside Core with --env-path.');

            return self::FAILURE;
        }

        $handle = @fopen($path, 'r+');
        if ($handle === false) {
            $this->error('Cannot open the Alumni environment file.');

            return self::FAILURE;
        }

        $original = null;
        $modified = false;
        $created = false;

        try {
            if (! flock($handle, LOCK_EX | LOCK_NB)) {
                throw new RuntimeException;
            }
            $original = stream_get_contents($handle);
            if ($original === false) {
                throw new RuntimeException;
            }
            $env = Dotenv::parse($original);
            $coreDatabase = DB::connection()->getDatabaseName();
            if (empty($env['DB_DATABASE']) || $env['DB_DATABASE'] === $coreDatabase
                || str_contains($env['DB_DATABASE'], '${')) {
                $this->error('Alumni DB_DATABASE must be explicit and different from the Core database.');

                return self::FAILURE;
            }

            $result = DB::transaction(function () use ($credentials, $env, $original, $handle, &$modified, &$created): int {
                $app = CoreApplication::query()->where('app_code', 'karir-farmasi')->lockForUpdate()->first();
                if (! $app || ! $app->is_active) {
                    $this->error('Seed and activate karir-farmasi before issuing credentials.');

                    return self::FAILURE;
                }

                $clients = CoreApiClient::withTrashed()->where('app_code', 'karir-farmasi')->get();
                $abilities = config('core_karir.client_abilities');
                if ($clients->isNotEmpty()) {
                    $client = $clients->firstWhere('client_id', $env['CORE_IDENTITY_CLIENT_ID'] ?? '');
                    if ($client && ! $client->trashed() && $client->is_active && ! $client->isRevoked()
                        && ! in_array('*', $client->abilities ?: [], true)
                        && array_diff($abilities, $client->abilities ?: []) === []
                        && array_diff($client->abilities ?: [], $abilities) === []
                        && Hash::check($env['CORE_IDENTITY_CLIENT_SECRET'] ?? '', $client->secret_hash)) {
                        $this->info('Existing Alumni credentials match. Nothing was changed.');

                        return self::SUCCESS;
                    }
                    $this->error('A Karir client already exists but does not match this .env. No duplicate or rotation was performed.');

                    return self::FAILURE;
                }

                if (! empty($env['CORE_IDENTITY_CLIENT_ID']) || ! empty($env['CORE_IDENTITY_CLIENT_SECRET'])) {
                    $this->error('Alumni already has credentials configured. Review them before issuing a new client.');

                    return self::FAILURE;
                }
                if (! $this->option('apply')) {
                    $this->info('Dry-run passed: separate database, active app, and no existing credentials. Re-run with --apply.');

                    return self::SUCCESS;
                }

                [$client, $secret] = $credentials->createClient([
                    'app_code' => 'karir-farmasi', 'name' => 'Alumni Farmasi API Client',
                    'abilities' => $abilities, 'is_active' => true,
                    'notes' => 'Created by core:issue-karir-api-client; secret delivered directly to Alumni .env.',
                ]);
                $updated = $original;
                foreach (['CORE_IDENTITY_CLIENT_ID' => $client->client_id, 'CORE_IDENTITY_CLIENT_SECRET' => $secret] as $key => $value) {
                    $pattern = '/^[\t ]*(?:export[\t ]+)?'.preg_quote($key, '/').'[\t ]*=.*$/m';
                    $line = $key.'='.$value;
                    $updated = preg_match($pattern, $updated)
                        ? preg_replace($pattern, $line, $updated)
                        : rtrim($updated).PHP_EOL.$line.PHP_EOL;
                }
                $modified = true;
                $this->writeContents($handle, $updated);
                $created = true;

                return self::SUCCESS;
            });
            if ($created) {
                $this->info('Credentials written directly to Alumni .env. The secret is not displayed.');
                $this->line('Clear the Alumni config cache before checking the connection.');
            }

            return $result;
        } catch (Throwable) {
            if ($modified && is_string($original)) {
                try {
                    $this->writeContents($handle, $original);
                } catch (Throwable) {
                    $this->error('Environment restoration failed. Inspect the target .env privately before retrying.');
                }
            }
            $this->error('Issuance failed; no database changes were committed. No credential details are printed.');

            return self::FAILURE;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function writeContents($handle, string $contents): void
    {
        if (! rewind($handle) || ! ftruncate($handle, 0)
            || fwrite($handle, $contents) !== strlen($contents) || ! fflush($handle)) {
            throw new RuntimeException;
        }
    }
}
