<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserAppAccessResource\Pages;
use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\User;
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
                Section::make('Panduan Singkat')
                    ->description('Berikan akses aplikasi per user. Pilih user, aplikasi, lalu role aplikasi yang sesuai. Role di sini hanya berlaku untuk aplikasi yang dipilih, bukan role global Core.')
                    ->schema([])
                    ->columnSpanFull(),
                Section::make('1. User & Aplikasi')
                    ->description('Pastikan user sudah aktif dan aplikasi sudah terdaftar aktif di Core.')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->options(fn (): array => self::defaultUserOptions())
                            ->getSearchResultsUsing(fn (string $search): array => self::userSearchResults($search))
                            ->getOptionLabelUsing(fn ($value): ?string => self::userOptionLabel($value))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Cari dengan nama, email, username, NIM, NIDN/NIP, atau nomor pegawai.')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('app_code')
                            ->label('Aplikasi')
                            ->options(fn (): array => self::applicationOptions())
                            ->live()
                            ->afterStateUpdated(fn ($set): mixed => $set('role_slug', null))
                            ->searchable()
                            ->required()
                            ->helperText('Role aplikasi akan difilter sesuai aplikasi ini.'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Akses aktif')
                            ->default(true)
                            ->helperText('Matikan jika akses hanya disiapkan tetapi belum boleh dipakai.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('2. Role Aplikasi')
                    ->description('Pilih satu role aplikasi. Untuk mahasiswa KP pilih Mahasiswa, untuk admin TA pilih Admin TA, dan seterusnya.')
                    ->schema([
                        Forms\Components\Select::make('role_slug')
                            ->label('Role Aplikasi')
                            ->options(fn ($get): array => self::roleOptions($get('app_code')))
                            ->searchable()
                            ->required()
                            ->helperText('Jika belum ada pilihan, pilih aplikasi dulu atau cek catalog Role Aplikasi.'),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
                Section::make('3. Periode Akses')
                    ->description('Umumnya cukup biarkan tanggal aktif terisi otomatis. Tanggal nonaktif hanya diisi jika akses punya batas waktu.')
                    ->schema([
                        Forms\Components\DateTimePicker::make('activated_at')
                            ->label('Mulai Aktif')
                            ->default(now())
                            ->helperText('Jika kosong, akses tetap aktif selama toggle Akses aktif menyala.'),
                        Forms\Components\DateTimePicker::make('deactivated_at')
                            ->label('Nonaktif Pada')
                            ->helperText('Opsional. Isi hanya untuk akses sementara.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Advanced: Permission Tambahan')
                    ->description('Biarkan kosong untuk akses normal. Dipakai hanya jika aplikasi membutuhkan permission khusus di luar role.')
                    ->schema([
                        Forms\Components\KeyValue::make('permissions')
                            ->label('Permission Tambahan')
                            ->keyLabel('Permission')
                            ->valueLabel('Value')
                            ->addActionLabel('Tambah permission'),
                    ])
                    ->collapsed()
                    ->columnSpanFull(),
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
                TextColumn::make('activated_at')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('deactivated_at')->dateTime('d M Y H:i')->sortable(),
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

    public static function defaultUserOptions(): array
    {
        return User::query()
            ->where('active', true)
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (User $user): array => [$user->id => self::formatUserOption($user)])
            ->all();
    }

    public static function userSearchResults(string $search): array
    {
        $term = '%'.strtolower(trim($search)).'%';

        return User::query()
            ->where('active', true)
            ->where(function ($query) use ($term): void {
                $query
                    ->whereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(username) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(identity_number) LIKE ?', [$term]);
            })
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (User $user): array => [$user->id => self::formatUserOption($user)])
            ->all();
    }

    public static function userOptionLabel(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $user = User::query()->find($value);

        return $user ? self::formatUserOption($user) : null;
    }

    protected static function formatUserOption(User $user): string
    {
        $identifier = collect([$user->username, $user->identity_number])
            ->filter()
            ->unique()
            ->implode(' / ');

        $label = "{$user->name} - {$user->email}";

        return $identifier ? "{$label} ({$identifier})" : $label;
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
                $role->role_slug => "{$role->role_name} - {$role->role_slug}",
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
