<?php

namespace App\Filament\Resources\LecturerResource\Pages;

use App\Filament\Resources\LecturerResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLecturers extends ListRecords
{
    protected static string $resource = LecturerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('report')
                ->label('Laporan')
                ->icon('heroicon-o-document-chart-bar')
                ->url(route('admin.reports.show', 'lecturers')),
            CreateAction::make(),
        ];
    }
}
