# CORE-QA-2 Final Regression & Release Readiness Report

## Scope
Tahap ini melakukan final regression, security readiness, import readiness, API readiness, dokumentasi final, dan release readiness checklist untuk `apps/core-farmasi`.

Tahap ini bukan fitur besar baru. Tidak ada endpoint API baru, import type baru, SSO, auto-login, cross-app session, token URL, atau integrasi aplikasi lain.

## Previous Reports Reviewed
- CORE-QA-1 Stabilization, Security Review & Documentation Report
- CORE-IMPORT-8 Users & App Access Execute/Rollback Report
- CORE-API-4 Log Retention & Pruning Report
- CORE-API-3 API Audit & Rate Limit per Client Report
- CORE-API-2 App Client Credentials & Token Rotation Report
- CORE-API-1 Internal API Safety Baseline Report
- CORE-ACCESS-2 Internal App Launcher Report
- CORE-DQ-1 Data Quality Dashboard Report

## Route & Auth Regression
`php artisan route:list` berhasil dan menampilkan 72 routes.

Hasil review:
- `/admin/login` tetap login resmi Core admin.
- `/` hanya redirect ke `/admin`.
- Admin routes berada di Filament admin panel.
- Admin panel memakai `Authenticate` dan `EnsureCorePasswordChanged`.
- `User::canAccessPanel()` tetap mensyaratkan user aktif dan role `super-admin` atau `admin-core` aktif.
- API user-token routes tetap memakai `auth.api`.
- API internal app-client routes tetap memakai `auth.core-api-client` dengan ability.
- Health check tetap public minimal.
- Tidak ada SSO route.
- Tidak ada token URL route.
- Tidak ada public reset password route.
- Tidak ada route import execute/rollback public.
- Tidak ada suspicious public route baru.

## Filament Regression
Resource/page yang dicek melalui route review, code review, dan coverage test:
- Users
- Roles
- Students
- Lecturers
- Employees
- Departments
- Study Programs
- Leadership Assignments
- Core Applications
- Core Application Roles
- User App Accesses
- Core API Clients
- Core API Request Logs
- Import Center
- Data Quality Dashboard
- App Launcher
- Change Password

Hasil:
- Protected: OK
- Guest diarahkan ke `/admin/login`: OK
- Unauthorized user ditolak: OK
- Core API Request Logs read-only: OK
- API client secret tidak disimpan plaintext: OK
- Role access tidak dilonggarkan: OK
- Inactive user blocked melalui `canAccessPanel`: OK

## Import Lifecycle Regression
Import lifecycle yang sudah tersedia:
- Template download.
- Upload private/local.
- Heading validation.
- Row validation/conflict detection.
- Admin decision UI.
- Execute import students/lecturers/employees.
- Rollback students/lecturers/employees.
- Execute users/global role assignments/user app accesses.
- Rollback users/global role assignments/user app accesses.

Safety check:
- Password column rejected/ignored.
- Plaintext password tidak disimpan/ditampilkan.
- Initial password untuk user baru dari import memakai `birth_date`, hashed, dan `must_change_password=true`.
- Missing birth_date tidak menghasilkan fallback password lemah.
- Invalid/skip/pending rows tidak dieksekusi.
- Profile import tidak memproses app role columns sebagai app access.
- App/global roles dan applications tidak auto-created dari import.
- Rollback memakai metadata batch/record dan manual review saat tidak aman.

Test coverage import tetap lulus dalam suite penuh.

## API Lifecycle Regression
API lifecycle yang dicek:
- Safe response sanitizer.
- User-token API.
- App client credentials.
- Secret hash only.
- Rotate/revoke client.
- App client middleware.
- Ability check.
- Rate limit per client/app code.
- API request logs.
- Log pruning command.
- Internal API docs.

Hasil:
- Password/hash/token/secret tidak diexpose.
- `birth_date` tidak diexpose default.
- Client secret tidak diterima dari URL.
- Invalid/revoked/inactive client ditolak.
- Internal endpoint protected.
- API request log tidak menyimpan body penuh, full headers, authorization header, atau secret.
- Pruning dry-run berjalan aman.

