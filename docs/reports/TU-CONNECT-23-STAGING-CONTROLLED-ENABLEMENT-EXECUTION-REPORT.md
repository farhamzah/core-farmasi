# TU-CONNECT-23 Staging Controlled Enablement Execution Report

Tanggal: 2026-05-24

## Scope

Tahap ini mencatat hasil controlled enablement Portal Core HTTP Auth pada environment lokal/staging. Mode `core_http` diaktifkan sementara untuk preflight/smoke, lalu di-rollback ke `disabled`.

Tahap ini bukan production cutover dan tidak mengubah runtime Core.

## Previous Context

- TU-CONNECT-22 allowed staging/local `core_http` with checklist.
- Production cutover remains NO-GO.
- Default remains disabled.
- App access `user_id=3` and `user_id=4` previously proven.
- Dosen and mahasiswa login previously proven, but fresh password runtime was not available in TU-CONNECT-23.

## Execution Summary

Initial mode:

- `TU_PORTAL_AUTH_MODE=disabled`
- `TU_CORE_READ_MODE=http-shadow`
- `TU_CORE_HTTP_ENABLED=true`

Temporary mode:

- `TU_PORTAL_AUTH_MODE=core_http`
- `TU_CORE_READ_MODE=http-shadow`
- `TU_CORE_HTTP_ENABLED=true`

Results:

- `php artisan optimize:clear`: OK.
- `php artisan tu:portal-login-preflight`: READY in temporary `core_http`.
- `php artisan tu:core-smoke-test --user-id=3`: OK, has-access.
- `php artisan tu:core-smoke-test --user-id=4`: OK, has-access.
- Wrong password generic failure: OK.
- Fresh successful login: skipped because passwords were not available to Codex runtime.
- Rollback to `TU_PORTAL_AUTH_MODE=disabled`: OK.
- Post-rollback preflight: safe default `NOT READY` because disabled.

## Security Confirmation

- No password in report.
- No password/hash/token/secret output.
- No SSO.
- No auto-login.
- No token URL.
- No write-back to Core.
- No admin auth replacement.
- No production cutover.
- No Core runtime/code change.

## Decision

Controlled enablement execution: PASS WITH LIMITATIONS.

Production cutover: NO.

## Files Created

- `apps/tu-farmasi/docs/TU-CONNECT-23-STAGING-CONTROLLED-ENABLEMENT-RESULT.md`
- `apps/core-farmasi/docs/reports/TU-CONNECT-23-STAGING-CONTROLLED-ENABLEMENT-EXECUTION-REPORT.md`

## Recommended Next Step

- Repeat successful login execution if safe test passwords are provided to runtime.
- Or proceed to a formal owner risk acceptance step before any broader staging operation.
- Production cutover remains blocked until final approval.

