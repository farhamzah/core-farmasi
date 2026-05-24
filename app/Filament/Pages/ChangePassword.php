<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\UserActivityLog;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ChangePassword extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'Account';

    protected static ?string $navigationLabel = 'Ganti Password';

    protected static ?string $title = 'Ganti Password';

    protected static ?int $navigationSort = 90;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function getSlug(?\Filament\Panel $panel = null): string
    {
        return 'change-password';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Perbarui password akun Core Farmasi UBP Anda.';
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->model($this->getUser())
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('current_password')
                    ->label('Password Saat Ini')
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->autocomplete('current-password')
                    ->currentPassword(guard: Filament::getAuthGuard())
                    ->required(),
                Forms\Components\TextInput::make('new_password')
                    ->label('Password Baru')
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->autocomplete('new-password')
                    ->rule(Password::default())
                    ->minLength(8)
                    ->required()
                    ->confirmed(),
                Forms\Components\TextInput::make('new_password_confirmation')
                    ->label('Konfirmasi Password Baru')
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->autocomplete('new-password')
                    ->required()
                    ->dehydrated(false),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = $this->getUser();

        if (Hash::check($data['new_password'], $user->password)) {
            throw ValidationException::withMessages([
                'data.new_password' => 'Password baru tidak boleh sama dengan password saat ini.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($data['new_password']),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        if (request()->hasSession()) {
            request()->session()->put([
                'password_hash_' . Filament::getAuthGuard() => $user->password,
            ]);
        }

        UserActivityLog::create([
            'user_id' => $user->id,
            'action' => 'user.password_changed',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'meta' => [
                'target_user_id' => $user->id,
                'changed_by' => $user->id,
            ],
        ]);

        $this->data = [];
        $this->form->fill();

        Notification::make()
            ->success()
            ->title('Password berhasil diganti.')
            ->send();

        $this->redirect(Filament::getUrl());
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->alignment(Alignment::Start)
                            ->key('form-actions'),
                    ]),
            ]);
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Password')
                ->submit('save'),
        ];
    }

    public function getUser(): User & Model
    {
        /** @var User&Model $user */
        $user = Filament::auth()->user();

        return $user;
    }
}
