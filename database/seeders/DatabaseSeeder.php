<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserAppAccess;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CoreApplicationSeeder::class);

        $superAdmin = User::firstOrCreate([
            'email' => 'admin@core-farmasi.local',
        ], [
            'name' => 'Core Farmasi Admin',
            'password' => 'password',
            'active' => true,
        ]);

        $roles = collect([
            ['name' => 'super-admin', 'label' => 'Super Admin'],
            ['name' => 'admin-core', 'label' => 'Admin Core'],
            ['name' => 'admin-safa', 'label' => 'Admin SAFA'],
            ['name' => 'admin-kp', 'label' => 'Admin KP'],
            ['name' => 'koordinator-kp', 'label' => 'Koordinator KP'],
            ['name' => 'mahasiswa', 'label' => 'Mahasiswa'],
            ['name' => 'dosen', 'label' => 'Dosen'],
            ['name' => 'pembimbing-dalam', 'label' => 'Pembimbing Dalam'],
            ['name' => 'pembimbing-lapangan', 'label' => 'Pembimbing Lapangan'],
            ['name' => 'penguji', 'label' => 'Penguji'],
            ['name' => 'tata-usaha', 'label' => 'Tata Usaha'],
            ['name' => 'kaprodi', 'label' => 'Kaprodi'],
            ['name' => 'dekan', 'label' => 'Dekan'],
        ])->map(fn (array $role) => Role::firstOrCreate(['name' => $role['name']], ['label' => $role['label']]));

        $superAdmin->roles()->sync([$roles->firstWhere('name', 'super-admin')->id]);

        $department = Department::firstOrCreate([
            'code' => 'FF',
        ], [
            'name' => 'Fakultas Farmasi',
            'description' => 'Fakultas Farmasi',
        ]);

        Department::firstOrCreate([
            'code' => 'FARKLIN',
        ], [
            'name' => 'Farmasi Klinis',
            'description' => 'Farmasi Klinis',
        ]);

        collect([
            ['code' => 'S1-FARMASI', 'name' => 'S1 Farmasi'],
            ['code' => 'PROFESI-APOTEKER', 'name' => 'Profesi Apoteker'],
        ])->each(fn (array $program) => StudyProgram::firstOrCreate([
            'code' => $program['code'],
        ], [
            'name' => $program['name'],
            'department_id' => $department->id,
            'description' => $program['name'],
        ]));

        collect(['core-farmasi', 'safa-ubp', 'kp-farmasi', 'ta-farmasi'])
            ->each(fn (string $appCode) => UserAppAccess::firstOrCreate([
                'user_id' => $superAdmin->id,
                'app_code' => $appCode,
                'role_slug' => 'super-admin',
            ], [
                'permissions' => ['*'],
                'is_active' => true,
                'activated_at' => now(),
            ]));

        if (app()->environment(['local', 'testing'])) {
            $this->call(LabFarmasiDevUserSeeder::class);
        }
    }
}
