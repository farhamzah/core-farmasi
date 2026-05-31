<?php

namespace App\Filament\Resources\LeadershipAssignmentResource\Pages;

use App\Filament\Resources\LeadershipAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLeadershipAssignments extends ListRecords
{
    protected static string $resource = LeadershipAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
