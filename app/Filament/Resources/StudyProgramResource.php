<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudyProgramResource\Pages;
use App\Models\StudyProgram;
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

class StudyProgramResource extends Resource
{
    protected static ?string $model = StudyProgram::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 60;

    protected static ?string $modelLabel = 'Program Studi';

    protected static ?string $pluralModelLabel = 'Program Studi';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Program Studi')
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
                        Forms\Components\Select::make('department_id')
                            ->label('Department')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('head_lecturer_id')
                            ->label('Kaprodi Referensi')
                            ->relationship('headLecturer', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Referensi lama tetap dipertahankan. Jabatan resmi Kaprodi memakai Leadership Assignment.'),
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
                TextColumn::make('department.name')->label('Department'),
                TextColumn::make('headLecturer.name')->label('Head Lecturer'),
                BooleanColumn::make('active')->label('Active'),
                TextColumn::make('created_at')->dateTime('d MMM Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
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
            'index' => Pages\ListStudyPrograms::route('/'),
            'create' => Pages\CreateStudyProgram::route('/create'),
            'edit' => Pages\EditStudyProgram::route('/{record}/edit'),
        ];
    }
}
