# CORE-PROFILE-0/1 Centralized Profile Portal Report

## Scope
Tahap ini membuat planning dan skeleton awal Centralized Self-Service Profile Portal di Core Farmasi UBP. Fokusnya adalah kepemilikan profil terpusat, route protected `/profile`, safe profile summary, dan update field kontak aman jika kolom profil tertaut sudah tersedia.

Tahap ini bukan full profile portal kompleks, bukan SSO, bukan auto-login, bukan cutover consumer app, dan tidak mengubah database.

## Previous Context Reviewed
- `docs/CORE-ARCHITECTURE-SUMMARY.md`
- `docs/CORE-CONSUMER-INTEGRATION-PLAN.md`
- `docs/CORE-UI-UX-DIRECTION.md`
- `app/Models/User.php`
- `app/Models/Student.php`
- `app/Models/Lecturer.php`
- `app/Models/Employee.php`
- `app/Providers/Filament/AdminPanelProvider.php`
- `routes/web.php`
- `tests/Feature/CoreAdminAccessTest.php`

## Centralized Profile Plan
- Core owns canonical user/profile data: name, username, identity type/number, email utama, student/lecturer/employee profile, app access metadata, dan official leadership data.
- Other apps should display profile data read-only and provide a link to Core Profile Portal for profile changes.
- App-specific data remains in each app: transaksi, berkas, workflow, penilaian, dan status aplikasi spesifik.
- Planning document dibuat di `docs/CORE-CENTRALIZED-PROFILE-PORTAL-PLAN.md`.

## Access Model
- Core Admin Panel tetap hanya untuk active `super-admin` dan `admin-core` melalui `User::canAccessPanel()`.
- Profile Portal tersedia di `/profile` untuk authenticated user, termasuk non-admin.
- `/profil-saya` redirect ke `/profile`.
- Non-admin tetap tidak bisa mengakses `/admin`.
- Guest ke `/profile` diarahkan ke login resmi `/admin/login`.

## Editable Field Policy
User may edit:
- `phone` jika kolom tersedia di profil tertaut.
- `address` jika kolom tersedia di profil tertaut.

Admin-only / not editable by self-service portal:
- name resmi.
- username.
- identity_type.
- identity_number.
- NIM.
- NIDN/NIP/employee_number.
- department/prodi.
- status aktif/nonaktif.
- roles.
- app access.
- leadership/jabatan.
- password melalui form profile biasa.

Catatan field saat ini:
- `lecturers` mendukung `phone`.
- `employees` mendukung `phone` dan `address`.
- `students` belum memiliki `phone` atau `address`, sehingga student profile skeleton read-only untuk kontak sampai ada migration/field resmi di tahap berikut.

## Implementation
- Route:
  - `GET /profile`
  - `GET /profile/edit`
  - `PUT /profile`
  - `ANY /profil-saya` redirect ke `/profile`
- Controller:
  - `app/Http/Controllers/ProfilePortalController.php`
- Service:
  - `app/Services/CoreProfilePortalService.php`
- Views:
  - `resources/views/profile/show.blade.php`
  - `resources/views/profile/edit.blade.php`
- Guest redirect:
  - `bootstrap/app.php` mengarahkan guest auth middleware ke `/admin/login`.
- Audit:
  - `profile.updated` dicatat ke `user_activity_logs` saat ada perubahan kontak.
  - Metadata hanya menyimpan `profile_user_id` dan `changed_fields`, bukan old/new sensitive values.

## Security Confirmation
- Protected route dengan middleware `auth`.
- Non-admin tidak mendapat akses `/admin`.
- Self-only profile karena route tidak menerima user id.
- Tidak menampilkan password, password hash, api token, remember token, app client secret, atau secret hash.
- Tidak ada edit role/app access.
- Tidak ada edit status.
- Tidak ada edit official identity field.
- Tidak ada edit NIM/NIDN/NIP/employee_number.
- Tidak ada SSO, auto-login, cross-app session, atau token URL.
- Tidak menyentuh KP/TU/SAFA.

## Documentation Updates
- `docs/CORE-CENTRALIZED-PROFILE-PORTAL-PLAN.md` dibuat.
- `docs/CORE-ARCHITECTURE-SUMMARY.md` diperbarui dengan konsep Profile Portal.
- `docs/CORE-CONSUMER-INTEGRATION-PLAN.md` diperbarui dengan rule read-only profile + link to Core.
- `README.md` diperbarui dengan status Profile Portal skeleton.

## Commands Run
- `php -l app\Http\Controllers\ProfilePortalController.php` - OK.
- `php -l app\Services\CoreProfilePortalService.php` - OK.
- `php -l tests\Feature\CoreProfilePortalTest.php` - OK.
- `php artisan test --filter=CoreProfilePortalTest` - OK, 7 passed / 35 assertions.
- `php artisan optimize:clear` - OK.
- `npm.cmd run build` - OK.
- `php artisan route:list` - OK, 88 routes.
- `php artisan test` - OK, 166 passed / 832 assertions.
- Migration not run.

## Test Result
- Targeted profile portal tests: 7 passed / 35 assertions.
- Full Core test suite: 166 passed / 832 assertions.

## Manual Check
- `/profile` guest redirected: OK, `/admin/login`.
- `/profile` authenticated OK: covered by tests.
- Student profile OK: covered by tests.
- Lecturer profile OK: covered by tests.
- Employee profile OK: covered by tests.
- `/admin` still restricted for non-admin: covered by tests.
- No sensitive fields visible: covered by safe response assertions and view/service design.

## Guardrails Confirmation
- Tidak menjalankan migrate:fresh/reset/rollback.
- Tidak menjalankan migration.
- Tidak drop database.
- Tidak membuka `/admin` untuk non-admin.
- Tidak melonggarkan role access.
- Tidak membuat SSO/bypass login.
- Tidak membuat auto-login.
- Tidak membuat cross-app session.
- Tidak membuat token URL.
- Tidak expose password/hash/token/secret.
- Tidak expose app client secret.
- Tidak mengizinkan edit role/app access/status.
- Tidak mengizinkan edit NIM/NIDN/NIP/employee_number.
- Tidak mengizinkan edit leadership/jabatan.
- Tidak mengubah KP/TU/SAFA.
- Tidak write-back dari app lain.
- Tidak hardcode secret/credential.

## Risks / Notes
- Profile Portal masih skeleton awal.
- Student contact edit belum aktif karena tabel `students` belum memiliki `phone` atau `address`.
- Email utama masih read-only; perubahan email perlu policy/verifikasi tahap lanjutan.
- Link "Ubah Profil di Core" belum ditambahkan di KP/TU.
- Profile completion workflow dan foto profil belum dibuat.
- Jika portal ini akan dipakai oleh user non-admin langsung, SOP login non-admin ke Core perlu diputuskan agar tidak membingungkan dengan login admin Filament.

## Recommended Next Step
Rekomendasi tahap berikutnya:
- CORE-PROFILE-2 Editable Safe Contact Fields & Profile Completion.
- Alternatif: CORE-INTEGRATION-4 Staging Smoke SOP.
- Alternatif: KP/TU link-to-profile integration.
