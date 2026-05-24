# CORE-INTEGRATION-3B TU Staging Smoke Test Plan Report

## Scope
Tahap ini membuat rencana staging smoke test untuk adapter HTTP read-only TU ke Core API. Scope ini bukan cutover, bukan SSO, bukan pengganti auth TU, dan tidak mengaktifkan adapter ke production forms.

## Previous Reports Reviewed
- CORE-INTEGRATION-3 TU Read-Only Adapter Report.
- CORE-INTEGRATION-2A App-Client Directory Endpoints Report.
- CORE-INTERNAL-API.
- TU CORE-HTTP-ADAPTER-READONLY.

## Files Changed
- `apps/tu-farmasi/app/Console/Commands/CoreFarmasiSmokeTestCommand.php`
- `apps/tu-farmasi/tests/Feature/CoreFarmasiClientTest.php`
- `apps/tu-farmasi/docs/CORE-HTTP-ADAPTER-STAGING-SMOKE-TEST.md`
- `apps/tu-farmasi/docs/CORE-HTTP-ADAPTER-READONLY.md`
- `apps/core-farmasi/docs/CORE-CONSUMER-INTEGRATION-PLAN.md`
- `apps/core-farmasi/docs/reports/CORE-INTEGRATION-3B-TU-STAGING-SMOKE-TEST-PLAN-REPORT.md`

## Smoke Test Checklist
Checklist staging dibuat di `apps/tu-farmasi/docs/CORE-HTTP-ADAPTER-STAGING-SMOKE-TEST.md`.

- Prerequisites: Core staging dan TU staging harus berjalan, app client `tu-farmasi` sudah dibuat di Core, abilities minimal sudah diberikan, credential dikelola oleh admin/devops, dan staging snapshot/backup disiapkan bila perlu.
- Env vars: checklist memakai placeholder `TU_CORE_HTTP_ENABLED`, `TU_CORE_READ_MODE`, `TU_CORE_BASE_URL`, `TU_CORE_APP_CODE`, `TU_CORE_CLIENT_ID`, `TU_CORE_CLIENT_SECRET`, timeout, SSL verify, dan fail silently. Tidak ada credential asli ditulis di repo.
- Test steps: health Core, directory users/students/lecturers/employees, departments, study programs, `searchPeople`, leadership, app access check, invalid credential 401, missing ability 403, rate limit, Core unavailable behavior, Core API logs, TU logs, dan konfirmasi tidak ada perubahan DB TU.
- Expected results: semua call read-only aman, person picker dapat diuji tanpa write, leadership lookup aman, tidak ada write-back, tidak ada auth replacement, tidak ada SSO, tidak ada secret di logs, dan TU tetap berfungsi jika Core unreachable.
- Rollback/disable plan: set `TU_CORE_HTTP_ENABLED=false`, clear config/cache, pertahankan `TU_CORE_READ_MODE=disabled`, tidak perlu DB rollback karena tidak ada DB change.
- Go/no-go criteria: Go hanya jika endpoint read-only OK, tidak ada secret leak, dan TU tidak rusak. No-go jika auth/ability gagal, Core unavailable merusak TU, secret muncul di log, atau ada mutasi DB tidak terduga.

## Optional Command
Command dibuat:

```bash
php artisan tu:core-smoke-test
```

Behavior:
- Read-only only.
- Menggunakan `CoreFarmasiClient`.
- Jika `TU_CORE_HTTP_ENABLED=false`, command berhenti aman tanpa request HTTP.
- Memanggil departments, study programs, students, lecturers, employees, `searchPeople`, leadership, dan optional app access check.
- Tidak mencetak secret.
- Tidak menulis database.
- Tidak mengubah auth.
- Exit code sukses untuk disabled/success path, non-zero untuk kegagalan smoke test.

## Security Confirmation
- No SSO.
- No auto-login.
- No write-back.
- No cutover.
- No real secret.
- No token URL.
- Default disabled preserved.
- No database changes.
- Not wired to production forms.
- No auth replacement.
- No Core public exposure.

## Commands Run
- `cd apps/tu-farmasi`
- `php artisan optimize:clear` - OK.
- `php artisan list tu` - OK, `tu:core-smoke-test` registered.
- `php artisan test --filter=CoreFarmasiClientTest` - OK, 10 passed / 45 assertions.
- `php artisan test` - OK, 240 passed / 1236 assertions.
- `cd ../core-farmasi`
- `php artisan test` - OK, 159 passed / 797 assertions.
- Migrations not run.
- npm build not run because no frontend assets changed.

## Test Result
- TU targeted adapter/smoke tests: 10 passed / 45 assertions.
- TU full test suite: 240 passed / 1236 assertions.
- Core full test suite: 159 passed / 797 assertions.

## Guardrails Confirmation
- Tidak menjalankan migrate:fresh.
- Tidak menjalankan migration.
- Tidak drop database.
- Tidak mengubah database TU/Core.
- Tidak execute import.
- Tidak cutover.
- Tidak mengganti auth TU.
- Tidak membuat SSO/bypass login.
- Tidak membuat token URL.
- Tidak menulis real secret.
- Tidak expose password/hash/token/secret.
- Tidak write-back.
- Default disabled tetap.
- Tidak menyentuh KP/SAFA.
- Tidak mengaktifkan TU_CORE_HTTP_ENABLED di repo/default.
- Tidak mengubah default TU read mode dari disabled.
- Tidak membuat Core public.

## Risks / Notes
- Real staging credential belum dibuat/diuji.
- Smoke test real ke Core staging belum dijalankan.
- Production masih belum aktif.
- Secret handling masih perlu dilakukan oleh admin/devops melalui environment staging, bukan repo.
- Person picker belum wired ke production forms.
- Perlu verifikasi Core API request logs setelah smoke test real.

## Recommended Next Step
Rekomendasi tahap berikutnya:

- CORE-INTEGRATION-4 Real Staging Smoke Test Execution for KP/TU.
- Alternatif: CORE-UI-5 Leadership Conditional Select UX.
- Alternatif: CORE-QA-3 Pre-Staging Final Checklist.
