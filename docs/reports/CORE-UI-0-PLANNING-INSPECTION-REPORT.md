# CORE-UI-0 Planning & Inspection Report

Tanggal inspeksi: 2026-05-23

## Scope

Tahap CORE-UI-0 ini hanya melakukan planning dan inspection untuk `apps/core-farmasi`.

Tidak ada implementasi UI besar, tidak ada fitur baru, tidak ada perubahan database, tidak ada perubahan aplikasi lain, dan tidak ada perubahan source aplikasi selain pembuatan file report ini.

Fokus inspeksi:

- Struktur aplikasi Core.
- Konfigurasi Laravel, Filament, Vite, auth, session, dan database.
- Route web/API.
- Model utama identity, role, master data, dan app access.
- Filament resources, pages, widgets, dan admin panel provider.
- Migration dan seeder hanya untuk memahami struktur.
- Asset pipeline dan folder public build/static asset.
- Dugaan penyebab halaman login Core tampil polos/unstyled.

## Files Inspected

File/folder yang dibaca:

- `apps/core-farmasi/`
- `apps/core-farmasi/composer.json`
- `apps/core-farmasi/package.json`
- `apps/core-farmasi/vite.config.js`
- `apps/core-farmasi/phpunit.xml`
- `apps/core-farmasi/.env` hanya key non-secret yang relevan
- `apps/core-farmasi/config/app.php`
- `apps/core-farmasi/config/auth.php`
- `apps/core-farmasi/config/database.php`
- `apps/core-farmasi/config/filament.php`
- `apps/core-farmasi/config/session.php`
- `apps/core-farmasi/config/kp_import.php`
- `apps/core-farmasi/routes/web.php`
- `apps/core-farmasi/routes/api.php`
- `apps/core-farmasi/bootstrap/app.php`
- `apps/core-farmasi/app/Providers/Filament/AdminPanelProvider.php`
- `apps/core-farmasi/app/Models/User.php`
- `apps/core-farmasi/app/Models/Role.php`
- `apps/core-farmasi/app/Models/UserAppAccess.php`
- `apps/core-farmasi/app/Models/Student.php`
- `apps/core-farmasi/app/Models/Lecturer.php`
- `apps/core-farmasi/app/Models/StudyProgram.php`
- `apps/core-farmasi/app/Models/Department.php`
- `apps/core-farmasi/app/Models/UserActivityLog.php`
- `apps/core-farmasi/app/Models/CoreImportBatch.php`
- `apps/core-farmasi/app/Models/CoreImportRecord.php`
- `apps/core-farmasi/app/Http/Middleware/AuthenticateApiToken.php`
- `apps/core-farmasi/app/Http/Controllers/Api/*`
- `apps/core-farmasi/app/Console/Commands/ImportKpMasterDataCommand.php`
- `apps/core-farmasi/app/Console/Commands/RollbackKpImportCommand.php`
- `apps/core-farmasi/app/Services/KpMasterDataDryRunAuditor.php`
- `apps/core-farmasi/app/Filament/Resources/*`
- `apps/core-farmasi/database/migrations/*`
- `apps/core-farmasi/database/seeders/DatabaseSeeder.php`
- `apps/core-farmasi/resources/css/app.css`
- `apps/core-farmasi/resources/js/app.js`
- `apps/core-farmasi/resources/js/bootstrap.js`
- `apps/core-farmasi/public/`
- `apps/core-farmasi/vendor/filament/*`
- `docs/CORE-MYSQL-SWITCH-REPORT.md`

Tidak ditemukan:

- `apps/core-farmasi/tailwind.config.*`
- `apps/core-farmasi/postcss.config.*`
- `apps/core-farmasi/app/Filament/Pages`
- `apps/core-farmasi/app/Filament/Widgets`
- `apps/core-farmasi/node_modules`
- `apps/core-farmasi/public/build/manifest.json`
- `apps/core-farmasi/public/css`
- `apps/core-farmasi/public/js`
- `apps/core-farmasi/public/fonts`

