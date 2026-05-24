<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\Role;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use BackedEnum;
use UnitEnum;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Access Control';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Role Global';

    protected static ?string $pluralModelLabel = 'Role Global';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Role Global')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Role global Core, berbeda dari role aplikasi.'),
                        Forms\Components\TextInput::make('label')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Toggle::make('active')
                            ->label('Active')
                            ->default(true),
                        Forms\Components\Textarea::make('description')
                            ->rows(4)
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
                TextColumn::make('name')->label('Slug')->sortable()->searchable()->badge()->color('info'),
                TextColumn::make('label')->sortable()->searchable(),
                BooleanColumn::make('active')->label('Active'),
                TextColumn::make('created_at')->dateTime('d MMM Y')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('active')->label('Active'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
