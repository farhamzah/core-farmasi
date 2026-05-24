# CORE-IMPORT-8 Users & App Access Execute/Rollback Report

## Scope
Tahap ini menambahkan execute dan rollback safety untuk import:

- `users`
- `user_role_assignments`
- `user_app_accesses`

Tahap ini melanjutkan validation skeleton CORE-IMPORT-7. Eksekusi hanya berjalan untuk row valid yang punya `admin_decision` executable. `skip`, `invalid`, dan pending decision tidak dieksekusi.

Tahap ini bukan SSO, bukan auto-login, bukan API baru, dan bukan app shortcut baru.

## Previous Reports Reviewed
- CORE-IMPORT-7 Users & App Access Import Validation Report
- CORE-IMPORT-6 Rollback / Undo Import Safety Report
- CORE-IMPORT-5 Execute Import for Students, Lecturers & Employees Report
- CORE-API-2 App Client Credentials & Token Rotation Report
- CORE-ACCESS-1 Dynamic App Registry & App Role Catalog Report

## Files Changed
- `app/Services/CoreImportExecutionService.php`
- `app/Services/CoreImportRollbackService.php`
- `app/Services/CoreImportPreviewService.php`
- `app/Filament/Pages/CoreImportCenter.php`
- `resources/views/filament/pages/core-import-center.blade.php`
- `tests/Feature/CoreImportCenterTest.php`
- `README.md`
- `docs/reports/CORE-IMPORT-8-USERS-APP-ACCESS-EXECUTE-ROLLBACK-REPORT.md`

Tidak ada migration baru.

## Execution Service Updates
`CoreImportExecutionService` sekarang mendukung:

- `students`
- `lecturers`
- `employees`
- `users`
- `user_role_assignments`
- `user_app_accesses`

Transaction strategy:

- Per-row DB transaction tetap dipakai.
- Satu row gagal tidak membatalkan seluruh batch.
- Row gagal diberi `execution_status=failed`.
- Batch bisa menjadi `executed`, `partially_failed`, atau `failed`.

Summary execution diperluas dengan:

- `role_assignments_assigned_count`
- `app_accesses_assigned_count`
- `app_accesses_deactivated_count`

## Users Execution
`users` mendukung:

- `create_new`
- `update_existing`
- `skip`
- `invalid`

Create new:

- Validasi ulang `username`, `email`, `identity_type`, dan `identity_number`.
- `birth_date` wajib untuk user baru karena initial password harus aman.
- Password dibuat dari `birth_date` melalui `CoreInitialPasswordService`.
- Password selalu hashed.
- `must_change_password=true`.
- `password_changed_at=null`.
- Tidak ada password plaintext ditampilkan/disimpan.

Missing birth date:

- Row gagal aman.
- User tidak dibuat.
- Tidak ada fallback password lemah seperti username, NIM, `123456`, atau `password`.

Update existing:

- Cari user berdasarkan `username`.
- Update field aman: `name`, `email`, `identity_type`, `identity_number`, `active`, dan `must_change_password` jika eksplisit.
- Tidak mengubah `username`.
- Tidak mengubah/reset password.
- Menyimpan `previous_snapshot` untuk rollback.

## User Role Assignment Execution
`user_role_assignments` mendukung:

- `assign`
- `skip`
- `invalid`

Assign:

- `username` harus ada.
- `role_slug` harus ada sebagai global role aktif di `roles`.
- Role global tidak dibuat otomatis.
- Jika assignment belum ada, pivot `user_roles` dibuat.
- Jika assignment sudah ada sebelum import, row ditandai `skipped`.
- Metadata menyimpan apakah assignment sudah ada sebelum import.

Rollback safety:

- Assignment yang dibuat batch dihapus dari pivot.
- Assignment yang sudah ada sebelum import tidak dihapus.

## User App Access Execution
`user_app_accesses` mendukung:

- `assign`
- `deactivate`
- `skip`
- `invalid`

Assign:

- `username` harus ada.
- `app_code` harus ada dan aktif di `core_applications`.
- `role_slug` harus ada dan aktif di `core_application_roles` untuk `app_code` tersebut.
- App dan app role tidak dibuat otomatis.
- Jika access belum ada, `UserAppAccess` dibuat aktif.
- Jika access inactive sudah ada, row dapat re-activate dengan snapshot.
- Jika access active sudah ada, row ditandai `skipped`.

Deactivate:

- Existing active `UserAppAccess` diset inactive.
- `previous_snapshot` disimpan untuk rollback.
- Tidak hard delete.