## Current Core Structure

`core-farmasi` adalah aplikasi Laravel 12 dengan Filament 5.6.5 sebagai admin panel.

Struktur utama:

- `app/Models`: model identity, role, master data, app access, activity log, dan audit import.
- `app/Filament/Resources`: resource admin untuk data Core.
- `app/Providers/Filament/AdminPanelProvider.php`: konfigurasi panel admin `/admin`.
- `routes/web.php`: root `/` redirect ke `/admin`.
- `routes/api.php`: API health, login, token validation, dan endpoint baca master data.
- `config/kp_import.php`: mapping import master data KP ke Core.
- `database/migrations`: struktur tabel Core.
- `database/seeders/DatabaseSeeder.php`: user admin awal, role, department, study program, dan app access awal.
- `resources/css/app.css` dan `resources/js/app.js`: entry Vite default Laravel.
- `public/`: hanya `index.php`, `.htaccess`, `favicon.ico`, `robots.txt`, dan symlink `storage`.

Panel Filament:

- Path panel: `/admin`.
- Login panel: aktif via `->login()`.
- Route login terdaftar: `/admin/login`.
- Route `/login` tidak terdaftar di `php artisan route:list`.
- Resource discovery aktif dari `app/Filament/Resources`.
- Page default hanya `Filament\Pages\Dashboard`.
- Widget default hanya `AccountWidget` dan `FilamentInfoWidget`.
- Tidak ada custom Filament `Pages` atau `Widgets`.
- Warna panel saat ini `Color::Amber`.
- Belum ada `brandName`, `brandLogo`, atau custom theme.

Resource admin yang sudah ada:

- `DepartmentResource`
- `LecturerResource`
- `RoleResource`
- `StudentResource`
- `StudyProgramResource`
- `UserActivityLogResource`
- `UserAppAccessResource`
- `UserResource`

## Login UI Problem Analysis

Dugaan terkuat penyebab login tampil polos/unstyled adalah asset CSS/JS Filament atau frontend asset tidak tersedia/ter-load oleh browser.

Bukti:

- `public/build/manifest.json` tidak ada.
- `public/build` tidak ditemukan pada inspeksi.
- `public/css`, `public/js`, dan `public/fonts` tidak ditemukan.
- `node_modules` tidak ada, sehingga build frontend lokal belum siap dijalankan tanpa install dependency.
- `php artisan about` menunjukkan `Views: CACHED`, tetapi `Routes` dan `Config` tidak cached. View cache sendiri biasanya bukan penyebab utama unstyled, tetapi bisa membuat diagnosis UI terasa membingungkan jika view lama masih tersimpan.
- `php artisan route:list` menunjukkan login Core yang sah adalah `GET admin/login` dari Filament, bukan route custom `/login`.
- `AdminPanelProvider` tidak mengarah ke custom theme file, sehingga tidak ada bukti theme custom menunjuk ke file yang hilang.

Kemungkinan skenario:

1. Filament login `/admin/login` sudah benar, tetapi CSS/JS Filament tidak ter-publish atau tidak bisa diakses oleh server.
2. Browser membuka route selain `/admin/login`, misalnya `/login`, yang bukan route Core dan bukan login Filament. Dari route list, `/login` tidak terdaftar.
3. Vite app asset Laravel belum dibuild, tetapi untuk login Filament default, faktor yang lebih penting adalah asset Filament/Livewire/Filament package dapat dilayani dengan benar.
4. Web server/document root mungkin tidak mengarah ke `apps/core-farmasi/public`, atau rewrite/static asset routing tidak benar. Ini belum diverifikasi lewat browser/devtools pada tahap ini.

## Filament & Asset Pipeline Findings

Composer:

- Laravel: `12.60.2`.
- Filament: `v5.6.5`.
- Livewire: `v4.3.0`.
- `vendor/filament` tersedia.

