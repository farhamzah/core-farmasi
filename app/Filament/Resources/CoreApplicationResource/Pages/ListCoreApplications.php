<?php

namespace App\Filament\Resources\CoreApplicationResource\Pages;

use App\Filament\Resources\CoreApplicationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCoreApplications extends ListRecords
{
    protected static string $resource = CoreApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
