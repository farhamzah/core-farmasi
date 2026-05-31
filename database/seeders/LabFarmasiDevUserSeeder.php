<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserAppAccess;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LabFarmasiDevUserSeeder extends Seeder
{
    /**
     * Seed local/dev Lab Farmasi demo users for manual role testing.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Lab Farmasi demo users skipped outside local/testing environment.');

            return;
        }

        $this->call(CoreApplicationSeeder::class);

        foreach ($this->demoUsers() as $demoUser) {
            $user = User::withTrashed()->firstOrNew([
                'email' => $demoUser['email'],
            ]);

            $user->fill([
                'name' => $demoUser['name'],
                'username' => $demoUser['username'],
                'identity_type' => 'dev_lab_demo',
                'identity_number' => $demoUser['identity_number'],
                'active' => true,
                'must_change_password' => true,
            ]);

            if (! $user->exists) {
                $user->password = Str::random(48);
            }

            if (method_exists($user, 'restore') && $user->trashed()) {
                $user->restore();
            }

            $user->save();

            UserAppAccess::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'app_code' => 'lab-farmasi',
                    'role_slug' => $demoUser['role_slug'],
                ],
                [
                    'permissions' => null,
                    'is_active' => true,
                    'activated_at' => now(),
                    'deactivated_at' => null,
                ],
            );
        }
    }

    /**
     * @return array<int, array{name: string, email: string, username: string, identity_number: string, role_slug: string}>
     */
    private function demoUsers(): array
    {
        return [
            [
                'name' => 'Mahasiswa Lab Demo',
                'email' => 'lab.demo.mahasiswa@example.test',
                'username' => 'lab-demo-mahasiswa',
                'identity_number' => 'LAB-DEMO-MHS-001',
                'role_slug' => 'mahasiswa',
            ],
            [
                'name' => 'Dosen Lab Demo',
                'email' => 'lab.demo.dosen@example.test',
                'username' => 'lab-demo-dosen',
                'identity_number' => 'LAB-DEMO-DSN-001',
                'role_slug' => 'dosen',
            ],
            [
                'name' => 'Laboran Lab Demo',
                'email' => 'lab.demo.laboran@example.test',
                'username' => 'lab-demo-laboran',
                'identity_number' => 'LAB-DEMO-LBR-001',
                'role_slug' => 'laboran',
            ],
            [
                'name' => 'Teknisi Lab Demo',
                'email' => 'lab.demo.teknisi@example.test',
                'username' => 'lab-demo-teknisi',
                'identity_number' => 'LAB-DEMO-TKN-001',
                'role_slug' => 'teknisi',
            ],
            [
                'name' => 'Koordinator Lab Demo',
                'email' => 'lab.demo.koordinator@example.test',
                'username' => 'lab-demo-koordinator',
                'identity_number' => 'LAB-DEMO-KOR-001',
                'role_slug' => 'koordinator_lab',
            ],
            [
                'name' => 'Admin Lab Demo',
                'email' => 'lab.demo.admin@example.test',
                'username' => 'lab-demo-admin',
                'identity_number' => 'LAB-DEMO-ADM-001',
                'role_slug' => 'admin_lab',
            ],
            [
                'name' => 'Viewer Lab Demo',
                'email' => 'lab.demo.viewer@example.test',
                'username' => 'lab-demo-viewer',
                'identity_number' => 'LAB-DEMO-VWR-001',
                'role_slug' => 'viewer',
            ],
        ];
    }
}
