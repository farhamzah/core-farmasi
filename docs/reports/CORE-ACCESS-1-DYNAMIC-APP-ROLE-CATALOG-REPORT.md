# CORE-ACCESS-1 Dynamic App Registry & App Role Catalog Report

Tanggal pengerjaan: 2026-05-23

## Scope

Tahap CORE-ACCESS-1 membuat skeleton dynamic app registry dan app role catalog agar aplikasi baru dan role aplikasi baru dapat ditambahkan dari Core tanpa hardcode role di banyak tempat.

Tahap ini tetap membedakan:

- Role global di `roles`.
- Role aplikasi di `core_application_roles`.
- Assignment user ke aplikasi/role di `user_app_accesses`.

Tahap ini belum membuat SSO, auto-login, app shortcut/launcher, API baru, import execute, atau koneksi ke aplikasi lain.

## Previous Reports Reviewed

Report yang dibaca:

- `docs/reports/CORE-ACCESS-ORG-0-IDENTITY-ROLES-LEADERSHIP-PLANNING-REPORT.md`
- `docs/reports/CORE-ORG-1-LEADERSHIP-ASSIGNMENTS-SKELETON-REPORT.md`
- `docs/reports/CORE-IMPORT-3-ROW-VALIDATION-CONFLICT-DETECTION-REPORT.md`

File existing yang diperiksa:

- `app/Models/UserAppAccess.php`
- `app/Filament/Resources/UserAppAccessResource.php`
- `app/Models/User.php`
- `app/Models/Role.php`
- `database/migrations/2026_01_01_000106_create_user_app_accesses_table.php`
- `database/seeders/DatabaseSeeder.php`
- `tests/*`

## Files Changed

File dibuat:

- `database/migrations/2026_05_23_000116_create_core_applications_table.php`
- `database/migrations/2026_05_23_000117_create_core_application_roles_table.php`
- `app/Models/CoreApplication.php`
- `app/Models/CoreApplicationRole.php`
- `database/seeders/CoreApplicationSeeder.php`
- `app/Filament/Resources/CoreApplicationResource.php`
- `app/Filament/Resources/CoreApplicationResource/Pages/ListCoreApplications.php`
- `app/Filament/Resources/CoreApplicationResource/Pages/CreateCoreApplication.php`
- `app/Filament/Resources/CoreApplicationResource/Pages/EditCoreApplication.php`
- `app/Filament/Resources/CoreApplicationRoleResource.php`
- `app/Filament/Resources/CoreApplicationRoleResource/Pages/ListCoreApplicationRoles.php`
- `app/Filament/Resources/CoreApplicationRoleResource/Pages/CreateCoreApplicationRole.php`
- `app/Filament/Resources/CoreApplicationRoleResource/Pages/EditCoreApplicationRole.php`
- `tests/Feature/CoreAccessAppRegistryTest.php`
- `docs/reports/CORE-ACCESS-1-DYNAMIC-APP-ROLE-CATALOG-REPORT.md`

File diubah:

- `app/Models/UserAppAccess.php`
- `app/Filament/Resources/UserAppAccessResource.php`
- `database/seeders/DatabaseSeeder.php`

Tidak ada file di `apps/kp-farmasi`, `apps/safa-ubp`, atau `apps/tu-farmasi` yang diubah.

## Database Changes

Migration baru:

- `2026_05_23_000116_create_core_applications_table.php`
- `2026_05_23_000117_create_core_application_roles_table.php`

Tabel `core_applications`:

- `id`
- `app_code` unique
- `name`
- `description`
- `base_url`
- `admin_url`
- `icon`
- `color`
- `is_active`
- `is_public_visible`
- `requires_login`
- `is_sensitive`
- `sort_order`
- `notes`
- timestamps
- soft deletes

Tabel `core_application_roles`:

- `id`
- `core_application_id` nullable FK ke `core_applications`
- `app_code`
- `role_slug`
- `role_name`
- `description`
- `is_active`
- `sort_order`
- `notes`
- timestamps
- soft deletes

Unique/index:

- `core_applications.app_code` unique.
- `core_application_roles` unique `app_code + role_slug`.
- Index untuk `app_code`, `is_active`, dan `sort_order`.

Sifat perubahan:

- Additive dan non-destruktif.
- Tidak mengubah schema `user_app_accesses` existing.
- Tidak menghapus data existing.

## Models

Model baru:

- `App\Models\CoreApplication`
- `App\Models\CoreApplicationRole`

`CoreApplication`:

- casts boolean untuk `is_active`, `is_public_visible`, `requires_login`, `is_sensitive`.
- casts integer untuk `sort_order`.
- `roles()` hasMany `CoreApplicationRole`.
- `userAppAccesses()` hasMany `UserAppAccess` via `app_code`.
- scope `active()`.

