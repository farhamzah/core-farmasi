<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Employee;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-identification';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'Tendik / Staff';

    protected static ?string $pluralModelLabel = 'Tendik / Staff';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Identitas Staff')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('employee_number')
                            ->label('Employee Number')
                            ->unique(ignoreRecord: true)
                            ->maxLength(100)
                            ->helperText('Nomor pegawai/internal bila tersedia.'),
                        Forms\Components\TextInput::make('national_id_number')
                            ->label('National ID Number')
                            ->maxLength(100),
                        Forms\Components\Select::make('staff_type')
                            ->label('Staff Type')
                            ->options(self::staffTypeOptions())
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options(self::statusOptions())
                            ->default('active')
                            ->required(),
                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Birth Date'),
                        Forms\Components\Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                            ]),
                    ])
                    ->columns(2),
                Section::make('Penempatan')
                    ->schema([
                        Forms\Components\Select::make('department_id')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('study_program_id')
                            ->relationship('studyProgram', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('position_title')
                            ->label('Position Title')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Kontak')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),
                        Forms\Components\Textarea::make('address')
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),
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
                        Forms\Components\Textarea::make('notes')
                            ->rows(4)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee_number')
                    ->label('Employee No.')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('staff_type')
                    ->label('Staff Type')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (?string $state): string => self::staffTypeOptions()[$state] ?? (string) $state)
                    ->searchable(),
                TextColumn::make('department.name')
                    ->label('Department'),
                TextColumn::make('studyProgram.name')
                    ->label('Study Program'),
                TextColumn::make('position_title')
                    ->label('Position')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'active' ? 'success' : 'gray')
                    ->formatStateUsing(fn (?string $state): string => self::statusOptions()[$state] ?? (string) $state),
                TextColumn::make('user.email')
                    ->label('Linked User')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('staff_type')
                    ->label('Staff Type')
                    ->options(self::staffTypeOptions()),
                SelectFilter::make('status')
                    ->options(self::statusOptions()),
                SelectFilter::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('study_program_id')
                    ->label('Study Program')
                    ->relationship('studyProgram', 'name')
                    ->searchable()
                    ->preload(),
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function staffTypeOptions(): array
    {
        return [
            'tendik' => 'Tendik',
            'admin' => 'Admin',
            'staf_tu' => 'Staf TU',
            'laboran' => 'Laboran',
            'other' => 'Other',
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
