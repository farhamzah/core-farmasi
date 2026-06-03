<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages;
use App\Models\Department;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use BackedEnum;
use UnitEnum;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'Departemen';

    protected static ?string $pluralModelLabel = 'Departemen';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Departemen')
                    ->schema([
                        Forms\Components\Select::make('faculty_id')
                            ->label('Fakultas')
                            ->relationship('faculty', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Departemen berada di bawah fakultas. Untuk saat ini gunakan Fakultas Farmasi.'),
                        Forms\Components\TextInput::make('code')
                            ->label('Kode')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Toggle::make('active')
                            ->label('Active')
                            ->default(true),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
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
                TextColumn::make('code')->label('Kode')->sortable()->searchable()->badge()->color('info'),
                TextColumn::make('name')->label('Nama')->sortable()->searchable(),
                TextColumn::make('faculty.name')->label('Fakultas')->sortable(),
                BooleanColumn::make('active')->label('Active'),
                TextColumn::make('created_at')->dateTime('d M Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('faculty_id')
                    ->label('Fakultas')
                    ->relationship('faculty', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('active')->label('Active'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}
