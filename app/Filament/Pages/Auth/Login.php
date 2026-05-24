<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getTitle(): string | Htmlable
    {
        return 'Login Core Farmasi UBP';
    }

    public function getHeading(): string | Htmlable | null
    {
        return 'Core Farmasi UBP';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Pusat Identitas & Master Data Fakultas Farmasi';
    }
}
