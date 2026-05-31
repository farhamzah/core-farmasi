<?php

namespace App\Filament\Resources\UserAppAccessResource\Pages;

use App\Filament\Resources\UserAppAccessResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUserAppAccesses extends ListRecords
{
    protected static string $resource = UserAppAccessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
