<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FacultyResource\Pages;
use App\Models\Faculty;
use BackedEnum;
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

class FacultyResource extends Resource
{
    protected static ?string $model = Faculty::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-library';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 45;

    protected static ?string $modelLabel = 'Fakultas';

    protected static ?string $pluralModelLabel = 'Fakultas';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Fakultas')
                    ->schema([
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
                TextColumn::make('departments_count')->label('Departemen')->counts('departments')->sortable(),
                TextColumn::make('study_programs_count')->label('Program Studi')->counts('studyPrograms')->sortable(),
                BooleanColumn::make('active')->label('Active'),
                TextColumn::make('created_at')->dateTime('d M Y')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('active')->label('Active'),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFaculties::route('/'),
            'create' => Pages\CreateFaculty::route('/create'),
            'edit' => Pages\EditFaculty::route('/{record}/edit'),
        ];
    }
}