NPM/Vite:

- `package.json` memiliki script `build: vite build` dan `dev: vite`.
- Dev dependency mencakup `vite`, `laravel-vite-plugin`, `tailwindcss`, dan `@tailwindcss/vite`.
- `vite.config.js` input: `resources/css/app.css` dan `resources/js/app.js`.
- `resources/css/app.css` menggunakan Tailwind v4 style: `@import 'tailwindcss';` dan `@source`.
- Tidak ada `tailwind.config.*` atau `postcss.config.*`; ini masih wajar untuk setup Tailwind v4 sederhana.
- `node_modules` tidak ada, sehingga `npm run build` kemungkinan tidak siap tanpa `npm install`.
- `public/build/manifest.json` tidak ada, berarti asset Vite app belum tersedia.

Filament:

- `AdminPanelProvider` tidak memakai custom theme seperti `->viteTheme(...)`.
- `config/filament.php` memakai `assets_path => null`, sehingga belum ada custom assets path.
- `php artisan about` menunjukkan Filament package terdeteksi dan `Views: NOT PUBLISHED`.
- Tidak ditemukan asset Filament yang sudah dipublish di `public/css`, `public/js`, atau `public/fonts`.

Implikasi:

- Jika Filament v5 pada setup ini membutuhkan publish asset ke public path, maka perlu verifikasi `php artisan filament:assets` pada tahap berikutnya.
- Jika asset seharusnya dilayani dari route package, perlu cek browser network/devtools untuk request CSS/JS yang 404 atau diblokir.
- Karena belum ada `public/build/manifest.json`, setiap Blade/layout yang memakai `@vite(...)` untuk `resources/css/app.css` atau `resources/js/app.js` akan bermasalah sampai `npm run build` berhasil dijalankan.

## Auth & Security Findings

Login dan route:

- Root `/` redirect ke `/admin`.
- Admin login resmi adalah `/admin/login`.
- Tidak ada route custom `/login`.
- Direct URL `/admin`, `/admin/users`, dan resource lain dilindungi middleware auth Filament.
- Test `CoreAdminAccessTest` membuktikan guest redirect ke `/admin/login`.

Panel access:

- `User` mengimplementasikan `FilamentUser`.
- `canAccessPanel()` mensyaratkan:
  - `active = true`
  - role aktif bernama `super-admin` atau `admin-core`
- User inactive ditolak dari panel karena kondisi `active` wajib true.
- User dengan role non-core seperti `mahasiswa` ditolak dari admin panel; ini sudah ada test-nya.

API auth:

- `POST /api/v1/auth/login` hanya mengizinkan user dengan `active = true`.
- Password dicek via `Hash::check`.
- Token plaintext diberikan saat login, hash SHA-256 disimpan di `users.api_token`.
- Middleware `auth.api` memverifikasi bearer token dan juga mensyaratkan user aktif.
- Endpoint API master data selain login/health dilindungi `auth.api`.

Catatan security:

- Setelah masuk sebagai `super-admin` atau `admin-core`, resource CRUD cukup luas. Ada bulk delete di beberapa resource. Ini bukan masalah login polos, tetapi perlu kehati-hatian di tahap polish agar tidak membuka destructive UI ke role yang tidak tepat.
- Belum terlihat policy granular per resource. Kontrol utama saat ini ada di akses panel.
- Tidak ada bukti auto-login/SSO antar aplikasi.

## Resource & Dashboard Findings

Resource admin sudah mencakup objek Core utama:

- Users: form name/email/password/active/roles; table id/name/email/active/roles/created_at.
- Roles: role global.
- Departments: master department/fakultas.
- Study Programs: master prodi dan department.
- Students: nomor mahasiswa, nama, email, user, prodi, status, active.
- Lecturers: nomor dosen, nama, email, user, department, prodi, phone, notes, active.
- User App Accesses: user, app_code, role_slug, permissions, active flag, activated/deactivated timestamp.
- User Activity Logs: list log aktivitas.

