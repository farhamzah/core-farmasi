<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Actions\Action;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getTitle(): string | Htmlable
    {
        return 'Masuk Admin Core Farmasi UBP';
    }

    public function getHeading(): string | Htmlable | null
    {
        return 'Masuk Admin Core';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Pusat identitas, akun, dan master data Fakultas Farmasi UBP.';
    }

    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->label('Email Admin Core')
            ->placeholder('admin@core-farmasi.local');
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->label('Password')
            ->placeholder('Masukkan password');
    }

    protected function getRememberFormComponent(): Component
    {
        return parent::getRememberFormComponent()
            ->label('Ingat perangkat ini');
    }

    protected function getAuthenticateFormAction(): Action
    {
        return parent::getAuthenticateFormAction()
            ->label('Masuk ke Admin Core');
    }
}
