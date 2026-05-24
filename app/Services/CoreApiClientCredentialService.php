<?php

namespace App\Services;

use App\Models\CoreApiClient;
use App\Models\CoreApplication;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CoreApiClientCredentialService
{
    public function generatePlainSecret(): string
    {
        return 'core_' . Str::random(64);
    }

    public function generateClientId(string $appCode): string
    {
        return Str::slug($appCode) . '_' . Str::lower(Str::random(24));
    }

    public function hashSecret(string $plainSecret): string
    {
        return Hash::make($plainSecret);
    }

    public function createClient(array $data, ?User $actor = null): array
    {
        $plainSecret = $this->generatePlainSecret();
        $application = CoreApplication::query()->where('app_code', $data['app_code'])->first();

        $client = CoreApiClient::create([
            'core_application_id' => $application?->id,
            'app_code' => $data['app_code'],
            'name' => $data['name'],
            'client_id' => $data['client_id'] ?? $this->generateClientId($data['app_code']),
            'secret_hash' => $this->hashSecret($plainSecret),
            'abilities' => $data['abilities'] ?? [],
            'allowed_ips' => $data['allowed_ips'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'notes' => $data['notes'] ?? null,
            'created_by' => $actor?->id,
            'last_rotated_at' => now(),
            'rotated_by' => $actor?->id,
        ]);

        $this->log('api_client.created', $client, $actor);

        return [$client, $plainSecret];
    }

    public function rotateSecret(CoreApiClient $client, ?User $actor = null): string
    {
        $plainSecret = $this->generatePlainSecret();

        $client->forceFill([
            'secret_hash' => $this->hashSecret($plainSecret),
            'last_rotated_at' => now(),
            'rotated_by' => $actor?->id,
        ])->save();

        $this->log('api_client.rotated', $client, $actor);

        return $plainSecret;
    }

    public function revoke(CoreApiClient $client, ?User $actor = null): void
    {
        $client->forceFill([
            'is_active' => false,
            'revoked_at' => now(),
            'revoked_by' => $actor?->id,
        ])->save();

        $this->log('api_client.revoked', $client, $actor);
    }

    public function validate(string $clientId, string $plainSecret, string $appCode, ?string $ability = null, ?string $ip = null): ?CoreApiClient
    {
        $client = $this->validateCredentials($clientId, $plainSecret, $appCode, $ip);

        if (! $client || ! $client->canUseAbility($ability)) {
            return null;
        }

        $client->markUsed();

        return $client;
    }

    public function validateCredentials(string $clientId, string $plainSecret, string $appCode, ?string $ip = null): ?CoreApiClient
    {
        $client = CoreApiClient::query()
            ->with('application')
            ->where('client_id', $clientId)
            ->where('app_code', $appCode)
            ->first();

        if (! $client || ! $client->is_active || $client->isRevoked() || blank($client->secret_hash)) {
            return null;
        }

        $application = $client->application ?: CoreApplication::query()
            ->where('app_code', $client->app_code)
            ->first();

        if (! $application || ! $application->is_active) {
            return null;
        }

        if (! Hash::check($plainSecret, $client->secret_hash)) {
            return null;
        }

        $allowedIps = $client->allowed_ips ?: [];

        if ($allowedIps !== [] && $ip !== null && ! in_array($ip, $allowedIps, true)) {
            return null;
        }

        return $client;
    }

    protected function log(string $action, CoreApiClient $client, ?User $actor): void
    {
        if (! $actor) {
            return;
        }

        UserActivityLog::create([
            'user_id' => $actor->id,
            'action' => $action,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'meta' => [
                'client_id' => $client->client_id,
                'app_code' => $client->app_code,
                'actor_id' => $actor->id,
                'abilities' => $client->abilities ?: [],
            ],
        ]);
    }
}