Dashboard:

- Menggunakan dashboard bawaan Filament.
- Widget bawaan: `AccountWidget` dan `FilamentInfoWidget`.
- Tidak ada dashboard khusus Core.

Kondisi UI:

- Struktur CRUD sudah ada, tetapi masih sangat default.
- Brand Core belum dipoles.
- Login belum punya brand-specific copy/logo/visual.
- Dashboard belum menonjolkan ringkasan operasional Core seperti total users, active users, role count, app access count, dan master data count.

## App Access / Integration Findings

`UserAppAccess` sudah cukup sebagai basis awal untuk app shortcut di tahap berikutnya.

Struktur yang tersedia:

- `user_id`
- `app_code`
- `role_slug`
- `permissions` JSON
- `is_active`
- `activated_at`
- `deactivated_at`
- unique key `user_id + app_code + role_slug`

Seeder sudah membuat app access super-admin untuk:

- `core-farmasi`
- `safa-ubp`
- `kp-farmasi`
- `ta-farmasi`

Resource admin `UserAppAccessResource` sudah menyediakan pilihan app code tersebut.

Catatan:

- Belum ada tabel metadata aplikasi seperti nama aplikasi, URL, icon, visibility, atau urutan shortcut.
- Shortcut tahap berikutnya bisa mulai dari mapping konfigurasi statis berbasis `app_code`, lalu membaca `UserAppAccess` aktif.
- Jangan menjadikan shortcut sebagai SSO atau bypass login. Shortcut cukup berupa link ke aplikasi internal dengan tetap mengikuti auth masing-masing aplikasi.

## SAFA Visibility Findings

Pada inspeksi Core:

- Tidak ditemukan route yang membuat Core tampil sebagai halaman publik SAFA.
- Route public Core hanya `/` yang redirect ke `/admin`, `/api/v1/health`, dan `POST /api/v1/auth/login`.
- Admin UI berada di `/admin` dan wajib login.
- Tidak ada perubahan atau pembacaan mendalam ke `apps/safa-ubp` pada tahap ini.

Risiko Core tampil di SAFA public portal lebih mungkin berasal dari konfigurasi/link di SAFA atau deployment/web server, bukan dari route Core yang dibaca saat ini. Tahap ini tidak mengubah SAFA.

## Recommended Next Steps

Rekomendasi bertahap untuk CORE-UI-1, dari yang paling aman:

1. Asset/CSS/Filament theme fix.
   - Verifikasi browser network untuk `/admin/login`: catat request CSS/JS yang 404 atau blocked.
   - Pastikan document root mengarah ke `apps/core-farmasi/public`.
   - Jalankan dependency/build hanya pada tahap implementasi: `npm install` jika `node_modules` belum ada, lalu `npm run build`.
   - Pertimbangkan `php artisan filament:assets` jika Filament asset memang belum tersedia.
   - Jalankan `php artisan optimize:clear` hanya bila cache terbukti menghalangi asset/view terbaru.

2. Login UI polish.
   - Tetap gunakan login Filament, jangan membuat route login custom.
   - Tambahkan brand Core Farmasi UBP melalui konfigurasi panel atau customization Filament resmi.
   - Jangan membuat auto-login, SSO, atau bypass password.

3. Dashboard polish.
   - Tambahkan widget ringkasan Core yang aman: total user, user aktif, roles, app access, students, lecturers, study programs.
   - Hindari menampilkan data sensitif berlebihan di dashboard.

4. Resource polish.
   - Rapikan label, grouping, table columns, filters, dan empty states.
   - Evaluasi bulk delete pada data sensitif sebelum polish production.

5. Security hardening.
   - Pertimbangkan policy/authorization granular untuk resource sensitif.
   - Pertimbangkan rate limiting login/API.
   - Pertimbangkan audit log untuk perubahan data master.
   - Pastikan inactive user tetap tidak bisa login panel/API.

