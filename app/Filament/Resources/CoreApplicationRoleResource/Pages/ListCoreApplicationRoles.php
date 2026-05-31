<?php

namespace App\Filament\Resources\CoreApplicationRoleResource\Pages;

use App\Filament\Resources\CoreApplicationRoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCoreApplicationRoles extends ListRecords
{
    protected static string $resource = CoreApplicationRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
