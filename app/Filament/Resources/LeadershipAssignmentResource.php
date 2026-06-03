<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadershipAssignmentResource\Pages;
use App\Models\LeadershipAssignment;
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
                            ->helperText('Sumber resmi jabatan seperti Dekan/Kaprodi.'),
                        Forms\Components\TextInput::make('position_title')
                            ->label('Nama Jabatan Tampil')
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
                            ->required(),
                        Forms\Components\TextInput::make('unit_id')
                            ->label('ID Unit')
                            ->numeric()
                            ->helperText('Untuk study_program gunakan ID Program Studi; untuk department/faculty gunakan ID Department. Boleh kosong untuk unit umum seperti fakultas.'),
                    ])
                    ->columns(2),
                Section::make('Pejabat')
                    ->schema([
                        Forms\Components\Select::make('person_type')
                            ->label('Jenis Pejabat')
                            ->options(self::personTypeOptions())
                            ->required(),
                        Forms\Components\TextInput::make('person_id')
                            ->label('ID Pejabat')
                            ->numeric()
                            ->required()
                            ->helperText('Isi ID Dosen jika jenis pejabat Dosen, atau ID Tendik/Staff jika jenis pejabat Tendik / Staff.'),
                        Forms\Components\TextInput::make('title_prefix')
                            ->label('Gelar Depan')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('title_suffix')
                            ->label('Gelar Belakang')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('official_name_snapshot')
                            ->label('Snapshot Nama Resmi')
                            ->helperText('Opsional. Jika diisi, nama ini dipakai untuk menjaga konsistensi dokumen historis.')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
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
}
