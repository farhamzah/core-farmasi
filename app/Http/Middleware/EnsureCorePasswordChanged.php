<?php

namespace App\Http\Middleware;

use App\Filament\Pages\ChangePassword;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCorePasswordChanged
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        if ($request->routeIs(ChangePassword::getRouteName())) {
            return $next($request);
        }

        if ($request->routeIs('filament.admin.auth.logout')) {
            return $next($request);
        }

        return redirect(ChangePassword::getUrl());
    }
}
