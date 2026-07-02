<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CoreNormalizePersonNamesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_reports_changes_without_writing(): void
    {
        $this->insertLegacyUppercaseUser();

        $this->artisan('core:normalize-person-names', ['--show-samples' => true])
            ->expectsOutputToContain('Mode dry-run')
            ->expectsOutputToContain('Total akan diubah: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'nia@example.test',
            'name' => 'NIA Yuniarti',
        ]);
    }

    public function test_execute_normalizes_existing_names(): void
    {
        $this->insertLegacyUppercaseUser();

        $this->artisan('core:normalize-person-names', ['--execute' => true])
            ->expectsOutputToContain('Mode execute')
            ->expectsOutputToContain('Total diubah: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'nia@example.test',
            'name' => 'Nia Yuniarti',
        ]);
    }

    public function test_only_option_limits_targets(): void
    {
        $this->insertLegacyUppercaseUser();
        DB::table('departments')->insert([
            'id' => 1,
            'code' => 'FF',
            'name' => 'Fakultas Farmasi',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('study_programs')->insert([
            'id' => 1,
            'department_id' => 1,
            'code' => 'S1F',
            'name' => 'Farmasi S1',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('students')->insert([
            'student_number' => '240001',
            'name' => 'MUHAMMAD AZRIEL AULIA',
            'email' => 'azriel@example.test',
            'study_program_id' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('core:normalize-person-names', [
            '--execute' => true,
            '--only' => 'students',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'nia@example.test',
            'name' => 'NIA Yuniarti',
        ]);
        $this->assertDatabaseHas('students', [
            'student_number' => '240001',
            'name' => 'Muhammad Azriel Aulia',
        ]);
    }

    private function insertLegacyUppercaseUser(): void
    {
        DB::table('users')->insert([
            'name' => 'NIA Yuniarti',
            'email' => 'nia@example.test',
            'password' => Hash::make('password'),
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame('NIA Yuniarti', User::query()->where('email', 'nia@example.test')->value('name'));
    }
}
