<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Models\Student;
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

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'Mahasiswa';

    protected static ?string $pluralModelLabel = 'Mahasiswa';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Identitas Mahasiswa')
                    ->schema([
                        Forms\Components\TextInput::make('student_number')
                            ->label('NIM')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->helperText('Dipakai untuk reset password awal. Tidak ditampilkan default di tabel.'),
                    ])
                    ->columns(2),
                Section::make('Akademik')
                    ->schema([
                        Forms\Components\Select::make('study_program_id')
                            ->label('Program Studi')
                            ->relationship('studyProgram', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\DatePicker::make('enrolled_at')
                            ->label('Tanggal Masuk'),
                        Forms\Components\TextInput::make('status')
                            ->required()
                            ->maxLength(50),
                        Forms\Components\Toggle::make('active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Akun Terhubung')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'email')
                            ->searchable()
                            ->preload()
                            ->unique(ignoreRecord: true)
                            ->helperText('Hubungkan ke akun Core bila tersedia.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student_number')->label('NIM')->sortable()->searchable(),
                TextColumn::make('name')->label('Nama')->sortable()->searchable(),
                TextColumn::make('email')->sortable()->searchable(),
                TextColumn::make('studyProgram.name')->label('Program Studi')->sortable(),
                TextColumn::make('user.email')->label('Linked User')->searchable()->toggleable(),
                TextColumn::make('status')->badge()->searchable(),
                TextColumn::make('birth_date')
                    ->label('Tanggal Lahir')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                BooleanColumn::make('active')->label('Active'),
                TextColumn::make('created_at')->dateTime('d M Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('study_program_id')
                    ->label('Program Studi')
                    ->relationship('studyProgram', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options(fn (): array => Student::query()->whereNotNull('status')->distinct()->orderBy('status')->pluck('status', 'status')->all()),
                TernaryFilter::make('active')->label('Active'),
                TernaryFilter::make('user_id')->label('Akun Terhubung')->nullable(),
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
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
