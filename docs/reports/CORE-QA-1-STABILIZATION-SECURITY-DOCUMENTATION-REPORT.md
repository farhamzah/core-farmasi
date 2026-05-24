# CORE-QA-1 Stabilization, Security Review & Documentation Report

## Scope
Tahap ini melakukan stabilisasi, security review, route/resource review, dokumentasi konsolidasi, dan regression check untuk `apps/core-farmasi`.

Tahap ini bukan fitur besar baru. Tidak membuat SSO, auto-login, public API sensitif, import type baru, import execute baru, atau integrasi langsung ke KP/TU/SAFA.

## Previous Reports Reviewed
- CORE-API-2 App Client Credentials & Token Rotation Report
- CORE-API-1 Internal API Safety Baseline Report
- CORE-ACCESS-2 Internal App Launcher Report
- CORE-DQ-1 Data Quality Dashboard Report
- CORE-IMPORT-6 Rollback / Undo Import Safety Report
- CORE-ACCESS-1 Dynamic App Registry & App Role Catalog Report
- CORE-ORG-1 Leadership Assignments Skeleton Report

## Route Review
Command:
- `php artisan route:list`

Result:
- OK, 71 routes shown.

Review:
- `/admin/login` tetap login resmi Filament admin.
- `/admin` dan resource/page admin berada di panel Filament protected.
- Tidak ada route SSO.
- Tidak ada route token URL.
- Tidak ada public reset password route.
- Import Center, execute, dan rollback berada di Filament admin protected page/action, bukan public route.
- API health tetap public low-risk.
- API user-token endpoints tetap protected by `auth.api`.
- API internal app-to-app endpoints protected by `auth.core-api-client` with ability:
  - `read:app-access`
  - `read:leadership`

Suspicious routes:
- Tidak ada route mencurigakan yang perlu hardening pada tahap ini.

## Filament/Admin Security Review
Dicek:
- `AdminPanelProvider`
- `User::canAccessPanel()`
- `EnsureCorePasswordChanged`
- Users
- Students
- Lecturers
- Employees
- Departments
- Study Programs
- Leadership Assignments
- Core Applications
- Core Application Roles
- User App Accesses
- API Clients
- Import Center
- Data Quality Dashboard
- App Launcher

Hasil:
- Admin panel memakai Filament auth middleware.
- `canAccessPanel()` tetap mensyaratkan user aktif dan role `super-admin` atau `admin-core`.
- Must-change-password middleware tetap aktif untuk admin panel.
- Resource/page tidak dibuat public.
- Role access tidak dilonggarkan.
- API client secret hash tidak tampil di table/form.
- API client secret plaintext hanya ditampilkan sekali saat create/rotate dan tidak persisted.

## Sensitive Field Review
Dicek:
- `password`
- password hash
- `api_token`
- API client secret
- `secret_hash`
- `remember_token`
- `birth_date`

Hasil:
- API safe response tidak mengekspos password/hash/token/secret.
- `birth_date` tidak diexpose default di API.
- `CoreApiClient::$hidden` menyembunyikan `secret_hash`.
- Import preview/validation menolak/mengabaikan kolom password.
- Import execution memakai birth date untuk hash temporary password tanpa menampilkan plaintext.
- UserActivityLog untuk password/API client tidak menyimpan secret/password plaintext.
- Dokumentasi tidak berisi credential real.

Catatan:
- `birth_date` tetap tampil di admin resource Student/Lecturer/Employee karena itu data master internal untuk admin, bukan API public/default exposure.

## API Safety Review
Dicek:
- `config/core_api.php`
- `CoreApiResponseSanitizer`
- `CoreApiAccessService`
- `CoreApiClientCredentialService`
- `AuthenticateApiToken`
- `AuthenticateCoreApiClient`
- `routes/api.php`
- `docs/CORE-INTERNAL-API.md`

Hasil:
- Internal app-to-app endpoints memakai app client headers.
- Missing/invalid client ditolak.
- Revoked/inactive client ditolak.
- App code mismatch ditolak.
- Ability mismatch ditolak.
- Query string `client_id`/`client_secret` ditolak.
- Secret disimpan hashed.
- Rotation dan revocation tersedia.
- Safe response tidak mengandung password/hash/token/birth_date.
- `docs/CORE-INTERNAL-API.md` dikoreksi agar app client credentials tidak lagi tercatat sebagai future work.

## Import Safety Review
Dicek:
- `CoreImportPreviewService`
- `CoreImportValidationService`
- `CoreImportExecutionService`
- `CoreImportRollbackService`
- `CoreImportCenter`
- `CoreImportBatch`
- `CoreImportRecord`

