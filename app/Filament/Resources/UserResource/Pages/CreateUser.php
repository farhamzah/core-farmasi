<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (filled($data['password'] ?? null)) {
            $data['must_change_password'] = true;
            $data['password_changed_at'] = null;
        }

        return $data;
    }
}