`CoreApplicationRole`:

- belongsTo `CoreApplication`.
- casts boolean `is_active`.
- casts integer `sort_order`.
- scope `active()`.

Update `UserAppAccess`:

- `application()` belongsTo `CoreApplication` via `app_code`.
- `applicationRole` accessor mencari role berdasarkan `app_code + role_slug`.
- `application_role_name` accessor untuk tampilan resource.

Catatan:

- Composite lookup untuk role aplikasi dibuat sebagai accessor, bukan Eloquent relation murni, agar aman untuk lazy loading dan tidak bermasalah di SQLite/test.

## Seed Data

Seeder baru:

- `Database\Seeders\CoreApplicationSeeder`

`DatabaseSeeder` memanggil `CoreApplicationSeeder`.

Aplikasi yang diseed:

- `core-farmasi`
- `kp-farmasi`
- `safa-ubp`
- `tu-farmasi`

Nilai penting:

- `core-farmasi`
  - `is_public_visible=false`
  - `requires_login=true`
  - `is_sensitive=true`
  - `is_active=true`

Role aplikasi yang diseed:

- `core-farmasi`
  - `super-admin`
  - `admin-core`
- `kp-farmasi`
  - `mahasiswa`
  - `koordinator-kp`
  - `pembimbing-dalam`
  - `pembimbing-lapangan`
  - `penguji`
  - `admin-kp`
- `tu-farmasi`
  - `admin-tu`
  - `staf-tu`
  - `dosen`
  - `mahasiswa`
  - `validator`
  - `penandatangan`
- `safa-ubp`
  - `admin-safa`

Seeder menggunakan `updateOrCreate`, sehingga idempotent dan tidak membuat duplicate saat dijalankan ulang.

Catatan:

- Seed role ini adalah app-specific role catalog, bukan global roles.
- Role global existing tidak dihapus atau diubah secara destruktif.

## Filament Resources

Resource baru:

- `CoreApplicationResource`
- `CoreApplicationRoleResource`

Update resource existing:

- `UserAppAccessResource`

Navigation:

- Group: `Access Control`
- `Aplikasi`
- `Role Aplikasi`
- `User App Access`

`CoreApplicationResource`:

- Table:
  - app code
  - name
  - active
  - public visible
  - requires login
  - sensitive
  - admin URL
  - sort order
- Form:
  - app code
  - name
  - description
  - base URL
  - admin URL
  - icon
  - color
  - active/public/login/sensitive toggles
  - sort order
  - notes
- Filters:
  - active
  - public visible
  - requires login
  - sensitive

`CoreApplicationRoleResource`:

- Table:
  - application name
  - app code
  - role slug
  - role name
  - active
  - sort order
- Form:
  - application
  - app code
  - role slug
  - role name
  - description
  - active
  - sort order
  - notes
- Filters:
  - app code
  - active

`UserAppAccessResource` update:

- `app_code` select dari `CoreApplication` active apps.
- `role_slug` select dari `CoreApplicationRole` active roles sesuai `app_code`.
- Table menampilkan application name dan role name.
- `app_code` dan `role_slug` ditampilkan sebagai badge.
- Filter app dan role ditambahkan.

## Dynamic App Role Support

Desain sekarang mendukung:

- Admin membuat aplikasi baru di `CoreApplicationResource`.
- Admin membuat role aplikasi baru di `CoreApplicationRoleResource`.
- Admin assign role aplikasi ke user melalui `UserAppAccessResource`.
- Role aplikasi baru seperti `dossier-dosen: reviewer` bisa disimpan tanpa menambah global role dan tanpa mengubah kode untuk daftar role hardcoded.

Contoh yang dites:

- Aplikasi: `dossier-dosen`
- Role: `reviewer`, `validator`
- Assignment: user + `app_code=dossier-dosen` + `role_slug=reviewer`

Hasil:

- Assignment bisa dibuat.
- `user_app_accesses` tetap menjadi assignment utama.
- Global role tidak bertambah saat role aplikasi baru dibuat.

## Security Confirmation

Konfirmasi keamanan:

- Resource protected di admin Filament.
- `/admin` tetap protected.
- `canAccessPanel` tidak diubah dan tidak dilonggarkan.
- Core tetap `is_public_visible=false`.
- Default `is_public_visible=false` untuk app registry.
- Tidak ada SSO.
- Tidak ada auto-login.
- Tidak ada app shortcut/launcher.
- Tidak ada API baru.
- Tidak ada public route baru.
- Tidak mengubah login resmi `/admin/login`.
- Role global tidak dicampur dengan app role.
- Tidak ada password plaintext.
- Tidak ada bulk reset password.

## Commands Run

Command yang dijalankan:

