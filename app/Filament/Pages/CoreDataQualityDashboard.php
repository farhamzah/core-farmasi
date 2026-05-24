<?php

namespace App\Filament\Pages;

use App\Services\CoreDataQualityService;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class CoreDataQualityDashboard extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|UnitEnum|null $navigationGroup = 'Data Quality';

    protected static ?string $navigationLabel = 'Data Quality';

    protected static ?string $title = 'Data Quality Dashboard';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.core-data-quality-dashboard';

    public function getDataQualitySummaryProperty(): array
    {
        return app(CoreDataQualityService::class)->summary();
    }

    public static function getSlug(?\Filament\Panel $panel = null): string
    {
        return 'data-quality';
    }
}
