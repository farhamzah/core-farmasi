# CORE-IMPORT-7 Users & App Access Import Validation Report

## Scope
Tahap ini menambahkan validation skeleton untuk import `users`, `user_role_assignments`, dan `user_app_accesses`.

Tahap ini belum membuat execute import untuk user, global role assignment, atau app access. Tidak ada user dibuat, tidak ada role di-assign, tidak ada app access dibuat/dinonaktifkan, dan tidak ada password yang diset/reset.

## Previous Reports Reviewed
- CORE-QA-1 Stabilization, Security Review & Documentation Report
- CORE-ACCESS-1 Dynamic App Registry & App Role Catalog Report
- CORE-API-2 App Client Credentials & Token Rotation Report
- CORE-IMPORT-6 Rollback / Undo Import Safety Report

## Files Changed
- `config/core_import.php`
- `app/Services/CoreImportTemplateService.php`
- `app/Services/CoreImportValidationService.php`
- `app/Filament/Pages/CoreImportCenter.php`
- `resources/views/filament/pages/core-import-center.blade.php`
- `tests/Feature/CoreImportCenterTest.php`
- `README.md`
- `docs/reports/CORE-IMPORT-7-USERS-APP-ACCESS-VALIDATION-REPORT.md`

## Import Registry Update
`config/core_import.php` diperjelas untuk import type:

- `users`
  - Required: `name`, `username`, `identity_type`, `identity_number`
  - Optional: `email`, `birth_date`, `phone`, `is_active`, `must_change_password`
  - Role dan app access dipisah ke template khusus.
- `user_role_assignments`
  - Required: `username`, `role_slug`
  - Optional: `action`, `notes`
  - Action: `assign` atau `skip`
- `user_app_accesses`
  - Required: `username`, `app_code`, `role_slug`
  - Optional: `is_active`, `notes`, `action`
  - Action: `assign`, `deactivate`, atau `skip`

Template tetap tidak memiliki kolom `password` atau `password_confirmation`. `must_change_password` tetap diperbolehkan sebagai flag boolean, bukan kolom password plaintext.

## Users Validation
Rules:

- `name` wajib.
- `username` wajib.
- `identity_type` wajib dan harus sesuai `config/core_identity.php`.
- `identity_number` wajib.
- `email` harus valid jika diisi.
- `birth_date` harus valid jika diisi.
- `is_active` dan `must_change_password` harus boolean-like jika diisi.
- Kolom `password`, `password_confirmation`, `api_token`, `secret`, dan `client_secret` tidak diperbolehkan.

Conflict detection:

- `username` sudah ada di `users`.
- `email` sudah ada di `users`.
- `identity_number` sudah ada di `users`.
- Kombinasi `identity_type + identity_number` sudah ada di `users`.
- Duplikasi username/email/identity number dalam file menjadi warning.

Suggested actions:

- Valid user baru: `create_new`.
- User existing/conflict: `needs_admin_decision`.
- Missing required/prohibited sensitive columns: `invalid`.

Tahap ini tidak membuat user dan tidak mengatur password.

## User Role Assignment Validation
Rules:

- `username` wajib dan harus ada di `users`.
- `role_slug` wajib dan harus ada sebagai global role aktif di `roles.name`.
- `action` harus `assign` atau `skip`.
- `app_code` tidak diproses pada template ini dan diberi warning karena app role harus lewat `user_app_accesses`.
- Tidak membuat role baru otomatis.

Conflict detection:

- User sudah memiliki global role tersebut.
- User tidak ditemukan.
- Role global tidak ditemukan/tidak aktif.

Suggested actions:

- User dan role valid serta belum assigned: `assign`.
- Sudah assigned: `skip`.
- Missing user/role: `invalid`.

Tahap ini tidak melakukan attach role.

## User App Access Validation
Rules:

- `username` wajib dan harus ada di `users`.
- `app_code` wajib, harus ada di `core_applications`, dan harus aktif.
- `role_slug` wajib, harus ada di `core_application_roles` untuk `app_code` tersebut, dan harus aktif.
- `is_active` harus boolean-like jika diisi.
- `action` harus `assign`, `deactivate`, atau `skip`.
- Tidak membuat aplikasi baru otomatis.
- Tidak membuat app role baru otomatis.
- Tidak memakai global roles table sebagai sumber app role.

Conflict detection:

