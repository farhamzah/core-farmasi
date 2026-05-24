# CORE-INTEGRATION-2C KP Staging Smoke Test Plan Report

## Scope
Tahap ini membuat staging smoke test plan untuk KP read-only HTTP adapter terhadap Core API. Ini bukan cutover, tidak mengaktifkan production, tidak mengganti auth KP, dan tidak menulis data ke Core.

## Previous Reports Reviewed
- CORE-INTEGRATION-2B KP Read-Only Adapter.
- CORE-INTEGRATION-2A App-Client Directory Endpoints.
- CORE-INTERNAL-API.
- KP `CORE-HTTP-ADAPTER-READONLY`.

## Files Changed
- `apps/kp-farmasi/app/Console/Commands/CoreFarmasiSmokeTestCommand.php`
- `apps/kp-farmasi/tests/Feature/CoreFarmasiClientTest.php`
- `apps/kp-farmasi/docs/CORE-HTTP-ADAPTER-STAGING-SMOKE-TEST.md`
- `apps/kp-farmasi/docs/CORE-HTTP-ADAPTER-READONLY.md`
- `apps/core-farmasi/docs/CORE-CONSUMER-INTEGRATION-PLAN.md`
- `apps/core-farmasi/docs/reports/CORE-INTEGRATION-2C-KP-STAGING-SMOKE-TEST-PLAN-REPORT.md`

## Smoke Test Checklist
Checklist mencakup:
- Preconditions: Core/KP staging running, app client `kp-farmasi` aktif, ability minimal tersedia, secret dikelola devops/admin.
- Env vars: `KP_CORE_HTTP_ENABLED=true` hanya staging, `KP_CORE_READ_MODE=legacy`, base URL, app code, client ID, client secret placeholder, timeout, SSL, dan fail silently.
- Test steps: health, directory users/students/lecturers/study programs, app access check, current leadership, invalid credential, missing ability, rate limit normal, unavailable behavior, Core API logs, KP logs, dan konfirmasi no DB changes.
- Expected results: read-only calls OK, no write-back, no auth replacement, no SSO, no token URL, no secret leakage, fail safe saat Core unreachable.
- Rollback/disable plan: set `KP_CORE_HTTP_ENABLED=false`, keep `KP_CORE_READ_MODE=legacy`, clear cache, no DB rollback needed.
- Go/no-go criteria: Go hanya jika endpoint read-only OK, secret aman, KP tidak rusak, dan tidak ada mutasi DB.

## Optional Command
Command dibuat: `php artisan kp:core-smoke-test`.

Behavior:
- Read-only only.
- Menggunakan `CoreFarmasiClient`.
- Jika adapter disabled atau env belum lengkap, command menampilkan status disabled dan exit sukses tanpa HTTP call.
- Jika enabled, command memanggil study programs, students, lecturers, current leadership, dan app access jika `--user-id` diberikan.
- Tidak mencetak secret.
- Tidak menulis database.
- Non-zero exit untuk failure saat service dikonfigurasi fail-fast.

## Security Confirmation
- Tidak membuat SSO.
- Tidak membuat auto-login.
- Tidak membuat write-back.
- Tidak cutover.
- Tidak menulis real secret.
- Tidak membuat token URL.
- Default legacy preserved.
- Tidak ada database changes.

## Commands Run
- `php artisan optimize:clear` di KP: OK.
- `php artisan test --filter=CoreFarmasiClientTest` di KP: OK, 9 passed / 35 assertions.
- `php artisan test` di KP: OK, 128 passed / 604 assertions.
- `php artisan test` di Core karena dokumentasi Core berubah: OK, 159 passed / 797 assertions.
- Migration tidak dijalankan.
- Real staging smoke command tidak dijalankan karena tahap ini hanya plan/skeleton dan tidak memakai real credential.

## Test Result
- KP: 128 passed / 604 assertions.
- Core: 159 passed / 797 assertions.

## Guardrails Confirmation
- Tidak menjalankan migrate:fresh.
- Tidak menjalankan migration.
- Tidak drop database.
- Tidak mengubah database KP/Core.
- Tidak execute import.
- Tidak cutover.
- Tidak mengganti auth KP.
- Tidak membuat SSO/bypass login.
- Tidak membuat token URL.
- Tidak menulis real secret.
- Tidak expose password/hash/token/secret.
- Tidak write-back.
- Default legacy tetap.
- Tidak menyentuh SAFA/TU.

## Risks / Notes
- Real staging credential belum dibuat/diuji.
- Smoke test real belum dijalankan.
- Production masih belum aktif.
- Perlu devops/admin untuk secret handling, issuance, rotate/revoke, dan validasi log.

## Recommended Next Step
Rekomendasi tahap berikutnya: CORE-INTEGRATION-2D KP Real Staging Smoke Test Execution.
