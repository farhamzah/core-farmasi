<?php

namespace App\Filament\Resources\UserAppAccessResource\Pages;

use App\Filament\Resources\UserAppAccessResource;
use App\Models\UserAppAccess;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListUserAppAccesses extends ListRecords
{
    protected static string $resource = UserAppAccessResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua')
                ->badge((string) UserAppAccess::query()->count())
                ->badgeColor('gray'),
            'aktif' => Tab::make('Aktif')
                ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                ->badge((string) UserAppAccess::query()->where('is_active', true)->count())
                ->badgeColor('success'),
            'nonaktif' => Tab::make('Nonaktif')
                ->query(fn (Builder $query): Builder => $query->where('is_active', false))
                ->badge((string) UserAppAccess::query()->where('is_active', false)->count())
                ->badgeColor('warning'),
            'kp' => $this->makeAppTab('KP Farmasi', 'kp-farmasi'),
            'tu' => $this->makeAppTab('TU Farmasi', 'tu-farmasi'),
            'ta' => $this->makeAppTab('TA Farmasi', 'ta-farmasi'),
            'lab' => $this->makeAppTab('Lab Farmasi', 'lab-farmasi'),
            'obe' => $this->makeAppTab('OBE Farmasi', 'obe-farmasi'),
            'dosen' => $this->makeAppTab('Dosen Farmasi', 'dosen-farmasi'),
            'kppspa' => $this->makeAppTab('KPPSPA', 'kppspa-farmasi'),
        ];
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Gunakan tab cepat untuk aplikasi utama, tambah filter bila perlu, lalu pilih beberapa baris sekaligus untuk menonaktifkan, mengaktifkan ulang, atau menghapus akses.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bulkAccess')
                ->label('Bulk App Access')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->url('/admin/bulk-user-app-access'),
            CreateAction::make(),
        ];
    }

    protected function makeAppTab(string $label, string $appCode): Tab
    {
        return Tab::make($label)
            ->query(fn (Builder $query): Builder => $query->where('app_code', $appCode))
            ->badge((string) UserAppAccess::query()->where('app_code', $appCode)->count())
            ->badgeColor('info');
    }
}