6. App shortcut.
   - Gunakan `UserAppAccess` aktif sebagai dasar visibility shortcut.
   - Simpan mapping URL/nama/icon aplikasi di config, bukan hardcode tersebar di view.
   - Shortcut harus link biasa, bukan SSO atau token bridge.

## Commands Run

Command yang dijalankan:

- `php artisan about`
  - Result: success.
  - Ringkasan: Application Name `Core Farmasi UBP`; Laravel `12.60.2`; PHP `8.2.12`; environment `local`; timezone `Asia/Jakarta`; database `mysql`; Filament `v5.6.5`; Livewire `v4.3.0`; routes/config not cached; views cached; storage linked.

- `php artisan route:list`
  - Result: success.
  - Ringkasan: 49 routes. Route admin utama termasuk `/admin`, `/admin/login`, `/admin/users`, `/admin/students`, `/admin/lecturers`, `/admin/roles`, `/admin/departments`, `/admin/study-programs`, `/admin/user-app-accesses`, `/admin/user-activity-logs`. API utama berada di `/api/v1/*`. Tidak ada `/login`.

- `php artisan test`
  - Result: success.
  - Ringkasan: 24 tests passed, 68 assertions.

Command yang sengaja tidak dijalankan:

- `npm run build`
  - Alasan: `node_modules` tidak ada dan tahap ini inspection-only. Build akan menghasilkan artifact baru di `public/build`, sehingga lebih tepat dilakukan pada tahap CORE-UI-1 setelah rekomendasi disetujui.

- `php artisan optimize:clear`
  - Alasan: tidak diperlukan untuk inspection. Command ini diizinkan bila diperlukan untuk membaca kondisi cache, tetapi bukti yang dibutuhkan sudah cukup dari `php artisan about` dan inspeksi file.

## Test Result

`php artisan test` dijalankan dari `apps/core-farmasi`.

Hasil:

- Tests: 24 passed.
- Assertions: 68.
- Duration: 2.38s.

Test memakai `DB_CONNECTION=sqlite` dan `DB_DATABASE=:memory:` dari `phpunit.xml`, sehingga tidak mengubah database MySQL Core atau database KP.

## Guardrails Confirmation

Konfirmasi guardrails:

- Tidak menjalankan `migrate:fresh`.
- Tidak menjalankan `migrate:reset`.
- Tidak menjalankan `migrate:rollback`.
- Tidak drop database.
- Tidak menghapus database.
- Tidak menghapus data existing.
- Tidak mengubah database KP.
- Tidak execute import KP.
- Tidak mengubah `apps/kp-farmasi`.
- Tidak mengubah `apps/safa-ubp`.
- Tidak membuat Core tampil di SAFA public portal.
- Tidak hardcode password, token, secret, API key, atau credential.
- Tidak membuat auto-login/SSO antar aplikasi.
- Tidak membuat perubahan destruktif.
- Tidak menjalankan command yang mengubah database schema/production data.

## Risks / Notes

- Masalah login polos paling aman diverifikasi berikutnya lewat browser network/devtools untuk memastikan asset mana yang gagal.
- Jangan langsung membuat login custom karena bisa menggeser proteksi Filament dan memperbesar risiko security.
- Jangan menggabungkan shortcut aplikasi dengan SSO/token bridge pada tahap UI polish.
- Jangan memperbaiki UI dengan mengubah SAFA public portal.
- Perlu hati-hati dengan bulk delete di resource admin karena Core menyimpan data sensitif.
- `views` saat ini cached. Jika tahap berikutnya mengubah tampilan dan hasil tidak terlihat, `php artisan optimize:clear` bisa dipakai dengan alasan jelas.
- `public/build/manifest.json` belum ada. Jika ada layout yang memakai Vite asset, build frontend perlu dibuat pada tahap implementasi.
