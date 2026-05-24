<?php

namespace App\Services;

use App\Models\CoreApiClient;
use App\Models\CoreApiRequestLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class CoreApiAuditService
{
    public function log(
        Request $request,
        int $statusCode,
        ?CoreApiClient $client,
        ?string $ability,
        int $durationMs,
        ?string $errorCode = null,
        ?string $errorMessage = null,
    ): void {
        try {
            CoreApiRequestLog::create([
                'core_api_client_id' => $client?->id,
                'app_code' => $client?->app_code ?: $this->safeHeader($request, 'X-Core-App-Code'),
                'client_id' => $client?->client_id ?: $this->safeHeader($request, 'X-Core-Client-Id'),
                'method' => Str::limit($request->method(), 16, ''),
                'path' => $this->safePath($request),
                'route_name' => $request->route()?->getName(),
                'status_code' => $statusCode,
                'ability' => $ability,
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 512, ''),
                'request_id' => $request->headers->get('X-Request-Id') ?: $request->headers->get('X-Correlation-Id'),
                'duration_ms' => $durationMs,
                'is_success' => $statusCode >= 200 && $statusCode < 400,
                'error_code' => $errorCode,
                'error_message' => $errorMessage ? Str::limit($errorMessage, 512, '') : null,
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            //
        }
    }

    protected function safePath(Request $request): string
    {
        return Str::limit('/' . ltrim($request->path(), '/'), 255, '');
    }

    protected function safeHeader(Request $request, string $header): ?string
    {
        $value = $request->header($header);

        return filled($value) ? Str::limit((string) $value, 255, '') : null;
    }
}
