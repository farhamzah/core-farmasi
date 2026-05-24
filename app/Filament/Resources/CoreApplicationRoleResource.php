<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CoreApplicationRoleResource\Pages;
use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class CoreApplicationRoleResource extends Resource
{
    protected static ?string $model = CoreApplicationRole::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-key';

    protected static string|UnitEnum|null $navigationGroup = 'Access Control';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'Role Aplikasi';

    protected static ?string $pluralModelLabel = 'Role Aplikasi';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Role Aplikasi')
                    ->schema([
                        Forms\Components\Select::make('core_application_id')
                            ->label('Aplikasi')
                            ->relationship('application', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('app_code')
                            ->label('App Code')
                            ->options(fn (): array => self::applicationOptions())
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('role_slug')
                            ->label('Role Slug')
                            ->required()
                            ->rule('alpha_dash')
                            ->maxLength(255)
                            ->helperText('Role aplikasi dinamis. Jangan dicampur dengan role global.'),
                        Forms\Components\TextInput::make('role_name')
                            ->label('Nama Role')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Urutan')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('application.name')
                    ->label('Aplikasi')
                    ->searchable(),
                TextColumn::make('app_code')
                    ->label('App Code')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role_slug')
                    ->label('Role Slug')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role_name')
                    ->label('Nama Role')
                    ->searchable()
                    ->sortable(),
                BooleanColumn::make('is_active')
                    ->label('Aktif'),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('app_code')
                    ->label('Aplikasi')
                    ->options(fn (): array => self::applicationOptions()),
                TernaryFilter::make('is_active')->label('Aktif'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoreApplicationRoles::route('/'),
            'create' => Pages\CreateCoreApplicationRole::route('/create'),
            'edit' => Pages\EditCoreApplicationRole::route('/{record}/edit'),
        ];
    }

    public static function applicationOptions(): array
    {
        return CoreApplication::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'app_code')
            ->all();
    }
}