## Security Review
- no SSO: OK
- no auto-login: OK
- no cross-app user session: OK
- no token URL: OK
- no public Core: OK
- no password/hash/token/secret exposure: OK
- no `birth_date` default API exposure: OK
- no secret in logs: OK
- no request body penuh in logs: OK
- role access not loosened: OK
- API protected: OK
- import private: OK
- Core not public visible / not SAFA public portal: OK

## Documentation Updates
Dokumen dibuat:
- `docs/CORE-RELEASE-READINESS-CHECKLIST.md`

Dokumen diperbarui:
- `README.md`
- `docs/CORE-ARCHITECTURE-SUMMARY.md`

`docs/CORE-INTERNAL-API.md` sudah relevan dari CORE-API-4 dan tidak memerlukan perubahan tambahan pada tahap ini.

Checklist release mencakup:
- environment prerequisites
- database readiness
- migration status check
- seed safety
- admin login check
- security checks
- import checks
- rollback checks
- API client checks
- API pruning SOP
- backup recommendation
- no SSO/no public Core note
- next integration checklist

Tidak ada credential, secret, token, atau password yang ditulis di dokumentasi.

## Commands Run
- `php artisan optimize:clear` - OK
- `php artisan route:list` - OK, 72 routes
- `php artisan core:prune-api-request-logs --dry-run` - OK
  - total logs: 0
  - eligible logs: 0
  - deleted logs: 0
- `php artisan test` - OK, 156 passed / 748 assertions
- `php artisan migrate` - tidak dijalankan karena tidak ada migration baru
- `npm run build` - tidak dijalankan karena tidak ada perubahan frontend asset/CSS/JS

## Test Result
`php artisan test` berhasil:

- 156 tests passed
- 748 assertions

Regression area yang tercakup:
- admin access
- app registry/app role catalog
- API auth
- API client credential
- API audit/rate limit/pruning
- app launcher
- identity/change password
- data quality dashboard
- import validation/decision/execute/rollback
- internal API safe endpoints
- employee resource
- KP import guardrails
- leadership assignments

## Release Readiness Verdict
- Ready for local/staging integration: yes.
- Ready for production: not yet as a process verdict.

Blockers:
- Tidak ada blocking bug dari test/regression.
- Production readiness masih membutuhkan operational checklist: backup/restore validation, migration plan, environment hardening, secret management, API client issuance SOP, pruning schedule, and staging smoke test with real consumer app.

Recommended pre-production steps:
- Jalankan staging deployment dengan database clone/sanitized data.
- Verifikasi `/admin/login`, admin resources, import dry-run/execute rollback, API client create/rotate/revoke, internal API 401/403/429 behavior.
- Validasi backup dan restore.
- Tetapkan SOP API client secret storage dan rotation.
- Tetapkan jadwal pruning API logs.
- Mulai consumer app integration read-only terlebih dahulu.

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
- Tidak menyimpan secret di logs.
- Tidak menyimpan request body penuh.
- Tidak membuat public API sensitive.
- Tidak membuat import execute baru.
- Tidak bulk reset password.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.

## Risks / Notes
- Production deployment belum dilakukan pada tahap ini.
- Scheduler pruning belum diaktifkan otomatis; command tersedia untuk SOP operasional.
- Departments/study programs/leadership import execute belum dibuat.
- Consumer app integration belum dilakukan.
- Monitoring/alerting API usage belum dibuat.
- Object-level authorization consumer app tetap harus dijaga di masing-masing aplikasi.

## Recommended Next Step
Rekomendasi tahap berikutnya: **CORE-INTEGRATION-1 Connect KP/TU read-only to Core API**.

Alasannya, Core sudah siap untuk local/staging integration. Mulai dari read-only consumer integration adalah jalur paling aman sebelum membuka write/integration flow yang lebih besar.
