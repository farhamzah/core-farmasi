# CORE-PROFILE-2 Editable Safe Contact Fields & Profile Completion Report

## Scope
Tahap ini melengkapi Profile Portal dengan field kontak aman dan profile completion. Perubahan tetap berada di Core, tidak membuka admin panel untuk non-admin, dan tidak mengubah aplikasi KP/TU/SAFA.

## Previous Reports Reviewed
- `docs/CORE-CENTRALIZED-PROFILE-PORTAL-PLAN.md`
- `docs/reports/CORE-PROFILE-0-1-CENTRALIZED-PROFILE-PORTAL-REPORT.md`
- `app/Http/Controllers/ProfilePortalController.php`
- `app/Services/CoreProfilePortalService.php`
- `resources/views/profile/show.blade.php`
- `resources/views/profile/edit.blade.php`
- `tests/Feature/CoreProfilePortalTest.php`

## Database Changes
Migration additive dibuat:
- `database/migrations/2026_05_24_000122_add_contact_fields_to_students_table.php`

Fields:
- `students.phone` nullable string.
- `students.address` nullable text.
- `lecturers.address` nullable text.

Perubahan ini additive/non-destructive, nullable, tanpa data migration, dan tidak menghapus data existing.

## Model Changes
- `Student` fillable ditambah `phone` dan `address`.
- `Lecturer` fillable ditambah `address`.
- `Employee` sudah memiliki `phone` dan `address`, tidak perlu perubahan model.

## Profile Service Changes
`CoreProfilePortalService` diperbarui untuk:
- Resolve profile type student/lecturer/employee.
- Return safe profile summary.
- Return editable fields berdasarkan kolom yang tersedia.
- Update only safe fields: `phone`, `address`, dan `alternate_email` hanya jika suatu saat kolomnya tersedia.
- Build profile completion summary:
  - linked profile exists.
  - email available.
  - phone available.
  - address available.
  - birth date recorded status only.
- Tidak mengekspos full birth date, password, hash, token, secret, role, app access, atau official identity mutation.
- Self-only behavior tetap karena route tidak menerima target user id.

Read-only/admin-only fields:
- name resmi.
- username.
- identity_type.
- identity_number.
- NIM.
- NIDN/NIP.
- employee_number.
- national_id_number.
- study_program.
- department.
- status.
- roles.
- app access.
- leadership/jabatan.

## Controller/View Changes
Controller:
- `ProfilePortalController` tetap memakai route authenticated.
- Validation diperkuat:
  - `phone` nullable string max 50.
  - `address` nullable string max 1000.
  - `alternate_email` nullable email max 255, hanya disimpan jika kolom tersedia.
- Payload malicious untuk identity/status/role/app access tidak dipakai.

Views:
- `show.blade.php` menampilkan profile completion card dengan white base dan blue pharmacy accent.
- `show.blade.php` menampilkan status birth date hanya sebagai "Tercatat/Belum tercatat".
- `edit.blade.php` menampilkan official identity sebagai read-only dan hanya merender field kontak yang editable.
- UI tetap light, responsive basic, dan tidak dark.

Audit:
- `profile.updated` dicatat saat ada field kontak berubah.
- Metadata menyimpan changed field names only, bukan old/new values.

## Security Confirmation
- Protected route: OK.
- Non-admin still cannot access `/admin`: OK.
- Self-only update: OK.
- Official fields locked: OK.
- Role/app access locked: OK.
- Status locked: OK.
- NIM/NIDN/NIP/employee_number locked: OK.
- Identity/prodi/department/leadership locked: OK.
- No sensitive exposure: OK.
- No SSO, auto-login, cross-app session, or token URL: OK.
- No KP/TU/SAFA changes: OK.

## Commands Run
- `php -l app\Services\CoreProfilePortalService.php` - OK.
- `php -l database\migrations\2026_05_24_000122_add_contact_fields_to_students_table.php` - OK.
- `php -l tests\Feature\CoreProfilePortalTest.php` - OK.
- `php artisan test --filter=CoreProfilePortalTest` - OK, 10 passed / 57 assertions.
- `php artisan optimize:clear` - OK.
- `php artisan migrate` - OK, migration ran successfully.
- `npm.cmd run build` - OK.
- `php artisan route:list` - OK, 88 routes.
- `php artisan test` - OK, 169 passed / 854 assertions.

## Test Result
- Targeted Profile Portal test: 10 passed / 57 assertions.
- Full Core test suite: 169 passed / 854 assertions.

## Manual Check
- `/profile` show OK.
- `/profile` edit OK.
- Student phone/address OK.
- Lecturer phone/address OK.
- Employee phone/address OK.
- Malicious fields blocked/ignored.
- `/admin` still restricted for non-admin.
- No sensitive fields visible.

## Guardrails Confirmation
- Tidak menjalankan migrate:fresh/reset/rollback.
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
- Tidak mengizinkan edit identity/prodi/department/leadership.
- Tidak mengganti auth KP/TU.
- Tidak mengubah KP/TU/SAFA.
- Tidak write-back dari app lain.
- Tidak hardcode secret/credential.

## Risks / Notes
- Full profile photo upload belum dibuat.
- Profile completion masih basic dan belum menjadi workflow approval/data quality khusus.
- KP/TU link-to-profile belum ditambahkan.
- Approval workflow untuk perubahan data resmi belum dibuat.
- Email utama tetap read-only; perubahan email sebaiknya menunggu policy/verifikasi.

## Recommended Next Step
Rekomendasi tahap berikutnya:
- CORE-PROFILE-3 KP/TU Link-to-Core-Profile Integration.
- Alternatif: CORE-INTEGRATION-4 Staging Smoke SOP.
- Alternatif: CORE-UI-5 Profile Portal Polish.
