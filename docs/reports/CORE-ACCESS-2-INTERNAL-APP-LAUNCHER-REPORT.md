# CORE-ACCESS-2 Internal App Launcher Report

## Scope
Tahap ini membuat Internal App Launcher di Core admin. Launcher hanya menyediakan shortcut navigasi internal untuk aplikasi yang sudah diberikan akses aktif kepada user.

Tahap ini bukan SSO, bukan auto-login, tidak membuat cross-app token, tidak membuat API baru, dan tidak mengubah aplikasi lain.

## Previous Reports Reviewed
- CORE-ACCESS-1 Dynamic App Registry & App Role Catalog Report
- CORE-DQ-1 Data Quality Dashboard Report

## Files Changed
- `app/Services/CoreAppLauncherService.php`
- `app/Filament/Pages/CoreAppLauncher.php`
- `resources/views/filament/pages/core-app-launcher.blade.php`
- `tests/Feature/CoreAppLauncherTest.php`
- `tests/Feature/CoreAccessAppRegistryTest.php`
- `docs/reports/CORE-ACCESS-2-INTERNAL-APP-LAUNCHER-REPORT.md`
- `README.md`

## App Launcher Service
`CoreAppLauncherService` dibuat untuk menerima `User` dan mengembalikan daftar app cards.

Resolusi app:
- Mengambil `UserAppAccess` aktif milik user.
- Hanya mengambil `CoreApplication` yang aktif.
- Menyembunyikan `core-farmasi` karena user sedang berada di Core.
- Menampilkan role dari `CoreApplicationRole` aktif jika tersedia.
- Aplikasi tanpa URL tetap dikembalikan sebagai disabled dengan alasan aman.

URL selection:
- `admin_url` dipakai lebih dulu.
- Fallback ke `base_url`.
- Jika keduanya kosong, card disabled.

Security behavior:
- Tidak membuat token.
- Tidak membuat signed login URL.
- Tidak auto-login.
- Tidak bypass guard aplikasi tujuan.

## Filament Page
`CoreAppLauncher` dibuat sebagai Filament page protected di `/admin/app-launcher`.

Navigation:
- Group: Access Control
- Label: Aplikasi Saya

UI:
- Card grid aplikasi.
- Nama, kode aplikasi, deskripsi.
- Role/akses user pada aplikasi.
- Badge wajib login dan internal/sensitive.
- Tombol `Buka Aplikasi` untuk app yang punya URL.
- Disabled state untuk app tanpa URL.
- Empty state: `Belum ada akses aplikasi aktif untuk akun ini.`

Current Core handling:
- `core-farmasi` disembunyikan dari launcher karena user sudah berada di Core.

## Security Confirmation
- Page protected di admin panel.
- Guest diarahkan ke `/admin/login`.
- User non Core admin ditolak.
- Hanya aplikasi dengan `UserAppAccess.is_active = true` yang tampil.
- Inactive app tidak tampil.
- Inactive access tidak tampil.
- Tidak ada public route launcher.
- Tidak ada SSO.
- Tidak ada auto-login.
- Tidak ada cross-app token.
- Tidak ada API baru.
- Core tetap `is_public_visible = false`.
- Role global tidak dicampur dengan role aplikasi.

## Commands Run
- `php artisan optimize:clear` - OK.
- `php artisan test --filter=CoreAppLauncherTest` - OK, 10 passed / 29 assertions.
- `php artisan test` - OK, 124 passed / 526 assertions.

Tidak menjalankan `php artisan migrate` karena tidak ada migration baru.
Tidak menjalankan `npm run build` karena tidak ada perubahan frontend asset/CSS/JS build pipeline.

## Test Result
`php artisan test`: 124 passed / 526 assertions.

## Manual Check
- App Launcher bisa dibuka authorized user: OK, diverifikasi via `/admin/app-launcher`.
- Guest diarahkan login: OK.
- Unauthorized user ditolak: OK.
- App dengan akses aktif tampil: OK.
- App tanpa akses tidak tampil: OK.
- Inactive app/access tidak tampil: OK.
- App tanpa URL disabled: OK.
- Tidak ada token/SSO: OK.
- Tidak ada 500 error: OK.

## Guardrails Confirmation
- Tidak menjalankan migrate:fresh.
- Tidak menjalankan migrate:reset.
- Tidak menjalankan migrate:rollback.
- Tidak drop database.
- Tidak mengubah data master otomatis.
- Tidak execute import KP.
- Tidak mengubah database KP.
- Tidak menyentuh SAFA/KP/TU.
- Tidak membuat Core public.
- Tidak membuat SSO/bypass login.
- Tidak membuat cross-app token.
- Tidak membuat API baru.
- Tidak bulk reset password.
- Tidak expose/export password plaintext/hash.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.

## Risks / Notes
- SSO belum dibuat.
- Aplikasi tujuan tetap harus login sendiri.
- Internal API belum dibuat.
- App access import belum dibuat.
- Launcher hanya navigasi internal.
- URL aplikasi bergantung pada konfigurasi `CoreApplication.admin_url` atau `base_url`.

## Recommended Next Step
Rekomendasi tahap berikutnya: `CORE-API-1 Internal API Planning/Skeleton` jika integrasi antar aplikasi mulai dibutuhkan, atau `CORE-IMPORT-7 Users & App Access Import` jika prioritas berikutnya adalah bulk assignment akses aplikasi dari Excel.
