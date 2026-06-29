<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountRequestResource\Pages;
use App\Models\AccountRequest;
use App\Services\CoreAccountRequestService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use Throwable;
use UnitEnum;

class AccountRequestResource extends Resource
{
    protected static ?string $model = AccountRequest::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Access Control';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Permohonan Akun';

    protected static ?string $pluralModelLabel = 'Permohonan Akun';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Data Pemohon')
                    ->schema([
                        Forms\Components\Select::make('request_type')
                            ->label('Jenis Pemohon')
                            ->options(AccountRequest::typeOptions())
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Telepon')
                            ->maxLength(50),
                        Forms\Components\Textarea::make('address')
                            ->label('Alamat')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Tanggal Lahir'),
                        Forms\Components\TextInput::make('gender')
                            ->label('Jenis Kelamin')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('identity_number')
                            ->label('NIK / No. KTP')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('student_number')
                            ->label('NIM')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('lecturer_number')
                            ->label('Nomor Utama Dosen')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nip')
                            ->label('NIP')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nidn')
                            ->label('NIDN')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nidk')
                            ->label('NIDK')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nuptk')
                            ->label('NUPTK')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('employee_number')
                            ->label('Nomor Pegawai')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('staff_type')
                            ->label('Jenis Tendik/Staf')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('position_title')
                            ->label('Jabatan/Posisi')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('institution_name')
                            ->label('Instansi / Perusahaan')
                            ->maxLength(255),
                        Forms\Components\Select::make('institution_type')
                            ->label('Jenis Instansi')
                            ->options(AccountRequest::externalInstitutionTypeOptions())
                            ->searchable(),
                        Forms\Components\TextInput::make('profession')
                            ->label('Profesi / Keahlian')
                            ->maxLength(255),
                        Forms\Components\Select::make('study_program_id')
                            ->label('Program Studi')
                            ->relationship('studyProgram', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('department_id')
                            ->label('Departemen')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),
                Section::make('Permintaan Akses')
                    ->schema([
                        Forms\Components\TextInput::make('requested_app_code')
                            ->label('Requested App Code')
                            ->maxLength(255)
                            ->helperText('Informasi permintaan saja. App access tetap harus dibuat admin secara terpisah.'),
                        Forms\Components\TextInput::make('requested_role')
                            ->label('Requested Role')
                            ->maxLength(255)
                            ->helperText('Informasi permintaan saja. Role tidak diberikan otomatis.'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan Pemohon')
                            ->rows(4)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Review Admin')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(AccountRequest::statusOptions())
                            ->required()
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Status berubah melalui action Review, Approve & Buat Akun, atau Reject agar provisioning tetap aman.'),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Catatan Admin')
                            ->rows(4)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('reviewed_by')
                            ->label('Reviewed By')
                            ->relationship('reviewedBy', 'email')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\DateTimePicker::make('reviewed_at')
                            ->label('Reviewed At')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('approved_user_id')
                            ->label('Approved User')
                            ->relationship('approvedUser', 'email')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
                Section::make('Submission Metadata')
                    ->schema([
                        Forms\Components\TextInput::make('submitted_ip')
                            ->label('Submitted IP')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Textarea::make('submitted_user_agent')
                            ->label('Submitted User Agent')
                            ->rows(3)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('request_type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => AccountRequest::typeOptions()[$state] ?? $state)
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('identity_number')
                    ->label('Identitas')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('student_number')
                    ->label('NIM')
                    ->searchable(),
                TextColumn::make('lecturer_number')
                    ->label('No. Dosen')
                    ->searchable(),
                TextColumn::make('employee_number')
                    ->label('No. Pegawai')
                    ->searchable(),
                TextColumn::make('requested_app_code')
                    ->label('App')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                TextColumn::make('requested_role')
                    ->label('Role')
                    ->badge()
                    ->color('primary')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        AccountRequest::STATUS_APPROVED => 'success',
                        AccountRequest::STATUS_REJECTED, AccountRequest::STATUS_CANCELLED => 'danger',
                        AccountRequest::STATUS_IN_REVIEW => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => AccountRequest::statusOptions()[$state] ?? $state)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(AccountRequest::statusOptions()),
                SelectFilter::make('request_type')
                    ->label('Jenis Pemohon')
                    ->options(AccountRequest::typeOptions()),
                SelectFilter::make('requested_app_code')
                    ->label('Requested App')
                    ->options(fn (): array => AccountRequest::query()
                        ->whereNotNull('requested_app_code')
                        ->distinct()
                        ->orderBy('requested_app_code')
                        ->pluck('requested_app_code', 'requested_app_code')
                        ->all()),
            ])
            ->actions([
                Action::make('markInReview')
                    ->label('Review')
                    ->icon('heroicon-o-eye')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (AccountRequest $record): bool => in_array($record->status, [
                        AccountRequest::STATUS_PENDING,
                        AccountRequest::STATUS_CANCELLED,
                        AccountRequest::STATUS_REJECTED,
                    ], true))
                    ->action(fn (AccountRequest $record) => self::accountRequests()->markInReview($record, Filament::auth()->user())),
                Action::make('approveSkeleton')
                    ->label('Approve & Buat Akun')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Akun Core dan profil master akan dibuat atau ditautkan jika data lolos validasi. User App Access hanya dibuat jika aplikasi dan role permintaan tersedia dan opsi di bawah diaktifkan.')
                    ->visible(fn (AccountRequest $record): bool => blank($record->approved_user_id))
                    ->form([
                        Forms\Components\Toggle::make('create_requested_app_access')
                            ->label('Buat User App Access dari permintaan')
                            ->helperText('Aktifkan hanya jika kolom App dan Role sudah benar. Jika App kosong, akses aplikasi dibuat manual nanti.')
                            ->default(fn (AccountRequest $record): bool => filled($record->requested_app_code) && filled($record->requested_role))
                            ->disabled(fn (AccountRequest $record): bool => blank($record->requested_app_code) || blank($record->requested_role)),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Catatan Admin')
                            ->rows(3),
                    ])
                    ->action(function (AccountRequest $record, array $data): void {
                        try {
                            self::accountRequests()->approveAndProvision(
                                $record,
                                Filament::auth()->user(),
                                $data['admin_notes'] ?? null,
                                (bool) ($data['create_requested_app_access'] ?? false),
                            );

                            Notification::make()
                                ->success()
                                ->title('Permohonan disetujui.')
                                ->body('User dan profil master sudah dibuat/ditautkan. User App Access dibuat jika opsi dan data permintaan valid.')
                                ->send();
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Approval diblokir.')
                                ->body(collect($exception->errors())->flatten()->implode(' '))
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->danger()
                                ->title('Approval gagal diproses.')
                                ->body('Terjadi error teknis saat membuat/menautkan akun. Periksa data duplikat atau log aplikasi, lalu coba lagi.')
                                ->send();
                        }
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (AccountRequest $record): bool => ! $record->isRejected())
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Catatan Admin')
                            ->rows(3),
                    ])
                    ->action(function (AccountRequest $record, array $data): void {
                        self::accountRequests()->reject($record, Filament::auth()->user(), $data['admin_notes'] ?? null);

                        Notification::make()
                            ->success()
                            ->title('Permohonan ditolak.')
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountRequests::route('/'),
            'edit' => Pages\EditAccountRequest::route('/{record}/edit'),
        ];
    }

    private static function accountRequests(): CoreAccountRequestService
    {
        return app(CoreAccountRequestService::class);
    }
}