- `php artisan optimize:clear`
  - Result: OK.
- `php artisan migrate`
  - Result: OK.
  - Migration `core_applications` dan `core_application_roles` berhasil dijalankan.
- `php artisan db:seed --force`
  - Result: OK.
  - `CoreApplicationSeeder` berhasil dijalankan.
  - Seeder memakai `updateOrCreate`.
- `php artisan test`
  - Run pertama: gagal 2 test pada accessor role aplikasi karena relasi composite memakai `whereColumn` tidak cocok untuk lazy loading SQLite.
  - Perbaikan: `applicationRole` diubah menjadi accessor aman berdasarkan `app_code + role_slug`, dan table memakai `application_role_name`.
- `php artisan test --filter=CoreAccessAppRegistryTest`
  - Result: OK.
  - 9 tests passed / 59 assertions.
- `php artisan test`
  - Result: OK.
  - 88 tests passed / 368 assertions.
- `php artisan route:list --path=core-app`
  - Result: OK.
  - Routes `admin/core-applications` dan `admin/core-application-roles` tersedia.

`npm run build` tidak dijalankan karena tidak ada perubahan frontend CSS/JS/Vite asset.

## Test Result

Hasil akhir `php artisan test`:

- Tests: 88 passed.
- Assertions: 368.
- Duration: 9.92s.

Test baru mencakup:

- Tabel `core_applications` dan `core_application_roles` tersedia.
- Model dan relasi/accessor registry bekerja.
- Seeder idempotent.
- `core-farmasi` tetap not public visible, requires login, dan sensitive.
- App role catalog dapat menyimpan role aplikasi baru tanpa mengubah global roles.
- `user_app_accesses` dapat assign role aplikasi baru ke user.
- Resource `Aplikasi`, `Role Aplikasi`, dan `User App Access` dapat dibuka oleh super-admin.
- Guest diarahkan ke `/admin/login`.
- User tanpa role Core admin ditolak.
- Tidak ada route SSO atau app launcher baru.

## Manual Check

Checklist:

- Menu Aplikasi muncul authorized user: OK via route/resource test.
- Menu Role Aplikasi muncul authorized user: OK via route/resource test.
- Bisa tambah aplikasi baru: OK via model test dan create page test.
- Bisa tambah role aplikasi baru: OK via model test dan create page test.
- Bisa assign role aplikasi ke user: OK via model test dan UserAppAccessResource test.
- Guest diarahkan login: OK.
- Unauthorized user ditolak: OK.
- Core `is_public_visible=false`: OK.
- Tidak ada 500 error: OK via full test suite.

## Guardrails Confirmation

Konfirmasi:

- Tidak menjalankan `migrate:fresh`.
- Tidak menjalankan `migrate:reset`.
- Tidak menjalankan `migrate:rollback`.
- Tidak drop database.
- Tidak menghapus data existing.
- Tidak execute import KP.
- Tidak mengubah database KP.
- Tidak menyentuh `apps/safa-ubp`.
- Tidak menyentuh `apps/kp-farmasi`.
- Tidak menyentuh `apps/tu-farmasi`.
- Tidak membuat Core public.
- Tidak membuat SSO/bypass login.
- Tidak membuat app shortcut/launcher.
- Tidak membuat API baru.
- Tidak membuat import execute.
- Tidak bulk reset password.
- Tidak expose/export password plaintext.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.
- Tidak hardcode secret/credential baru.

## Risks / Notes

Sisa pekerjaan:

- App shortcut/launcher belum dibuat.
- Internal API belum dibuat.
- Import `user_app_accesses` belum execute.
- Admin decision UI untuk conflict import belum dibuat.
- Data quality dashboard belum dibuat.
- Leadership import belum dibuat.

Catatan teknis:

- `CoreApplicationRole.core_application_id` nullable untuk kompatibilitas, tetapi `app_code + role_slug` tetap menjadi key operasional.
- `UserAppAccess` tidak diubah schema-nya agar data existing tetap aman.
- Dependent role select di `UserAppAccessResource` sudah membaca app role catalog aktif berdasarkan `app_code`.

## Recommended Next Step

Rekomendasi tahap berikutnya:

- Jika prioritas owner adalah menyelesaikan alur import: `CORE-IMPORT-4 Admin Decision UI for Conflicts`.
- Jika prioritas owner adalah navigasi antar aplikasi internal: `CORE-ACCESS-2 Internal App Launcher tanpa SSO`.

Rekomendasi teknis utama:

- Lanjut `CORE-IMPORT-4` lebih dulu jika data master akan segera diisi massal.
- Lanjut `CORE-ACCESS-2` lebih dulu jika admin membutuhkan launcher internal berbasis app registry yang tetap protected dan tanpa SSO.
