<?php

namespace App\Http\Middleware;

use App\Services\CoreApiAuditService;
use App\Services\CoreApiClientCredentialService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AuthenticateCoreApiClient
{
    public function __construct(
        protected CoreApiClientCredentialService $credentials,
        protected CoreApiAuditService $audit,
    ) {}

    public function handle(Request $request, Closure $next, ?string $ability = null)
    {
        $startedAt = microtime(true);

        if ($request->query->has('client_id') || $request->query->has('client_secret')) {
            return $this->reject($request, 401, $ability, $startedAt, 'query_token', 'Credential query string is not allowed.');
        }

        $clientId = $request->header('X-Core-Client-Id');
        $clientSecret = $request->header('X-Core-Client-Secret');
        $appCode = $request->header('X-Core-App-Code');

        if ($this->isRateLimited($request, $ability, $clientId, $appCode)) {
            return $this->reject($request, 429, $ability, $startedAt, 'rate_limited', 'Rate limit exceeded.');
        }

        if (blank($clientId) || blank($clientSecret) || blank($appCode)) {
            return $this->reject($request, 401, $ability, $startedAt, 'missing_credentials', 'Missing app client credentials.');
        }

        $client = $this->credentials->validateCredentials($clientId, $clientSecret, $appCode, $request->ip());

        if (! $client) {
            return $this->reject($request, 401, $ability, $startedAt, 'invalid_client', 'Invalid app client credentials.');
        }

        if (! $client->canUseAbility($ability)) {
            return $this->reject($request, 403, $ability, $startedAt, 'missing_ability', 'App client does not have the required ability.');
        }

        $client->markUsed();

        $request->attributes->set('core_api_client', $client);
        $request->attributes->set('core_api_client_app_code', $client->app_code);
        $request->attributes->set('core_api_client_ability', $ability);

        $response = $next($request);

        $this->audit->log(
            $request,
            $response->getStatusCode(),
            $client,
            $ability,
            $this->durationMs($startedAt),
        );

        return $response;
    }

    protected function reject(Request $request, int $statusCode, ?string $ability, float $startedAt, string $errorCode, string $errorMessage)
    {
        $this->audit->log(
            $request,
            $statusCode,
            null,
            $ability,
            $this->durationMs($startedAt),
            $errorCode,
            $errorMessage,
        );

        return response()->json(['message' => match ($statusCode) {
            403 => 'Forbidden',
            429 => 'Too Many Requests',
            default => 'Unauthorized',
        }], $statusCode);
    }

    protected function isRateLimited(Request $request, ?string $ability, ?string $clientId, ?string $appCode): bool
    {
        $limit = $this->rateLimit($appCode, $ability);
        $window = (int) config('core_api.client_rate_window_seconds', 60);
        $key = $this->rateLimitKey($request, $clientId, $appCode);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return true;
        }

        RateLimiter::hit($key, $window);

        return false;
    }

    protected function rateLimit(?string $appCode, ?string $ability): int
    {
        $abilityLimits = config('core_api.per_ability_rate_limits', []);

        if ($ability && isset($abilityLimits[$ability])) {
            return (int) $abilityLimits[$ability];
        }

        $appLimits = config('core_api.per_app_rate_limits', []);

        if ($appCode && isset($appLimits[$appCode])) {
            return (int) $appLimits[$appCode];
        }

        return (int) config('core_api.default_client_rate_limit', 120);
    }

    protected function rateLimitKey(Request $request, ?string $clientId, ?string $appCode): string
    {
        $identity = filled($clientId) && filled($appCode)
            ? "{$clientId}:{$appCode}"
            : 'ip:' . $request->ip();

        return 'core-api-client:' . sha1($identity);
    }

    protected function durationMs(float $startedAt): int
    {
        return (int) max(0, round((microtime(true) - $startedAt) * 1000));
    }
}
