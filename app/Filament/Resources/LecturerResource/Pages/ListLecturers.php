<?php

namespace App\Filament\Resources\LecturerResource\Pages;

use App\Filament\Resources\LecturerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLecturers extends ListRecords
{
    protected static string $resource = LecturerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
