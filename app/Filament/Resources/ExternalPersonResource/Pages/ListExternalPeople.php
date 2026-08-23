<?php

namespace App\Filament\Resources\ExternalPersonResource\Pages;

use App\Filament\Resources\ExternalPersonResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExternalPeople extends ListRecords
{
    protected static string $resource = ExternalPersonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('report')
                ->label('Laporan')
                ->icon('heroicon-o-document-chart-bar')
                ->url(route('admin.reports.show', 'external-people')),
            CreateAction::make(),
        ];
    }
}
