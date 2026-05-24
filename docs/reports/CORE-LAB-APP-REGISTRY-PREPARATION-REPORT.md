# Core - Lab App Registry Preparation Report

## Ringkasan

Core Farmasi menyiapkan entry aplikasi `lab-farmasi` dan role catalog Lab secara idempotent. Persiapan ini membuat Lab Farmasi UBP siap dikenali sebagai aplikasi workspace tanpa memberi akses user otomatis dan tanpa membuat auth bridge.

## App Registry Entry

- App code: `lab-farmasi`
- App name: `Lab Farmasi UBP`
- Description: sistem operasional laboratorium berbasis QR untuk absensi lab, logbook alat, stok bahan/reagen, SOP/SDS/K3, maintenance/kalibrasi, dashboard, dan laporan.
- Local base URL: `http://127.0.0.1:8006`
- Local dashboard URL: `http://127.0.0.1:8006/dashboard`
- Icon: `flask-conical`
- Visibility: not public visible
- Requires login: yes
- Status: active/development sesuai pola Core

Seeder:

- `database/seeders/CoreApplicationSeeder.php`

Seeder menggunakan pola idempotent dan tidak menghapus atau mengubah aplikasi lain.

## Role Catalog Lab

Role yang disiapkan:

- `lab-admin`: mengelola konfigurasi dan seluruh modul Lab.
- `lab-koordinator`: monitoring dan koordinasi operasional lab.
- `lab-kepala-lab`: monitoring dan persetujuan tingkat kepala lab.
- `lab-laboran`: operasional lab, stok, alat, K3, maintenance.
- `lab-dosen`: akses kegiatan dosen/praktikum/penelitian.
- `lab-asisten`: akses bantuan praktikum dan operasional terbatas.
- `lab-mahasiswa`: scan attendance, penggunaan alat sesuai izin, melihat riwayat sendiri.
- `lab-teknisi`: maintenance/kalibrasi alat.
- `lab-viewer`: akses baca dashboard/laporan terbatas.

Tidak ada role aplikasi lain yang dihapus.

## Command

Command readiness:

```bash
php artisan core:lab-app-readiness
```

Command mengecek:

- app `lab-farmasi` ada/tidak;
- duplicate app code;
- status active;
- public visibility;
- requires login;
- role Lab lengkap/tidak;
- jumlah active user app access.

Command tidak membuat user, tidak memberi akses otomatis, tidak menampilkan secret, dan tidak menulis ke aplikasi Lab.

## Hasil Validasi

- `php artisan db:seed --class=CoreApplicationSeeder`: OK.
- `php artisan core:lab-app-readiness`: READY.
- Active user app access count: 0.
- `php artisan test`: 208 passed / 1017 assertions.
- PHP lint file penting: OK.

## Guardrails

- Tidak ada user access otomatis.
- Tidak ada SSO.
- Tidak ada auto-login.
- Tidak ada token URL.
- Tidak ada secret output.
- Tidak ada write ke Lab.
- Core tetap source of truth untuk app registry, app access, roles, dan identity.
