<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserAppAccessResource\Pages;
use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\UserAppAccess;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class UserAppAccessResource extends Resource
{
    protected static ?string $model = UserAppAccess::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Access Control';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'User App Access';

    protected static ?string $pluralModelLabel = 'User App Accesses';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Akses Aplikasi')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'email')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('app_code')
                            ->label('Aplikasi')
                            ->options(fn (): array => self::applicationOptions())
                            ->live()
                            ->searchable()
                            ->required()
                            ->helperText('Aplikasi harus aktif di app registry Core.'),
                        Forms\Components\Select::make('role_slug')
                            ->label('Role Aplikasi')
                            ->options(fn ($get): array => self::roleOptions($get('app_code')))
                            ->searchable()
                            ->required()
                            ->helperText('Role berasal dari catalog role aplikasi, bukan role global.'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Permission & Periode')
                    ->schema([
                        Forms\Components\KeyValue::make('permissions')
                            ->keyLabel('Permission')
                            ->valueLabel('Value'),
                        Forms\Components\DateTimePicker::make('activated_at')
                            ->label('Activated At'),
                        Forms\Components\DateTimePicker::make('deactivated_at')
                            ->label('Deactivated At'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('User')->searchable(),
                TextColumn::make('user.email')->label('Email')->searchable()->toggleable(),
                TextColumn::make('application.name')->label('Aplikasi')->searchable(),
                TextColumn::make('app_code')->sortable()->searchable()->badge()->color('info'),
                TextColumn::make('role_slug')->sortable()->searchable()->badge()->color('primary'),
                TextColumn::make('application_role_name')->label('Nama Role'),
                BooleanColumn::make('is_active')->label('Active'),
                TextColumn::make('activated_at')->dateTime('d MMM Y H:i')->sortable(),
                TextColumn::make('deactivated_at')->dateTime('d MMM Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('app_code')
                    ->label('Aplikasi')
                    ->options(fn (): array => self::applicationOptions()),
                SelectFilter::make('role_slug')
                    ->label('Role Aplikasi')
                    ->options(fn (): array => self::allRoleOptions()),
                TernaryFilter::make('is_active')->label('Active'),
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
            'index' => Pages\ListUserAppAccesses::route('/'),
            'create' => Pages\CreateUserAppAccess::route('/create'),
            'edit' => Pages\EditUserAppAccess::route('/{record}/edit'),
        ];
    }

    public static function applicationOptions(): array
    {
        return CoreApplication::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'app_code')
            ->all();
    }

    public static function roleOptions(?string $appCode): array
    {
        return CoreApplicationRole::query()
            ->active()
            ->when($appCode, fn ($query) => $query->where('app_code', $appCode))
            ->orderBy('sort_order')
            ->orderBy('role_name')
            ->get()
            ->mapWithKeys(fn (CoreApplicationRole $role): array => [
                $role->role_slug => "{$role->role_name} ({$role->role_slug})",
            ])
            ->all();
    }

    public static function allRoleOptions(): array
    {
        return CoreApplicationRole::query()
            ->active()
            ->orderBy('app_code')
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (CoreApplicationRole $role): array => [
                $role->role_slug => "{$role->app_code}: {$role->role_name}",
            ])
            ->all();
    }
}
