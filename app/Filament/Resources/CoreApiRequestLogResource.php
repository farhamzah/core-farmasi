<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CoreApiRequestLogResource\Pages;
use App\Models\CoreApiClient;
use App\Models\CoreApiRequestLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class CoreApiRequestLogResource extends Resource
{
    protected static ?string $model = CoreApiRequestLog::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'API & System';

    protected static ?int $navigationSort = 45;

    protected static ?string $modelLabel = 'API Request Log';

    protected static ?string $pluralModelLabel = 'API Request Logs';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
                TextColumn::make('app_code')
                    ->label('App')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client_id')
                    ->label('Client ID')
                    ->searchable()
                    ->limit(28),
                TextColumn::make('method')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('path')
                    ->searchable()
                    ->limit(48),
                TextColumn::make('status_code')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 500 => 'danger',
                        $state >= 400 => 'warning',
                        $state >= 300 => 'info',
                        default => 'success',
                    }),
                TextColumn::make('ability')
                    ->badge()
                    ->color('primary')
                    ->searchable(),
                TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->suffix(' ms')
                    ->sortable(),
                IconColumn::make('is_success')
                    ->label('Success')
                    ->boolean(),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('core_api_client_id')
                    ->label('Client')
                    ->options(fn (): array => CoreApiClient::query()->orderBy('name')->pluck('name', 'id')->all()),
                SelectFilter::make('app_code')
                    ->label('App Code')
                    ->options(fn (): array => CoreApiRequestLog::query()->whereNotNull('app_code')->distinct()->orderBy('app_code')->pluck('app_code', 'app_code')->all()),
                SelectFilter::make('ability')
                    ->options(config('core_api.client_abilities', [])),
                SelectFilter::make('status_code')
                    ->label('Status')
                    ->options(fn (): array => CoreApiRequestLog::query()->whereNotNull('status_code')->distinct()->orderBy('status_code')->pluck('status_code', 'status_code')->mapWithKeys(fn ($value): array => [(string) $value => (string) $value])->all()),
                TernaryFilter::make('is_success')->label('Success'),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoreApiRequestLogs::route('/'),
        ];
    }
}
