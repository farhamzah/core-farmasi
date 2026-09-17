<?php

namespace Database\Seeders;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KarirApplicationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $application = CoreApplication::query()->updateOrCreate(
                ['app_code' => 'karir-farmasi'],
                [
                    'name' => 'Alumni Farmasi',
                    'description' => 'Portal alumni, karier, CV, portofolio, lowongan, event, dan tracer Farmasi UBP.',
                    'base_url' => env('KARIR_FARMASI_BASE_URL', 'https://alumni.safaubp.com'),
                    'admin_url' => env('KARIR_FARMASI_ADMIN_URL', 'https://alumni.safaubp.com'),
                    'icon' => 'briefcase',
                    'color' => '#0f766e',
                    'is_public_visible' => false,
                    'requires_login' => true,
                    'is_sensitive' => false,
                    'is_active' => true,
                    'sort_order' => 71,
                    'notes' => 'Nama publik Alumni Farmasi. App code karir-farmasi dipertahankan sebagai identifier integrasi; data profesional tetap berada di aplikasi Alumni Farmasi.',
                ],
            );

            $roles = [
                ['role_slug' => 'kandidat-karir', 'role_name' => 'Kandidat Karir', 'description' => 'Alumni/kandidat yang telah disetujui untuk memakai Karir.'],
                ['role_slug' => 'admin-karir', 'role_name' => 'Admin Karir', 'description' => 'Admin operasional aplikasi Karir.'],
                ['role_slug' => 'petugas-karir', 'role_name' => 'Petugas Karir', 'description' => 'Petugas layanan karier dan alumni.'],
                ['role_slug' => 'viewer-karir', 'role_name' => 'Viewer Karir', 'description' => 'Akses baca terbatas aplikasi Karir.'],
            ];

            foreach ($roles as $index => $role) {
                CoreApplicationRole::query()->updateOrCreate(
                    [
                        'app_code' => 'karir-farmasi',
                        'role_slug' => $role['role_slug'],
                    ],
                    [
                        'core_application_id' => $application->id,
                        'role_name' => $role['role_name'],
                        'description' => $role['description'],
                        'is_active' => true,
                        'sort_order' => ($index + 1) * 10,
                    ],
                );
            }

            CoreApplicationRole::query()
                ->where('app_code', 'karir-farmasi')
                ->whereNotIn('role_slug', collect($roles)->pluck('role_slug')->all())
                ->update(['is_active' => false]);
        });
    }
}
