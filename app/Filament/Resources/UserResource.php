<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Services\CoreInitialPasswordService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
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

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Users';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Akun')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nama tampil resmi untuk admin dan integrasi internal.'),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (?User $record) => ! $record)
                            ->helperText('Password awal bersifat sementara. Kosongkan saat edit jika tidak ingin mengganti password. Password tidak pernah ditampilkan di tabel.'),
                        Forms\Components\Toggle::make('active')
                            ->label('Active')
                            ->default(true),
                        Forms\Components\MultiSelect::make('roles')
                            ->label('Role Global')
                            ->relationship('roles', 'label')
                            ->searchable()
                            ->preload()
                            ->helperText('Role global Core. Role aplikasi dikelola melalui User App Access.'),
                    ])
                    ->columns(2),
                Section::make('Identitas Login')
                    ->schema([
                        Forms\Components\TextInput::make('username')
                            ->label('Username')
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Mahasiswa memakai NIM. Dosen memakai NIDN/NIP/nomor dosen. Tendik/staf/laboran memakai nomor kepegawaian.'),
                        Forms\Components\Select::make('identity_type')
                            ->label('Identity Type')
                            ->options(self::identityTypeOptions())
                            ->in(array_keys(self::identityTypeOptions())),
                        Forms\Components\TextInput::make('identity_number')
                            ->maxLength(255),
                        Forms\Components\Toggle::make('must_change_password')
                            ->label('Must Change Password')
                            ->default(true)
                            ->helperText('User baru wajib mengganti password sementara saat login pertama.'),
                        Forms\Components\DateTimePicker::make('password_changed_at')
                            ->label('Password Changed At')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\DateTimePicker::make('last_password_reset_at')
                            ->label('Last Password Reset At')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('password_reset_by')
                            ->label('Password Reset By')
                            ->relationship('passwordResetBy', 'email')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('username')->label('Username')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable()->sortable(),
                TextColumn::make('identity_type')
                    ->label('Identity Type')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (?string $state): string => self::identityTypeOptions()[$state] ?? (string) $state),
                TextColumn::make('identity_number')
                    ->label('Identity Number')
                    ->searchable(),
                BooleanColumn::make('must_change_password')
                    ->label('Must Change'),
                BooleanColumn::make('active')->label('Active'),
                TextColumn::make('roles.label')->label('Roles')->badge()->wrap(),
                TextColumn::make('app_accesses_count')
                    ->label('App Access')
                    ->counts('appAccesses')
                    ->badge()
                    ->color('info'),
                TextColumn::make('password_changed_at')
                    ->label('Password Changed')
                    ->dateTime('d MMM Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime('d MMM Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('identity_type')
                    ->label('Identity Type')
                    ->options(self::identityTypeOptions()),
                SelectFilter::make('roles')
                    ->label('Role Global')
                    ->relationship('roles', 'label')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('must_change_password')
                    ->label('Must Change Password'),
                TernaryFilter::make('active')
                    ->label('Active'),
            ])
            ->actions([
                Action::make('setInitialPassword')
                    ->label('Reset Password Awal')
                    ->icon('heroicon-o-key')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Password Awal')
                    ->modalDescription('Password awal akan diset sesuai strategi kebijakan Core saat ini. User wajib mengganti password saat login berikutnya. Password tidak akan ditampilkan.')
                    ->modalSubmitActionLabel('Reset Password Awal')
                    ->action(function (User $record): void {
                        $initialPasswordService = app(CoreInitialPasswordService::class);
                        $birthDate = $initialPasswordService->resolveBirthDateForUser($record);

                        if (! $initialPasswordService->usesNameStrategy() && blank($birthDate)) {
                            Notification::make()
                                ->danger()
                                ->title('Tanggal lahir belum tersedia.')
                                ->body('Password awal tidak dapat diset otomatis.')
                                ->send();

                            return;
                        }

                        /** @var User|null $operator */
                        $operator = Filament::auth()->user();

                        $initialPasswordService->setInitialPassword($record, $birthDate, $operator);

                        if ($operator) {
                            UserActivityLog::create([
                                'user_id' => $operator->id,
                                'action' => 'user.initial_password_reset',
                                'ip_address' => request()->ip(),
                                'user_agent' => request()->userAgent(),
                                'meta' => [
                                    'target_user_id' => $record->id,
                                    'reset_by' => $operator->id,
                                    'method' => $initialPasswordService->strategy().'_based',
                                ],
                            ]);
                        }

                        Notification::make()
                            ->success()
                            ->title('Password awal berhasil diset.')
                            ->body('User wajib mengganti password saat login berikutnya.')
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function identityTypeOptions(): array
    {
        return config('core_identity.identity_types', [
            'student' => 'Student',
            'lecturer' => 'Lecturer',
            'employee' => 'Employee',
            'admin' => 'Admin',
            'external' => 'External',
            'system' => 'System',
        ]);
    }
}
