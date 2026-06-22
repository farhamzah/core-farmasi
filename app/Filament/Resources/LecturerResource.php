<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LecturerResource\Pages;
use App\Models\Lecturer;
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

class LecturerResource extends Resource
{
    protected static ?string $model = Lecturer::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'Dosen';

    protected static ?string $pluralModelLabel = 'Dosen';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Identitas Dosen')
                    ->schema([
                        Forms\Components\TextInput::make('lecturer_number')
                            ->label('Nomor Utama Dosen')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),
                        Forms\Components\TextInput::make('national_id_number')
                            ->label('NIK / No. KTP')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('nip')
                            ->label('NIP')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('nidn')
                            ->label('NIDN')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('nidk')
                            ->label('NIDK')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('nuptk')
                            ->label('NUPTK')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('front_title')
                            ->label('Gelar Depan')
                            ->helperText('Contoh: Dr., apt. Nama dasar tetap di field Nama.')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('back_title')
                            ->label('Gelar Belakang')
                            ->helperText('Contoh: M.Farm., S.Si. Sistem akan menampilkan: Nama, Gelar Belakang.')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->helperText('Dipakai untuk reset password awal. Tidak ditampilkan default di tabel.'),
                    ])
                    ->columns(3),
                Section::make('Penempatan')
                    ->schema([
                        Forms\Components\Select::make('department_id')
                            ->label('Department')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('study_program_id')
                            ->label('Program Studi')
                            ->relationship('studyProgram', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Toggle::make('active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Kontak & Akun')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'email')
                            ->searchable()
                            ->preload()
                            ->unique(ignoreRecord: true),
                        Forms\Components\Textarea::make('notes')
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
                TextColumn::make('lecturer_number')->label('Nomor Utama')->sortable()->searchable(),
                TextColumn::make('nidn')->label('NIDN')->searchable()->toggleable(),
                TextColumn::make('nip')->label('NIP')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nuptk')->label('NUPTK')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('national_id_number')->label('NIK')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')->label('Nama')->sortable()->searchable(),
                TextColumn::make('display_name_with_title')->label('Nama Bergelar')->toggleable(),
                TextColumn::make('email')->sortable()->searchable(),
                TextColumn::make('department.name')->label('Department')->sortable(),
                TextColumn::make('studyProgram.name')->label('Program Studi')->sortable(),
                TextColumn::make('user.email')->label('Linked User')->searchable()->toggleable(),
                TextColumn::make('birth_date')
                    ->label('Tanggal Lahir')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                BooleanColumn::make('active')->label('Active'),
                TextColumn::make('created_at')->dateTime('d M Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('study_program_id')
                    ->label('Program Studi')
                    ->relationship('studyProgram', 'name')
                    ->searchable()
                    ->preload(),
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
            'index' => Pages\ListLecturers::route('/'),
            'create' => Pages\CreateLecturer::route('/create'),
            'edit' => Pages\EditLecturer::route('/{record}/edit'),
        ];
    }
}
