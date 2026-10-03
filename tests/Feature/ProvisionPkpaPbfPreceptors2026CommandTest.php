<?php

namespace Tests\Feature;

use App\Models\ExternalPerson;
use App\Models\User;
use App\Models\UserAppAccess;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProvisionPkpaPbfPreceptors2026CommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_previews_then_idempotently_creates_preceptors_without_resetting_existing_passwords(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->artisan('core:provision-pkpa-pbf-preceptors-2026')->assertSuccessful();
        $this->assertSame(0, User::query()->where('email', 'chindy@preseptor.safaubp.com')->count());

        $this->artisan('core:provision-pkpa-pbf-preceptors-2026', ['--apply' => true])->assertSuccessful();

        $this->assertSame(10, ExternalPerson::query()->where('external_number', 'like', 'PKPA-PBF-2026-%')->count());
        $this->assertSame(10, UserAppAccess::query()
            ->where('app_code', 'kppspa-farmasi')
            ->where('role_slug', 'pembimbing-lapangan')
            ->where('is_active', true)
            ->count());

        $chindy = User::query()->where('email', 'chindy@preseptor.safaubp.com')->firstOrFail();
        $this->assertTrue($chindy->must_change_password);
        $this->assertTrue(Hash::check('password1234', $chindy->password));
        $this->assertSame('PT. Bina San Prima Bekasi', $chindy->externalPerson?->institution_name);
        $passwordHash = $chindy->password;

        $this->artisan('core:provision-pkpa-pbf-preceptors-2026', ['--apply' => true])->assertSuccessful();

        $this->assertSame(10, ExternalPerson::query()->where('external_number', 'like', 'PKPA-PBF-2026-%')->count());
        $this->assertSame($passwordHash, $chindy->fresh()->password);
    }
}
