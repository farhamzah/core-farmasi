<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CoreApiClientResource\Pages;
use App\Models\CoreApiClient;
use App\Models\CoreApplication;
use App\Services\CoreApiClientCredentialService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class CoreApiClientResource extends Resource
{
    protected static ?string $model = CoreApiClient::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'API & System';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'API Client';

    protected static ?string $pluralModelLabel = 'API Clients';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Client')
                    ->schema([
                        Forms\Components\Select::make('core_application_id')
                            ->label('Aplikasi')
                            ->relationship('application', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('app_code')
                            ->label('App Code')
                            ->options(fn (): array => CoreApplication::query()->orderBy('name')->pluck('name', 'app_code')->all())
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Client')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('client_id')
                            ->label('Client ID')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Client ID bukan secret. Secret hanya ditampilkan sekali saat create/rotate.'),
                    ])
                    ->columns(2),
                Section::make('Access')
                    ->schema([
                        Forms\Components\CheckboxList::make('abilities')
                            ->label('Abilities')
                            ->options(config('core_api.client_abilities', []))
                            ->columns(2),
                        Forms\Components\Textarea::make('allowed_ips')
                            ->label('Allowed IPs')
                            ->helperText('Opsional. Isi satu IP per baris. Kosong berarti tidak dibatasi di tahap skeleton.')
                            ->afterStateHydrated(function (Forms\Components\Textarea $component, mixed $state): void {
                                if (is_array($state)) {
                                    $component->state(implode(PHP_EOL, $state));
                                }
                            })
                            ->dehydrateStateUsing(fn ($state): ?array => filled($state)
                                ? collect(preg_split('/\R/', (string) $state))->map(fn ($ip) => trim($ip))->filter()->values()->all()
                                : null)
                            ->rows(3),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->maxLength(65535),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('app_code')
                    ->label('App Code')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client_id')
                    ->label('Client ID')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('abilities')
                    ->label('Abilities')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state): string => is_array($state) ? implode(', ', $state) : (string) $state),
                BooleanColumn::make('is_active')
                    ->label('Aktif'),
                TextColumn::make('revoked_at')
                    ->label('Revoked')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('last_used_at')
                    ->label('Last Used')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('last_rotated_at')
                    ->label('Last Rotated')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('app_code')
                    ->label('Aplikasi')
                    ->options(fn (): array => CoreApplication::query()->orderBy('name')->pluck('name', 'app_code')->all()),
                TernaryFilter::make('is_active')->label('Aktif'),
                TernaryFilter::make('revoked_at')
                    ->label('Revoked')
                    ->nullable(),
            ])
            ->actions([
                EditAction::make(),
                Action::make('rotateSecret')
                    ->label('Rotate Secret')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (CoreApiClient $record): void {
                        $secret = app(CoreApiClientCredentialService::class)->rotateSecret($record, auth()->user());

                        Notification::make()
                            ->warning()
                            ->title('Secret baru dibuat. Simpan sekarang, karena tidak akan ditampilkan lagi.')
                            ->body($secret)
                            ->send();
                    }),
                Action::make('revoke')
                    ->label('Revoke')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (CoreApiClient $record): bool => ! $record->isRevoked())
                    ->action(function (CoreApiClient $record): void {
                        app(CoreApiClientCredentialService::class)->revoke($record, auth()->user());

                        Notification::make()
                            ->success()
                            ->title('API client revoked.')
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoreApiClients::route('/'),
            'create' => Pages\CreateCoreApiClient::route('/create'),
            'edit' => Pages\EditCoreApiClient::route('/{record}/edit'),
        ];
    }
}
