<?php

namespace App\Filament\Resources\CoreApplicationRoleResource\Pages;

use App\Filament\Resources\CoreApplicationRoleResource;
use App\Models\CoreApplication;
use Filament\Resources\Pages\CreateRecord;

class CreateCoreApplicationRole extends CreateRecord
{
    protected static string $resource = CoreApplicationRoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['core_application_id'] ??= CoreApplication::query()
            ->where('app_code', $data['app_code'] ?? null)
            ->value('id');

        return $data;
    }
}
