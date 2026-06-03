<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\Role;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserAppAccess;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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

        $faculty = Faculty::updateOrCreate([
            'code' => 'FF',
        ], [
            'name' => 'Farmasi',
            'description' => 'Fakultas Farmasi',
            'active' => true,
        ]);

        $departments = collect([
            ['code' => 'FFK', 'name' => 'Farmakologi dan Farmasi Klinik'],
            ['code' => 'FK', 'name' => 'Farmakokimia'],
            ['code' => 'TSF', 'name' => 'Teknologi Sediaan Farmasi'],
            ['code' => 'BF', 'name' => 'Biologi Farmasi'],
        ])->map(fn (array $department): Department => Department::updateOrCreate([
            'code' => $department['code'],
        ], [
            'faculty_id' => $faculty->id,
            'name' => $department['name'],
            'description' => $department['name'],
            'active' => true,
        ]));

        $fallbackDepartmentId = DB::connection()->getDriverName() === 'mysql'
            ? null
            : $departments->first()?->id;

        collect([
            ['code' => 'S1-FARMASI', 'name' => 'Farmasi S1'],
            ['code' => 'PROFESI-APOTEKER', 'name' => 'Profesi Apoteker'],
        ])->each(fn (array $program) => StudyProgram::updateOrCreate([
            'code' => $program['code'],
        ], [
            'faculty_id' => $faculty->id,
            'name' => $program['name'],
            'department_id' => $fallbackDepartmentId,
            'description' => $program['name'],
            'active' => true,
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
