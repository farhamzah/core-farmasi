<?php

namespace App\Filament\Resources\CoreApiClientResource\Pages;

use App\Filament\Resources\CoreApiClientResource;
use App\Models\CoreApplication;
use App\Services\CoreApiClientCredentialService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCoreApiClient extends CreateRecord
{
    protected static string $resource = CoreApiClientResource::class;

    protected ?string $plainSecret = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $service = app(CoreApiClientCredentialService::class);
        $this->plainSecret = $service->generatePlainSecret();
        $application = CoreApplication::query()->where('app_code', $data['app_code'])->first();

        $data['core_application_id'] = $data['core_application_id'] ?? $application?->id;
        $data['client_id'] = $service->generateClientId($data['app_code']);
        $data['secret_hash'] = $service->hashSecret($this->plainSecret);
        $data['created_by'] = auth()->id();
        $data['rotated_by'] = auth()->id();
        $data['last_rotated_at'] = now();

        return $data;
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->warning()
            ->title('Secret API client dibuat. Simpan sekarang, karena tidak akan ditampilkan lagi.')
            ->body($this->plainSecret)
            ->send();
    }
}
