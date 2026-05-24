<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\CoreAppLauncherService;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class CoreAppLauncher extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rocket-launch';

    protected static string|UnitEnum|null $navigationGroup = 'Access Control';

    protected static ?string $navigationLabel = 'Aplikasi Saya';

    protected static ?string $title = 'Aplikasi Saya';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.core-app-launcher';

    public function getSubheading(): string | Htmlable | null
    {
        return 'Aplikasi internal yang dapat Anda akses berdasarkan hak akses Core. Aplikasi tujuan tetap wajib login sendiri.';
    }

    public function getAppsProperty(): array
    {
        /** @var User $user */
        $user = Filament::auth()->user();

        return app(CoreAppLauncherService::class)->appsForUser($user);
    }

    public static function getSlug(?\Filament\Panel $panel = null): string
    {
        return 'app-launcher';
    }
}
