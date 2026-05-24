<?php

namespace App\Filament\Resources\CoreApiClientResource\Pages;

use App\Filament\Resources\CoreApiClientResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCoreApiClients extends ListRecords
{
    protected static string $resource = CoreApiClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
