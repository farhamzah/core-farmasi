<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadershipAssignmentResource\Pages;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Faculty;
use App\Models\LeadershipAssignment;
use App\Models\Lecturer;
use App\Models\StudyProgram;
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

class LeadershipAssignmentResource extends Resource
{
    protected static ?string $model = LeadershipAssignment::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-briefcase';

    protected static string|UnitEnum|null $navigationGroup = 'Organization';

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'Jabatan Struktural';

    protected static ?string $pluralModelLabel = 'Jabatan Struktural';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Jabatan')
                    ->schema([
                        Forms\Components\Select::make('position_type')
                            ->label('Jenis Jabatan')
                            ->options(self::positionTypeOptions())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($set, ?string $state): mixed => $set('position_title', self::defaultPositionTitle($state)))
                            ->helperText('Sumber resmi jabatan seperti Dekan/Kaprodi.'),
                        Forms\Components\TextInput::make('position_title')
                            ->label('Nama Jabatan Tampil')
                            ->helperText('Boleh otomatis mengikuti Jenis Jabatan, atau disesuaikan seperti Ketua Program Studi S1 Farmasi.')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('decree_number')
                            ->label('Nomor SK')
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Unit')
                    ->schema([
                        Forms\Components\Select::make('unit_type')
                            ->label('Jenis Unit')
                            ->options(self::unitTypeOptions())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($set): mixed => $set('unit_id', null)),
                        Forms\Components\Select::make('unit_id')
                            ->label('Pilih Unit')
                            ->options(fn ($get): array => self::unitOptions($get('unit_type')))
                            ->searchable()
                            ->preload()
                            ->helperText('Pilih unit sesuai jenis unit. Boleh kosong untuk jabatan umum fakultas jika tidak perlu target spesifik.'),
                    ])
                    ->columns(2),
                Section::make('Pejabat')
                    ->schema([
                        Forms\Components\Select::make('person_type')
                            ->label('Jenis Pejabat')
                            ->options(self::personTypeOptions())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($set): mixed => $set('person_id', null)),
                        Forms\Components\Select::make('person_id')
                            ->label('Pilih Pejabat')
                            ->options(fn ($get): array => self::personOptions($get('person_type')))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Cari berdasarkan nama atau email. Nama bergelar dosen otomatis diambil dari profil dosen Core.'),
                    ])
                    ->columns(2),
                Section::make('Arsip SK Khusus')
                    ->schema([
                        Forms\Components\TextInput::make('title_prefix')
                            ->label('Gelar Depan')
                            ->helperText('Opsional. Kosongkan agar mengikuti gelar dari profil dosen.')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('title_suffix')
                            ->label('Gelar Belakang')
                            ->helperText('Opsional. Kosongkan agar mengikuti gelar dari profil dosen.')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('official_name_snapshot')
                            ->label('Snapshot Nama Resmi')
                            ->helperText('Opsional. Isi hanya jika nama di SK lama harus dipertahankan persis dan tidak mengikuti perubahan profil.')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columns(2),
                Section::make('Periode')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Tanggal Selesai')
                            ->rule('after_or_equal:start_date'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
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
                TextColumn::make('position_type')
                    ->label('Jenis Jabatan')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (?string $state): string => self::positionTypeOptions()[$state] ?? (string) $state)
                    ->searchable(),
                TextColumn::make('position_title')
                    ->label('Jabatan')
                    ->searchable(),
                TextColumn::make('unit_type')
                    ->label('Jenis Unit')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (?string $state): string => self::unitTypeOptions()[$state] ?? (string) $state),
                TextColumn::make('unit_label')
                    ->label('Unit'),
                TextColumn::make('person_type')
                    ->label('Jenis Pejabat')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): string => self::personTypeOptions()[$state] ?? (string) $state),
                TextColumn::make('person_display_name')
                    ->label('Pejabat')
                    ->searchable(),
                TextColumn::make('decree_number')
                    ->label('Nomor SK')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date('d M Y')
                    ->sortable(),
                BooleanColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('position_type')
                    ->label('Jenis Jabatan')
                    ->options(self::positionTypeOptions()),
                SelectFilter::make('unit_type')
                    ->label('Jenis Unit')
                    ->options(self::unitTypeOptions()),
                SelectFilter::make('person_type')
                    ->label('Jenis Pejabat')
                    ->options(self::personTypeOptions()),
                TernaryFilter::make('is_active')
                    ->label('Aktif'),
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
            'index' => Pages\ListLeadershipAssignments::route('/'),
            'create' => Pages\CreateLeadershipAssignment::route('/create'),
            'edit' => Pages\EditLeadershipAssignment::route('/{record}/edit'),
        ];
    }

    public static function positionTypeOptions(): array
    {
        return config('core_leadership.position_types', []);
    }

    public static function unitTypeOptions(): array
    {
        return config('core_leadership.unit_types', []);
    }

    public static function personTypeOptions(): array
    {
        return config('core_leadership.person_types', []);
    }

    public static function unitOptions(?string $unitType): array
    {
        return match ($unitType) {
            'faculty' => Faculty::query()->orderBy('name')->pluck('name', 'id')->all(),
            'department', 'laboratory' => Department::query()->orderBy('name')->pluck('name', 'id')->all(),
            'study_program' => StudyProgram::query()->orderBy('name')->pluck('name', 'id')->all(),
            default => [],
        };
    }

    public static function personOptions(?string $personType): array
    {
        return match ($personType) {
            'lecturer' => Lecturer::query()
                ->orderBy('name')
                ->get()
                ->mapWithKeys(fn (Lecturer $lecturer): array => [
                    $lecturer->id => trim($lecturer->display_name_with_title.' - '.$lecturer->email),
                ])
                ->all(),
            'employee' => Employee::query()
                ->orderBy('name')
                ->get()
                ->mapWithKeys(fn (Employee $employee): array => [
                    $employee->id => trim($employee->name.' - '.$employee->email),
                ])
                ->all(),
            default => [],
        };
    }

    private static function defaultPositionTitle(?string $positionType): ?string
    {
        return self::positionTypeOptions()[$positionType] ?? null;
    }
}
