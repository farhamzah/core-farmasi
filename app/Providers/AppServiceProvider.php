<?php

namespace App\Providers;

use App\Http\Middleware\AuthenticateApiToken;
use App\Models\Employee;
use App\Models\Lecturer;
use App\Models\Student;
use App\Observers\CoreProfileUserObserver;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

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

        Student::observe(CoreProfileUserObserver::class);
        Lecturer::observe(CoreProfileUserObserver::class);
        Employee::observe(CoreProfileUserObserver::class);
    }
}
