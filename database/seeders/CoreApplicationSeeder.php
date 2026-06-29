<?php

namespace Database\Seeders;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use Illuminate\Database\Seeder;

class CoreApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $applications = [
            [
                'app_code' => 'core-farmasi',
                'name' => 'Core Farmasi',
                'description' => 'Pusat identity, master data, akses aplikasi, dan integrasi internal Farmasi UBP.',
                'is_public_visible' => false,
                'requires_login' => true,
                'is_sensitive' => true,
                'sort_order' => 10,
            ],
            [
                'app_code' => 'kp-farmasi',
                'name' => 'KP Farmasi',
                'description' => 'Aplikasi Kerja Praktik Farmasi.',
                'is_public_visible' => false,
                'requires_login' => true,
                'is_sensitive' => false,
                'sort_order' => 20,
            ],
            [
                'app_code' => 'safa-ubp',
                'name' => 'SAFA UBP',
                'description' => 'Aplikasi/admin SAFA UBP untuk konteks internal registry Core.',
                'is_public_visible' => false,
                'requires_login' => true,
                'is_sensitive' => false,
                'sort_order' => 30,
            ],
            [
                'app_code' => 'tu-farmasi',
                'name' => 'TU Farmasi',
                'description' => 'Aplikasi Tata Usaha Farmasi.',
                'is_public_visible' => false,
                'requires_login' => true,
                'is_sensitive' => false,
                'sort_order' => 40,
            ],
            [
                'app_code' => 'lab-farmasi',
                'name' => 'Lab Farmasi',
                'description' => 'Sistem operasional laboratorium Fakultas Farmasi UBP berbasis QR untuk absensi lab, logbook alat, stok bahan/reagen, SOP/SDS/K3, maintenance/kalibrasi, dashboard, dan laporan.',
                'base_url' => env('LAB_FARMASI_BASE_URL', 'http://127.0.0.1:8006'),
                'admin_url' => env('LAB_FARMASI_ADMIN_URL', 'http://127.0.0.1:8006/dashboard'),
                'icon' => 'flask-conical',
                'color' => '#0f6fb7',
                'is_public_visible' => false,
                'requires_login' => true,
                'is_sensitive' => false,
                'sort_order' => 50,
                'notes' => 'LAB-15 preparation. No SSO, no token URL, no auto-login, and no automatic user access grant.',
            ],
            [
                'app_code' => 'ta-farmasi',
                'name' => 'TA Farmasi UBP',
                'description' => 'Aplikasi pengelolaan Tugas Akhir Farmasi UBP untuk pendaftaran, pembimbing, bimbingan, seminar/sidang, revisi, finalisasi, evidence, dan pelaporan.',
                'base_url' => env('TA_FARMASI_BASE_URL', 'http://127.0.0.1:8007'),
                'admin_url' => env('TA_FARMASI_ADMIN_URL', 'http://127.0.0.1:8007/admin'),
                'icon' => 'academic-cap',
                'color' => '#2563eb',
                'is_public_visible' => false,
                'requires_login' => true,
                'is_sensitive' => false,
                'sort_order' => 60,
                'notes' => 'TA-26 registry readiness. No SSO, no token URL, no auto-login, no write-back, and no automatic mass user access grant.',
            ],
            [
                'app_code' => 'helpdesk-farmasi',
                'name' => 'Helpdesk Farmasi',
                'description' => 'Aplikasi helpdesk Farmasi untuk tiket, komentar, status, kategori, SLA, lampiran, dan operasional dukungan.',
                'base_url' => env('HELPDESK_FARMASI_BASE_URL', null),
                'admin_url' => env('HELPDESK_FARMASI_ADMIN_URL', null),
                'icon' => 'life-buoy',
                'color' => '#0284c7',
                'is_public_visible' => false,
                'requires_login' => true,
                'is_sensitive' => false,
                'sort_order' => 70,
                'notes' => 'Future Core consumer readiness. No SSO, no token URL, no auto-login, no write-back.',
            ],
        ];

        foreach ($applications as $applicationData) {
            CoreApplication::updateOrCreate(
                ['app_code' => $applicationData['app_code']],
                $applicationData + ['is_active' => true],
            );
        }

        $roles = [
            'core-farmasi' => [
                ['role_slug' => 'super-admin', 'role_name' => 'Super Admin'],
                ['role_slug' => 'admin-core', 'role_name' => 'Admin Core'],
            ],
            'kp-farmasi' => [
                ['role_slug' => 'mahasiswa', 'role_name' => 'Mahasiswa'],
                ['role_slug' => 'koordinator-kp', 'role_name' => 'Koordinator KP'],
                ['role_slug' => 'pembimbing-dalam', 'role_name' => 'Pembimbing Dalam'],
                ['role_slug' => 'pembimbing-lapangan', 'role_name' => 'Pembimbing Lapangan'],
                ['role_slug' => 'penguji', 'role_name' => 'Penguji'],
                ['role_slug' => 'penguji-luar', 'role_name' => 'Penguji Luar'],
                ['role_slug' => 'admin-kp', 'role_name' => 'Admin KP'],
            ],
            'tu-farmasi' => [
                ['role_slug' => 'admin-tu', 'role_name' => 'Admin TU'],
                ['role_slug' => 'staf-tu', 'role_name' => 'Staf TU'],
                ['role_slug' => 'tendik', 'role_name' => 'Tendik'],
                ['role_slug' => 'laboran', 'role_name' => 'Laboran'],
                ['role_slug' => 'dosen', 'role_name' => 'Dosen'],
                ['role_slug' => 'mahasiswa', 'role_name' => 'Mahasiswa'],
                ['role_slug' => 'validator', 'role_name' => 'Validator'],
                ['role_slug' => 'penandatangan', 'role_name' => 'Penandatangan'],
            ],
            'safa-ubp' => [
                ['role_slug' => 'admin-safa', 'role_name' => 'Admin SAFA'],
            ],
            'lab-farmasi' => [
                ['role_slug' => 'admin_lab', 'role_name' => 'Admin Lab', 'description' => 'Admin aplikasi Lab dengan akses operasional penuh.'],
                ['role_slug' => 'koordinator_lab', 'role_name' => 'Koordinator Lab', 'description' => 'Koordinasi operasional dan monitoring Lab.'],
                ['role_slug' => 'mahasiswa', 'role_name' => 'Mahasiswa', 'description' => 'Pengguna mahasiswa untuk aktivitas laboratorium.'],
                ['role_slug' => 'dosen', 'role_name' => 'Dosen', 'description' => 'Pengguna dosen untuk aktivitas laboratorium.'],
                ['role_slug' => 'laboran', 'role_name' => 'Laboran', 'description' => 'Operasional lab, stok, alat, K3, dan maintenance.'],
                ['role_slug' => 'kepala-lab', 'role_name' => 'Kepala Lab', 'description' => 'Pejabat/pengelola kepala lab dalam konteks aplikasi Lab.'],
                ['role_slug' => 'admin-lab', 'role_name' => 'Admin Lab', 'description' => 'Mengelola konfigurasi dan seluruh modul Lab.'],
                ['role_slug' => 'pengguna-lab', 'role_name' => 'Pengguna Lab', 'description' => 'Pengguna umum fasilitas dan layanan lab.'],
                ['role_slug' => 'peminjam-alat', 'role_name' => 'Peminjam Alat', 'description' => 'Mengajukan atau mengelola peminjaman alat lab.'],
                ['role_slug' => 'teknisi', 'role_name' => 'Teknisi', 'description' => 'Maintenance dan kalibrasi alat.'],
                ['role_slug' => 'viewer', 'role_name' => 'Viewer', 'description' => 'Akses baca dashboard dan laporan terbatas.'],
                ['role_slug' => 'lab-admin', 'role_name' => 'Admin Lab', 'description' => 'Mengelola konfigurasi dan seluruh modul Lab.'],
                ['role_slug' => 'lab-koordinator', 'role_name' => 'Koordinator Lab', 'description' => 'Monitoring dan koordinasi operasional lab.'],
                ['role_slug' => 'lab-kepala-lab', 'role_name' => 'Kepala Lab', 'description' => 'Monitoring dan persetujuan tingkat kepala lab.'],
                ['role_slug' => 'lab-laboran', 'role_name' => 'Laboran', 'description' => 'Operasional lab, stok, alat, K3, dan maintenance.'],
                ['role_slug' => 'lab-dosen', 'role_name' => 'Dosen', 'description' => 'Akses kegiatan dosen, praktikum, dan penelitian.'],
                ['role_slug' => 'lab-asisten', 'role_name' => 'Asisten Praktikum', 'description' => 'Akses bantuan praktikum dan operasional terbatas.'],
                ['role_slug' => 'lab-mahasiswa', 'role_name' => 'Mahasiswa', 'description' => 'Scan attendance, penggunaan alat sesuai izin, dan melihat riwayat sendiri.'],
                ['role_slug' => 'lab-teknisi', 'role_name' => 'Teknisi', 'description' => 'Maintenance dan kalibrasi alat.'],
                ['role_slug' => 'lab-viewer', 'role_name' => 'Viewer', 'description' => 'Akses baca dashboard dan laporan terbatas.'],
            ],
            'ta-farmasi' => [
                ['role_slug' => 'mahasiswa', 'role_name' => 'Mahasiswa', 'description' => 'Mahasiswa peserta tugas akhir.'],
                ['role_slug' => 'dosen', 'role_name' => 'Dosen', 'description' => 'Dosen yang terlibat dalam tugas akhir.'],
                ['role_slug' => 'dosen-pembimbing', 'role_name' => 'Dosen Pembimbing', 'description' => 'Pembimbing tugas akhir.'],
                ['role_slug' => 'pembimbing-luar', 'role_name' => 'Pembimbing Luar', 'description' => 'Pembimbing tugas akhir dari luar Fakultas Farmasi UBP.'],
                ['role_slug' => 'penguji', 'role_name' => 'Penguji', 'description' => 'Penguji tugas akhir.'],
                ['role_slug' => 'penguji-luar', 'role_name' => 'Penguji Luar', 'description' => 'Penguji tugas akhir dari luar Fakultas Farmasi UBP.'],
                ['role_slug' => 'koordinator-ta', 'role_name' => 'Koordinator TA', 'description' => 'Koordinator pelaksanaan tugas akhir.'],
                ['role_slug' => 'admin-ta', 'role_name' => 'Admin TA', 'description' => 'Admin aplikasi TA.'],
                ['role_slug' => 'kaprodi', 'role_name' => 'Kaprodi', 'description' => 'Peran aplikasi untuk kebutuhan koordinasi prodi; jabatan resmi tetap dari leadership Core.'],
                ['role_slug' => 'dekan', 'role_name' => 'Dekan', 'description' => 'Peran aplikasi untuk kebutuhan persetujuan; jabatan resmi tetap dari leadership Core.'],
                ['role_slug' => 'validator', 'role_name' => 'Validator', 'description' => 'Validator dokumen/proses tugas akhir.'],
            ],
            'helpdesk-farmasi' => [
                ['role_slug' => 'requester', 'role_name' => 'Requester', 'description' => 'Pengguna yang membuat atau memantau tiket helpdesk.'],
                ['role_slug' => 'agent', 'role_name' => 'Agent', 'description' => 'Petugas helpdesk yang menangani tiket.'],
                ['role_slug' => 'admin-helpdesk', 'role_name' => 'Admin Helpdesk', 'description' => 'Admin konfigurasi dan operasional Helpdesk Farmasi.'],
                ['role_slug' => 'teknisi', 'role_name' => 'Teknisi', 'description' => 'Teknisi yang menangani eskalasi teknis.'],
                ['role_slug' => 'supervisor', 'role_name' => 'Supervisor', 'description' => 'Supervisor monitoring SLA dan eskalasi tiket.'],
                ['role_slug' => 'viewer', 'role_name' => 'Viewer', 'description' => 'Akses baca terbatas untuk dashboard dan laporan helpdesk.'],
            ],
        ];

        foreach ($roles as $appCode => $appRoles) {
            $application = CoreApplication::query()->where('app_code', $appCode)->first();

            foreach ($appRoles as $index => $roleData) {
                CoreApplicationRole::updateOrCreate(
                    [
                        'app_code' => $appCode,
                        'role_slug' => $roleData['role_slug'],
                    ],
                    [
                        'core_application_id' => $application?->id,
                        'role_name' => $roleData['role_name'],
                        'description' => $roleData['description'] ?? null,
                        'is_active' => true,
                        'sort_order' => ($index + 1) * 10,
                    ],
                );
            }
        }
    }
}
