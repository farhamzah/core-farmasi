<?php

use Illuminate\Foundation\Application;
use App\Console\Commands\AppConnectionReadinessCommand;
use App\Console\Commands\CoreManualQaAccountsCommand;
use App\Console\Commands\GrantTuApiClientAbilityCommand;
use App\Console\Commands\ImportKpMasterDataCommand;
use App\Console\Commands\IssueTuApiClientCommand;
use App\Console\Commands\LabAccessDryRunCommand;
use App\Console\Commands\LabAppReadinessCommand;
use App\Console\Commands\ProvisionMasterProfileUsersCommand;
use App\Console\Commands\PruneCoreApiRequestLogsCommand;
use App\Console\Commands\ReconcileProfileUserLinksCommand;
use App\Console\Commands\RollbackKpImportCommand;
use App\Console\Commands\SetupTuAppAccessCommand;
use App\Console\Commands\TuConnectionReadinessCommand;
use App\Http\Middleware\AuthenticateCoreApiClient;
use App\Http\Middleware\AuthenticateApiToken;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        AppConnectionReadinessCommand::class,
        CoreManualQaAccountsCommand::class,
        GrantTuApiClientAbilityCommand::class,
        ImportKpMasterDataCommand::class,
        IssueTuApiClientCommand::class,
        LabAccessDryRunCommand::class,
        LabAppReadinessCommand::class,
        ProvisionMasterProfileUsersCommand::class,
        PruneCoreApiRequestLogsCommand::class,
        ReconcileProfileUserLinksCommand::class,
        RollbackKpImportCommand::class,
        SetupTuAppAccessCommand::class,
        TuConnectionReadinessCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn ($request) => $request->is('profile*') || $request->is('profil-saya*')
            ? '/profile/login'
            : '/admin/login');

        $middleware->alias([
            'auth.api' => AuthenticateApiToken::class,
            'auth.core-api-client' => AuthenticateCoreApiClient::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
