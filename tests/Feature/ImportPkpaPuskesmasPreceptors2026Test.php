<?php

namespace Tests\Feature;

use App\Models\ExternalPerson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportPkpaPuskesmasPreceptors2026Test extends TestCase
{
    use RefreshDatabase;

    public function test_preview_apply_and_repeat_do_not_create_accounts(): void
    {
        $this->artisan('core:import-pkpa-puskesmas-preceptors-2026')->assertSuccessful();
        $this->assertSame(0, ExternalPerson::count());
        $this->artisan('core:import-pkpa-puskesmas-preceptors-2026', ['--apply' => true])->assertSuccessful();
        $this->assertSame(29, ExternalPerson::count());
        $this->assertSame(0, ExternalPerson::whereNotNull('user_id')->count());
        $this->assertSame(0, User::count());
        $this->artisan('core:import-pkpa-puskesmas-preceptors-2026', ['--apply' => true])->assertSuccessful();
        $this->assertSame(29, ExternalPerson::count());
    }

    public function test_existing_linked_profile_is_reused_without_changing_account(): void
    {
        $site = json_decode(file_get_contents(database_path('data/pkpa_puskesmas_preceptors_2026.json')), true)['sites'][0];
        $user = User::factory()->create();
        $before = $user->fresh()->getRawOriginal();
        $profile = ExternalPerson::create(['user_id' => $user->id, 'name' => $site['preceptor'], 'phone' => $site['phone'], 'institution_name' => $site['name'], 'status' => 'active']);
        $this->artisan('core:import-pkpa-puskesmas-preceptors-2026', ['--apply' => true])->assertSuccessful();
        $this->assertSame(29, ExternalPerson::count());
        $this->assertSame($user->id, $profile->fresh()->user_id);
        $this->assertSame($before, $user->fresh()->getRawOriginal());
    }

    public function test_phone_conflict_blocks_entire_import(): void
    {
        $site = json_decode(file_get_contents(database_path('data/pkpa_puskesmas_preceptors_2026.json')), true)['sites'][0];
        ExternalPerson::create(['name' => 'Orang Lain', 'phone' => $site['phone'], 'status' => 'active']);
        $this->artisan('core:import-pkpa-puskesmas-preceptors-2026', ['--apply' => true])->assertFailed();
        $this->assertSame(1, ExternalPerson::count());
        $this->assertSame(0, User::count());
    }
}
