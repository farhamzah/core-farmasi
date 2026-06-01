# CORE-ACCOUNT-2B Disable Public Registration & Admin-Only Provisioning Report

Tanggal: 2026-06-01

## Scope

Perubahan hanya dilakukan di `apps/core-farmasi`.

CORE-ACCOUNT-2B menutup registrasi publik secara default dan menetapkan provisioning akun aktif melalui Admin Core, baik lewat CRUD user maupun Import Center.

## Decision

- Public account request disabled by default melalui `CORE_PUBLIC_ACCOUNT_REQUEST_ENABLED=false`.
- `/register` tetap diarahkan ke `/account-request`, tetapi tidak membuka form registrasi aktif saat fitur publik disabled.
- `POST /account-request` ditolak `403` saat fitur publik disabled.
- Admin resource account request tetap dipertahankan sebagai skeleton/histori dan dapat diaktifkan kembali eksplisit via konfigurasi.
- Akun aktif dibuat oleh Admin Core melalui `UserResource` atau Import Center.
- App access tidak dibuat otomatis dari registrasi/account request.

## Files Changed

- `app/Http/Controllers/AccountRequestController.php`
- `app/Services/CoreInitialPasswordService.php`
- `config/core_import.php`
- `app/Services/CoreImportExecutionService.php`
- `app/Filament/Resources/UserResource.php`
- `app/Filament/Resources/UserResource/Pages/CreateUser.php`
- `app/Filament/Resources/UserResource/Pages/EditUser.php`
- `config/core_account.php`
- `config/core_identity.php`
- `resources/views/account-request/disabled.blade.php`
- `tests/Feature/CoreAccountRequestTest.php`
- `tests/Feature/CoreAuthIdentityTest.php`
- `tests/Feature/CoreChangePasswordFlowTest.php`
- `tests/Feature/CoreImportCenterTest.php`
- `docs/CORE-ACCOUNT-LIFECYCLE-PLAN.md`
- `docs/CORE-CENTRALIZED-PROFILE-PORTAL-PLAN.md`
- `docs/CORE-CENTRAL-DATA-CONTRACT.md`
- `docs/reports/CORE-ACCOUNT-2B-DISABLE-PUBLIC-REGISTRATION-ADMIN-PROVISIONING-REPORT.md`

## Registration Route Behavior

- `GET /account-request`: menampilkan halaman informasi bahwa registrasi mandiri tidak dibuka saat public account request disabled.
- `POST /account-request`: forbidden saat public account request disabled.
- `GET /register`: redirect ke `/account-request`; hasil akhir tetap halaman disabled, bukan form aktif.
- Form account request lama tetap bisa diuji saat `core_account.public_account_request_enabled=true`.

## Username Policy

- Mahasiswa memakai NIM.
- Dosen memakai NIDN/NIP/nomor dosen.
- Tendik, staf, dan laboran memakai nomor kepegawaian.
- Helper text admin user diperjelas agar operator tidak memakai username acak.

## Initial Password Policy

- Password awal admin/import adalah password sementara.
- Strategi default: `name`.
- Strategi lama `birth_date` tetap tersedia melalui `CORE_INITIAL_PASSWORD_STRATEGY=birth_date`.
- Password selalu disimpan hashed.
- User baru atau user yang password-nya diubah admin dipaksa `must_change_password=true`.
- Password plaintext tidak ditulis ke report, log, template, atau response.

## Admin Provisioning

- Admin manual CRUD user tetap tersedia.
- Import Center tetap tersedia untuk user, student, lecturer, employee, role, dan app access.
- User baru dari import dibuat dengan password awal sementara yang hashed dan `must_change_password=true`.
- Import profile tidak otomatis membuat app access.
- Account request approval skeleton tidak membuat user/app access otomatis.

## Profile Portal

- Profile Portal tetap self-only untuk authenticated user.
- Non-admin tetap tidak mendapat akses `/admin`.
- Self-service change password non-admin dicatat sebagai next step `CORE-PROFILE-4`.

## Security Confirmation

- Tidak ada automatic user activation dari public registration.
- Tidak ada automatic app access.
- Tidak ada password plaintext yang ditampilkan atau disimpan.
- Tidak ada SSO/token URL/cross-app session.
- `/admin` tetap admin-only melalui policy panel existing.
- Tidak ada perubahan `.env`, secret, key, atau token.

## Commands Run

- `php artisan optimize:clear`: OK
- `npm.cmd run build`: OK
- `php artisan route:list`: OK, 95 routes listed
- `php artisan test --filter=CoreImportCenterTest`: OK, 48 passed, 265 assertions
- `php artisan test`: OK, 240 passed, 1345 assertions

## Test Result

All tests passed.

## Guardrails Confirmation

- Tidak mengubah aplikasi selain `apps/core-farmasi`.
- Tidak commit/push.
- Tidak membuat migration destruktif.
- Tidak memasukkan secret/key/token ke repo.
- Tidak mengekspos password plaintext.

## Recommended Next Step

CORE-PROFILE-4 Self-Service Change Password untuk user authenticated non-admin di Profile Portal.
