# TU-CONNECT-25 Production Cutover Planning Report

## Scope

TU-CONNECT-25 creates production cutover planning documents for TU Portal Core HTTP Auth. This stage does not execute production cutover and does not change runtime code.

## Current Status

- Technical UAT: `PASS WITH LIMITATIONS`
- Human UAT: `PENDING`
- Owner risk acceptance: `PENDING`
- Staging/local controlled use: `GO WITH LIMITATIONS`
- Production cutover: `NO-GO`
- Default production auth: `TU_PORTAL_AUTH_MODE=disabled`
- SSO/auto-login/token URL: `NO-GO`
- Write-back to Core: none

## Why Production Remains NO-GO

Production remains `NO-GO` until owner acceptance or human UAT sign-off is complete because:

- latest fresh successful login execution was skipped in Codex runtime due unavailable password.
- final human UAT is still pending.
- browser visual QA remains limited.
- production env is not yet validated.
- production rollback has not been executed.
- user-without-app-access negative scenario remains pending if no safe user exists.

## Documents Created

- `apps/tu-farmasi/docs/TU-CONNECT-25-PRODUCTION-CUTOVER-RUNBOOK.md`
- `apps/tu-farmasi/docs/TU-CONNECT-25-PRODUCTION-GO-NOGO-CHECKLIST.md`
- `apps/tu-farmasi/docs/TU-CONNECT-25-PRODUCTION-ROLLBACK-RUNBOOK.md`
- `apps/tu-farmasi/docs/TU-CONNECT-25-PRODUCTION-POST-CUTOVER-VERIFICATION.md`
- `apps/core-farmasi/docs/reports/TU-CONNECT-25-PRODUCTION-CUTOVER-PLANNING-REPORT.md`

## Go/No-Go Gates

Required before production:

- owner risk acceptance signed or human UAT passed.
- production env reviewed.
- Core endpoint reachable from TU production.
- Core app client secret stored securely.
- production test dosen and mahasiswa app access confirmed.
- rollback owner and support contact assigned.
- post-cutover verification checklist ready.
- no secret/token/password/hash exposure.
- admin auth unaffected.

## Rollback

Rollback is env-only for portal auth:

```env
TU_PORTAL_AUTH_MODE=disabled
```

Then run:

```bash
php artisan optimize:clear
php artisan tu:portal-auth-status
php artisan tu:portal-login-preflight
```

Expected result: portal full login is disabled and admin auth remains unaffected.

## Guardrails

- No migration.
- No database mutation.
- No Core runtime/code change.
- No production cutover.
- No SSO.
- No auto-login.
- No token URL.
- No write-back.
- No secret in report.
- No KP/SAFA changes.

## Recommended Next Step

If owner signs acceptance or human UAT passes: TU-CONNECT-26 Production Cutover Execution.

If not: wait for sign-off or complete human UAT first.
