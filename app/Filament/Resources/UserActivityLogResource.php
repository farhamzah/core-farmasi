<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserActivityLogResource\Pages;
use App\Models\UserActivityLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class UserActivityLogResource extends Resource
{
    protected static ?string $model = UserActivityLog::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Audit / Logs';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'User Activity Log';

    protected static ?string $pluralModelLabel = 'User Activity Logs';

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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.email')->label('User')->searchable(),
                TextColumn::make('action')->sortable()->searchable()->badge()->color('info'),
                TextColumn::make('ip_address')->label('IP Address'),
                TextColumn::make('user_agent')->limit(60),
                TextColumn::make('created_at')->dateTime('d MMM Y H:i')->sortable(),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserActivityLogs::route('/'),
        ];
    }
}
