<?php

namespace App\Providers;

use App\Http\Middleware\AuthenticateApiToken;
use App\Models\Employee;
use App\Models\Lecturer;
use App\Models\Student;
use App\Observers\CoreProfileUserObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::aliasMiddleware('auth.api', AuthenticateApiToken::class);

        RateLimiter::for('core-api-login', function (Request $request): Limit {
            $email = $request->input('email');
            $identifier = is_string($email) ? Str::lower(trim($email)) : '';

            // SAFA clients can share an IP; isolate failed attempts by account instead.
            return Limit::perMinute(max(1, (int) config('core_api.login_max_failed_attempts_per_minute', 5)))
                ->by('account:'.hash('sha256', $identifier))
                ->after(static fn (Response $response): bool => $response->getStatusCode() === 401);
        });

        Student::observe(CoreProfileUserObserver::class);
        Lecturer::observe(CoreProfileUserObserver::class);
        Employee::observe(CoreProfileUserObserver::class);
    }
}
