# CORE-AUTO-USER-1 Master Profile Auto User Provisioning Report

Tanggal: 2026-06-01

## Scope

Membuat auto-provisioning user Core saat master data mahasiswa, dosen, atau tendik/staf/laboran dibuat tanpa `user_id`.

Tahap ini tidak menjalankan migration, tidak membuat SSO, tidak membuat token URL, tidak memberi app access otomatis, dan tidak memberi role admin otomatis.

## Aturan Provisioning

Username:

- Mahasiswa: NIM / `student_number`.
- Dosen: NIDN/NIP/`lecturer_number`.
- Tendik/staf/laboran: `employee_number`.

Password awal:

- Format: `NamaDepan + 4 karakter akhir identifier + !`.
- Contoh: `Andi nurjanah` + `221011402637` menjadi `Andi2637!`.
- Untuk nama panjang, hanya kata pertama yang dipakai.
- Password disimpan hashed.
- User baru selalu `must_change_password=true`.

Keamanan:

- Jika profile sudah punya `user_id`, tidak dibuat user baru.
- Jika ada satu user existing yang cocok username/email/identity, profile ditautkan.
- Jika ada lebih dari satu matching user, provisioning ditahan sebagai blocker.
- Jika identifier, nama, atau email kosong, provisioning dilewati.
- Role admin tidak dibuat otomatis.
- App access tidak dibuat otomatis.

## Implementasi

- Service: `app/Services/CoreProfileUserProvisioningService.php`.
- Observer: `app/Observers/CoreProfileUserObserver.php`.
- Registrasi observer: `app/Providers/AppServiceProvider.php`.
- Config: `config/core_identity.php`.
- Env example: `CORE_AUTO_CREATE_USER_FROM_PROFILE=true`.
- Backfill command: `php artisan core:provision-master-users`.

## Command Backfill

Default dry-run:

```bash
php artisan core:provision-master-users
```

Apply:

```bash
php artisan core:provision-master-users --apply
```

Apply spesifik:

```bash
php artisan core:provision-master-users --apply --only=students --identifier=221011402637
```

## Data Lokal Andi Nurjanah

Dry-run untuk NIM `221011402637` menghasilkan action `create`.

Apply spesifik sudah dijalankan:

```bash
php artisan core:provision-master-users --apply --only=students --identifier=221011402637
```

Hasil:

- Student profile ID: `4`.
- User ID: `20`.
- Username: `221011402637`.
- Password awal: `Andi2637!`.
- `must_change_password=true`.
- Password hash check lokal: OK.

## Tests

- `CoreProfileAutoUserProvisioningTest`: 7 passed / 31 assertions.
- Focused affected suite: 61 passed / 327 assertions.

## Status

Auto user provisioning siap dipakai sebagai baseline. User baru dari master profile bisa login ke Profile Portal memakai username identifier dan password awal sesuai policy, lalu wajib ganti password.
