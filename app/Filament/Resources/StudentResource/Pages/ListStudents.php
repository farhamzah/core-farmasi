<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('report')
                ->label('Laporan')
                ->icon('heroicon-o-document-chart-bar')
                ->url(route('admin.reports.show', 'students')),
            CreateAction::make(),
        ];
    }
}