Hasil:
- Upload disimpan private/local.
- Tidak ada public file URL.
- Password column rejected/ignored.
- No plaintext password.
- Execute hanya setelah validation dan admin decision.
- Invalid/skip tidak dieksekusi.
- App role columns pada profile import tidak diproses sebagai app access.
- Execute memakai per-row transaction.
- Rollback memakai metadata execution, soft delete, dan manual review saat tidak aman.
- Rollback tidak hard delete unsafe data.

## App Access & Role Review
Dicek:
- `CoreApplication`
- `CoreApplicationRole`
- `UserAppAccess`
- `CoreApplicationSeeder`
- Resources terkait

Hasil:
- `core-farmasi` default `is_public_visible=false`.
- Global role dan app role tetap dipisah.
- Role aplikasi dynamic/configurable.
- App baru bisa dibuat.
- Role app baru bisa dibuat.
- User app access bisa assign role aplikasi baru.
- App launcher hanya menampilkan app dengan active access dan active application.
- Tidak ada SSO/auto-login dari launcher.

## Leadership Review
Dicek:
- `LeadershipAssignment`
- `CoreLeadershipResolver`
- `LeadershipAssignmentResource`

Hasil:
- Dekan/Kaprodi diambil dari active leadership assignment.
- Role login `dekan` tidak digunakan sebagai sumber jabatan resmi.
- Resolver aman saat data kosong dan return null.
- Multiple active assignment ditangani dengan latest start date/latest id.
- Data Quality Dashboard menandai kondisi multiple/expired/invalid person.
- `study_programs.head_lecturer_id` tetap tidak dihapus.

## Documentation Updates
Updated:
- `README.md`
- `docs/CORE-INTERNAL-API.md`

Created:
- `docs/CORE-ARCHITECTURE-SUMMARY.md`
- `docs/reports/CORE-QA-1-STABILIZATION-SECURITY-DOCUMENTATION-REPORT.md`

README sekarang menjelaskan:
- fungsi Core
- status modul
- security baseline
- internal API
- import flow
- non-goals
- recommended next steps

Architecture summary menjelaskan:
- Core as Master Data / Identity / Access / Import / API Center
- module overview
- data model overview
- auth & password policy
- app-specific roles
- leadership assignments
- import lifecycle
- rollback lifecycle
- internal API
- app client credential
- guardrails and non-goals

## Bugfixes / Minor Changes
- Dokumentasi `docs/CORE-INTERNAL-API.md` diperbarui: app client credentials/token rotation tidak lagi disebut sebagai future work karena sudah dibuat di CORE-API-2.
- README diganti dari boilerplate Laravel menjadi dokumentasi status Core Farmasi.
- Tidak ada perubahan schema, route, fitur besar, atau data master.

## Commands Run
- `php artisan optimize:clear` - OK.
- `php artisan route:list` - OK, 71 routes shown.
- `php artisan test` - percobaan pertama timeout di 120 detik tanpa hasil final.
- `php artisan test` - OK setelah timeout diperpanjang, 137 passed / 610 assertions.

Tidak menjalankan `php artisan migrate` karena tidak ada migration baru.
Tidak menjalankan `npm run build` karena tidak ada perubahan frontend asset/CSS/JS build pipeline.

## Test Result
`php artisan test`: 137 passed / 610 assertions.

## Guardrails Confirmation
- Tidak menjalankan migrate:fresh.
- Tidak menjalankan migrate:reset.
- Tidak menjalankan migrate:rollback.
- Tidak drop database.
- Tidak menghapus data.
- Tidak mengubah data master otomatis.
- Tidak execute import KP.
- Tidak mengubah database KP.
- Tidak menyentuh SAFA/KP/TU.
- Tidak membuat Core public.
- Tidak membuat SSO/bypass login.
- Tidak membuat cross-app user session.
- Tidak membuat token di URL.
- Tidak expose password/hash/token/secret.
- Tidak expose birth_date default di API.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.

## Risks / Notes
- API audit request detail belum diaktifkan untuk endpoint sensitif.
- Rate limit masih global baseline, belum per client.
- Consumer apps belum dikonfigurasi memakai app client credentials.
- SOP operasional untuk secret rotation/revocation masih perlu dibuat.
- Import users/app access belum dibuat.
- Import leadership assignment belum dibuat.
- Data Quality masih read-only dan belum punya export.

## Recommended Next Step
Rekomendasi tahap berikutnya: `CORE-IMPORT-7 Users & App Access Import` jika prioritas owner adalah mempercepat assignment akses aplikasi dari Excel.

Alternatif berikutnya:
- `CORE-API-3 API Audit & Rate Limit per Client` jika API akan segera dipakai app consumer.
- Integrasi consumer app pertama secara aman memakai app client credentials.
