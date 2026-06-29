<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExternalPersonResource\Pages;
use App\Models\ExternalPerson;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ExternalPersonResource extends Resource
{
    protected static ?string $model = ExternalPerson::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 45;

    protected static ?string $modelLabel = 'Mitra Eksternal';

    protected static ?string $pluralModelLabel = 'Mitra Eksternal';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Identitas Eksternal')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Telepon / WhatsApp')
                            ->tel()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('external_number')
                            ->label('Nomor Eksternal')
                            ->unique(ignoreRecord: true)
                            ->maxLength(100)
                            ->helperText('Opsional. Gunakan jika mitra punya nomor registrasi/internal.'),
                        Forms\Components\TextInput::make('identity_number')
                            ->label('NIK / Identitas')
                            ->maxLength(100),
                        Forms\Components\Select::make('status')
                            ->options(self::statusOptions())
                            ->default('active')
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Instansi / Profesi')
                    ->schema([
                        Forms\Components\TextInput::make('institution_name')
                            ->label('Instansi / Perusahaan')
                            ->maxLength(255),
                        Forms\Components\Select::make('institution_type')
                            ->label('Jenis Instansi')
                            ->options(self::institutionTypeOptions())
                            ->searchable(),
                        Forms\Components\TextInput::make('position_title')
                            ->label('Jabatan / Posisi')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('profession')
                            ->label('Profesi')
                            ->maxLength(255)
                            ->helperText('Contoh: Apoteker, praktisi industri, dosen luar.'),
                    ])
                    ->columns(2),
                Section::make('Kontak & Akun')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->label('Alamat')
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('user_id')
                            ->label('User Core')
                            ->relationship('user', 'email')
                            ->searchable()
                            ->preload()
                            ->unique(ignoreRecord: true)
                            ->helperText('Hubungkan ke User dengan Identity Type External bila orang ini perlu login.'),
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
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('institution_name')->label('Instansi')->searchable()->sortable(),
                TextColumn::make('institution_type')
                    ->label('Jenis')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (?string $state): string => self::institutionTypeOptions()[$state] ?? (string) $state),
                TextColumn::make('position_title')->label('Posisi')->searchable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('phone')->label('Telepon')->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'active' ? 'success' : 'gray')
                    ->formatStateUsing(fn (?string $state): string => self::statusOptions()[$state] ?? (string) $state),
                TextColumn::make('user.email')->label('User Core')->searchable(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('institution_type')
                    ->label('Jenis Instansi')
                    ->options(self::institutionTypeOptions()),
                SelectFilter::make('status')
                    ->options(self::statusOptions()),
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
            'index' => Pages\ListExternalPeople::route('/'),
            'create' => Pages\CreateExternalPerson::route('/create'),
            'edit' => Pages\EditExternalPerson::route('/{record}/edit'),
        ];
    }

    public static function institutionTypeOptions(): array
    {
        return [
            'industry' => 'Industri',
            'hospital' => 'Rumah Sakit',
            'pharmacy' => 'Apotek',
            'university' => 'Universitas / Kampus Lain',
            'clinic' => 'Klinik',
            'government' => 'Instansi Pemerintah',
            'other' => 'Lainnya',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];
    }
}