## Rollback Updates
`CoreImportRollbackService` sekarang mendukung rollback untuk:

- `users`
- `user_role_assignments`
- `user_app_accesses`

Users:

- `create_new`: soft delete user hanya jika aman.
- Jika user sudah punya role, app access, atau dipakai profile lain, rollback menjadi `manual_review`.
- `update_existing`: restore `previous_snapshot`.
- Password tidak disnapshot dan tidak direstore.

User role assignments:

- Assignment yang dibuat batch dan sebelumnya tidak ada akan dilepas dari pivot.
- Assignment yang sudah ada sebelum import tidak dilepas.

User app accesses:

- App access yang dibuat batch akan dinonaktifkan.
- App access yang di-reactivate atau di-deactivate akan restore `previous_snapshot`.
- Jika snapshot tidak tersedia, rollback masuk `manual_review`.

## UI Updates
Import Center:

- Execute button sekarang bisa dipakai untuk `users`, `user_role_assignments`, dan `user_app_accesses` jika batch sudah decision-ready.
- Confirmation diperjelas:
  - users dapat dibuat/diupdate,
  - role global dapat di-assign,
  - app access dapat di-assign/deactivate,
  - password tidak ditampilkan,
  - user baru memakai password awal dari `birth_date`.
- Rollback button tetap memakai confirmation kuat.
- Summary execution menampilkan count lama dan tetap menyimpan count baru di batch summary.

## Audit/Logging
`UserActivityLog` tetap digunakan melalui service existing.

Dicatat:

- batch executed
- row created/updated/assigned/deactivated
- rollback started/result/completed
- user created from import

Tidak dicatat:

- password plaintext
- password hash
- token
- secret

## Security Confirmation
- Execute protected di `/admin/import-center`.
- Rollback protected di `/admin/import-center`.
- Tidak ada public route baru.
- Tidak ada plaintext password.
- Tidak ada password export.
- Tidak ada SSO.
- Tidak ada auto-login.
- Tidak ada token URL.
- Tidak membuat global role otomatis.
- Tidak membuat app otomatis.
- Tidak membuat app role otomatis.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.
- Tidak menyentuh KP/SAFA/TU.

## Commands Run
- `php artisan test --filter CoreImportCenterTest` - OK, 48 passed / 264 assertions.
- `php artisan optimize:clear` - OK.
- `php artisan test` - OK, 146 passed / 686 assertions.

Tidak menjalankan `php artisan migrate` karena tidak ada migration baru.
Tidak menjalankan `npm run build` karena tidak ada perubahan frontend asset/CSS/JS build pipeline.

## Test Result
`php artisan test`: 146 passed / 686 assertions.

## Manual Check
- Users execute OK.
- User role assignments execute OK.
- User app accesses execute OK.
- Rollback users OK.
- Rollback role assignment OK.
- Rollback app access OK.
- Missing birth_date safe OK.
- No plaintext password OK.
- Guest/unauthorized rejected OK lewat existing Import Center tests.
- No 500 error OK lewat full test suite.

## Guardrails Confirmation
- Tidak menjalankan migrate:fresh.
- Tidak menjalankan migrate:reset.
- Tidak menjalankan migrate:rollback.
- Tidak drop database.
- Tidak menghapus data unsafe.
- Tidak execute import KP.
- Tidak mengubah database KP.
- Tidak menyentuh SAFA/KP/TU.
- Tidak membuat Core public.
- Tidak membuat SSO/bypass login.
- Tidak membuat cross-app user session.
- Tidak membuat token di URL.
- Tidak expose password/hash/token/secret.
- Tidak membuat role/app otomatis dari import.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.

## Risks / Notes
- Departments/study programs import execute belum dibuat.
- Leadership assignment import belum dibuat.
- API audit/rate limit per client belum dibuat.
- End-to-end integration consumer belum dibuat.
- Rollback app access memakai deactivate/restore snapshot, bukan hard delete, agar lebih aman untuk histori akses.

## Recommended Next Step
Rekomendasi tahap berikutnya:

- `CORE-API-3 API Audit & Rate Limit per Client`

Alasan: Core sekarang sudah bisa import identity dan access. Sebelum consumer app memakai API lebih intensif, audit dan rate limit per client akan memperkuat jejak akses internal.

Alternatif:

- `CORE-IMPORT-9 Departments/Study Programs/Leadership Import`
- `CORE-QA-2 End-to-End Regression`