- User sudah memiliki active app access untuk `app_code + role_slug`.
- User memiliki app access tersebut dalam status inactive.
- `app_code` tidak ditemukan atau inactive.
- `role_slug` tidak ditemukan untuk app tersebut atau inactive.
- User tidak ditemukan.

Suggested actions:

- Access baru valid: `assign`.
- Existing active dengan action `deactivate`: `deactivate`.
- Existing active tanpa action deactivate: `skip`.
- Missing user/app/role: `invalid`.

Tahap ini tidak membuat, mengaktifkan, atau menonaktifkan app access.

## Password/Secret Handling
- Kolom `password`, `password_confirmation`, `api_token`, `secret`, dan `client_secret` ditolak pada row validation.
- Nilai sensitif tidak dimasukkan ke `normalized_data`.
- `CoreImportRecord` menyimpan normalized data yang sudah dibersihkan.
- Tidak ada plaintext password/token/secret di report, log, atau preview.
- `birth_date` hanya divalidasi sebagai data sumber. Tahap ini tidak menghitung atau menampilkan initial password.

## Import Center UI
Import Center sekarang mendukung validation/decision skeleton untuk:

- `users`
- `user_role_assignments`
- `user_app_accesses`

UI tetap menampilkan:

- Summary cards.
- Validation table.
- Status badge.
- Suggested action.
- Error/warning/conflict summary.
- Decision rows jika validation records dipersist.

Execute import untuk tiga tipe ini tetap disabled/tidak tersedia. UI menampilkan pesan bahwa tahap ini hanya validasi dan decision skeleton.

## Security Confirmation
- Import Center tetap protected di `/admin`.
- Tidak ada public route baru.
- Tidak ada master data mutation.
- Tidak membuat user baru.
- Tidak assign global role.
- Tidak assign/deactivate app access.
- Tidak set/reset password.
- Tidak membuat SSO.
- Tidak membuat auto-login.
- Tidak membuat cross-app session.
- Tidak membuat token URL.
- Tidak expose password/hash/token/secret.
- Tidak menyentuh KP/SAFA/TU.

## Commands Run
- `php artisan test --filter CoreImportCenterTest` - OK, 42 passed / 224 assertions.
- `php artisan test --filter users_and_app_access_import_types_do_not_execute_in_this_stage` - OK, 1 passed / 5 assertions.
- `php artisan optimize:clear` - OK.
- `php artisan test` - OK, 141 passed / 651 assertions.

Tidak menjalankan `php artisan migrate` karena tidak ada migration baru.
Tidak menjalankan `npm run build` karena tidak ada perubahan frontend asset/CSS/JS build pipeline.

## Test Result
`php artisan test`: 141 passed / 651 assertions.

## Manual Check
- Users validation OK.
- User role assignments validation OK.
- User app accesses validation OK.
- Prohibited sensitive columns OK.
- No data mutation OK.
- Guest rejected/redirected by existing Import Center test OK.
- Unauthorized rejected by existing Import Center test OK.
- No 500 error OK via full test suite.

## Guardrails Confirmation
- Tidak menjalankan migrate:fresh.
- Tidak menjalankan migrate:reset.
- Tidak menjalankan migrate:rollback.
- Tidak drop database.
- Tidak mengubah data master otomatis.
- Tidak membuat user baru.
- Tidak assign role.
- Tidak assign/deactivate app access.
- Tidak set/reset password.
- Tidak execute import KP.
- Tidak mengubah database KP.
- Tidak menyentuh SAFA/KP/TU.
- Tidak membuat Core public.
- Tidak membuat SSO/bypass login.
- Tidak membuat cross-app user session.
- Tidak membuat token di URL.
- Tidak expose password/hash/token/secret.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.

## Risks / Notes
- Execute users/app access belum dibuat.
- Conflict decision/execute untuk user access perlu tahap berikutnya.
- Rollback untuk users/global role/app access perlu aturan khusus sebelum execute dibuat.
- Import app roles tetap harus memakai catalog dynamic, bukan hardcode.
- API consumer belum diintegrasikan.

## Recommended Next Step
Rekomendasi tahap berikutnya: `CORE-IMPORT-8 Execute Users & App Access with Rollback Safety`.

Alasan: validation skeleton untuk users/global role/app access sudah siap, tetapi execute tahap berikutnya harus langsung dirancang bersama rollback safety agar tidak ada assignment user/app access yang sulit dibatalkan.
