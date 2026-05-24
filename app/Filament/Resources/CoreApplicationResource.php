<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CoreApplicationResource\Pages;
use App\Models\CoreApplication;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class CoreApplicationResource extends Resource
{
    protected static ?string $model = CoreApplication::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-window';

    protected static string|UnitEnum|null $navigationGroup = 'Access Control';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Aplikasi';

    protected static ?string $pluralModelLabel = 'Aplikasi';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Aplikasi')
                    ->schema([
                        Forms\Components\TextInput::make('app_code')
                            ->label('App Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->rule('alpha_dash')
                            ->helperText('Kode stabil untuk integrasi internal, contoh: kp-farmasi.'),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('URL & Tampilan')
                    ->schema([
                        Forms\Components\TextInput::make('base_url')
                            ->label('Base URL')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('admin_url')
                            ->label('Admin URL')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('icon')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('color')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Status & Keamanan')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                        Forms\Components\Toggle::make('is_public_visible')
                            ->label('Tampil Public')
                            ->default(false)
                            ->helperText('Core dan aplikasi internal sensitif sebaiknya tidak public visible.'),
                        Forms\Components\Toggle::make('requires_login')
                            ->label('Wajib Login')
                            ->default(true),
                        Forms\Components\Toggle::make('is_sensitive')
                            ->label('Sensitive')
                            ->default(false),
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
                TextColumn::make('app_code')
                    ->label('App Code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                BooleanColumn::make('is_active')
                    ->label('Aktif'),
                BooleanColumn::make('is_public_visible')
                    ->label('Public'),
                BooleanColumn::make('requires_login')
                    ->label('Login'),
                BooleanColumn::make('is_sensitive')
                    ->label('Sensitive'),
                TextColumn::make('admin_url')
                    ->label('Admin URL')
                    ->searchable()
                    ->limit(32),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Aktif'),
                TernaryFilter::make('is_public_visible')->label('Public'),
                TernaryFilter::make('requires_login')->label('Wajib Login'),
                TernaryFilter::make('is_sensitive')->label('Sensitive'),
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
            'index' => Pages\ListCoreApplications::route('/'),
            'create' => Pages\CreateCoreApplication::route('/create'),
            'edit' => Pages\EditCoreApplication::route('/{record}/edit'),
        ];
    }
}
