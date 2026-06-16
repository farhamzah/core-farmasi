<?php

namespace App\Filament\Resources\UserAppAccessResource\Pages;

use App\Filament\Resources\UserAppAccessResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUserAppAccesses extends ListRecords
{
    protected static string $resource = UserAppAccessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bulkAccess')
                ->label('Bulk App Access')
                ->icon('heroicon-o-user-plus')
                ->color('gray')
                ->url('/admin/bulk-user-app-access'),
            CreateAction::make(),
        ];
    }
}
