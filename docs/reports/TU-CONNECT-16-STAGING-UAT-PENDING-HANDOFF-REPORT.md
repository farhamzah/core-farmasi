# TU-CONNECT-16 Staging UAT Pending Handoff Report

Tanggal: 2026-05-24

## Scope

Tahap ini merangkum status integrasi login portal TU via Core HTTP. Integrasi sudah terbukti di level HTTP/local-staging untuk dosen dan mahasiswa, tetapi staging UAT oleh tester belum dijalankan dan production cutover belum boleh dilakukan.

Dokumen ini adalah handoff/pause report. Tidak ada perubahan runtime, tidak ada cutover, dan tidak ada perubahan database.

## Evidence Summary

- Dosen login Core HTTP proven: yes, melalui user test dosen.
- Mahasiswa login Core HTTP proven: yes, melalui user test mahasiswa.
- App access dosen/mahasiswa: has-access untuk `tu-farmasi`.
- Wrong password generic failure: OK.
- Logout/session safety: OK.
- No password/hash/token/session leak: OK.
- TU tests terakhir yang tercatat: 268 passed / 1423 assertions.
- Core tests terakhir yang tercatat: 206 passed / 998 assertions.

## Current Mode

- Default `TU_PORTAL_AUTH_MODE=disabled`.
- Staging mode yang dapat dipakai sementara: `core_http`.
- Production cutover: no.

## What Is Ready

- Core endpoint password verification.
- TU Core HTTP client `verifyPortalPassword`.
- `CoreHttpPortalAuthService`.
- Portal login controller/session integration.
- App access check `tu-farmasi`.
- Dosen HTTP-level login proof.
- Mahasiswa HTTP-level login proof.
- UAT checklist package.

## What Is Not Ready

- Staging tester UAT not executed.
- Staging sign-off not available.
- Production cutover not approved.
- Browser visual QA not fully executed.
- User without app access negative test may still be skipped if no safe user is available.

## Required Before Production Cutover

- Execute TU-CONNECT-14 UAT checklist.
- Fill TU-CONNECT-14 result form.
- Sign off TU-CONNECT-14 sign-off form.
- Confirm no critical bug.
- Confirm rollback plan.
- Confirm production environment and secrets outside repository.
- Confirm no password/token/secret exposure.
- Confirm admin auth unaffected.

## Recommended Next Action

Run staging UAT with tester, record results in TU-CONNECT-17, and only after pass/sign-off prepare production cutover planning.

## Guardrails

- No SSO.
- No auto-login.
- No token URL.
- No write-back.
- No auth replacement.
- No production cutover.
- Default remains disabled.

## Validation Result

Validated from `apps/tu-farmasi`:

- `php artisan tu:portal-login-preflight`: OK, safe default `NOT READY` because `TU_PORTAL_AUTH_MODE=disabled`; HTTP shadow, app access, and password endpoint readiness are available.
- `php artisan tu:core-smoke-test --user-id=3`: OK, `has-access`.
- `php artisan tu:core-smoke-test --user-id=4`: OK, `has-access`.
- `composer validate`: OK.
- `php artisan test`: 268 passed / 1423 assertions.

Core test was not run in this phase because only docs/report files were created and Core runtime/code was not changed.

## Commands To Re-Validate Handoff

Recommended TU-side validation:

```bash
php artisan test
composer validate
php artisan tu:portal-login-preflight
php artisan tu:core-smoke-test --user-id=3
php artisan tu:core-smoke-test --user-id=4
```

Core tests are not required for this docs-only handoff unless Core runtime/code changes.
