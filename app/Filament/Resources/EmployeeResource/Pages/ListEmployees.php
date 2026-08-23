<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('report')
                ->label('Laporan')
                ->icon('heroicon-o-document-chart-bar')
                ->url(route('admin.reports.show', 'employees')),
            CreateAction::make(),
        ];
    }
}
