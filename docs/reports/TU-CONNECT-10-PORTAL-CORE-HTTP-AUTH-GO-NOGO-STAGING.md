# TU-CONNECT-10 Portal Core HTTP Auth Go/No-Go for Staging

Tanggal: 2026-05-24

## Scope

Evaluasi kesiapan portal Core HTTP auth untuk staging/local berdasarkan bukti implementasi, smoke test, app access test, dan login success QA.

Keputusan ini tidak mengaktifkan production cutover, tidak membuat SSO, tidak membuat auto-login lintas aplikasi, tidak membuat token URL, dan tidak mengganti admin auth TU.

## Evidence Reviewed

- `TU-CONNECT-8-PORTAL-LOGIN-CORE-HTTP-CLIENT-REPORT.md`
- `TU-CONNECT-9-PORTAL-LOGIN-LOCAL-STAGING-QA-REPORT.md`
- `TU-CONNECT-9B-PORTAL-LOGIN-SUCCESS-QA-REPORT.md`
- `CORE-TU-CONNECT-10-PREPARE-SAFE-PORTAL-LOGIN-TEST-ACCOUNT.md`

## Current Proven Capabilities

- Core HTTP password verification endpoint is ready.
- TU Core HTTP client can call the verification endpoint safely.
- `TU_PORTAL_AUTH_MODE=core_http` is available for staging/local use.
- Default TU portal auth remains disabled after rollback.
- User app access smoke for `user_id=3` returned has-access.
- HTTP-level login for `user_id=3` succeeded.
- Login success redirected to `/portal/pengajuan`.
- Logout cleared the TU portal session.
- Wrong password produced a generic failure.
- Session safety checks did not expose password, hash, token, client secret, or raw app-client credentials.
- Admin auth remained unaffected.
- Rollback to `TU_PORTAL_AUTH_MODE=disabled` was verified.
- TU automated tests passed after QA.

## Limitations

- Only dosen login has been proven with real Core HTTP password verification.
- Mahasiswa login has not yet been proven because a safe mahasiswa test account/password was not provided.
- App access missing negative scenario was skipped because no separate safe user without `tu-farmasi` access was provided.
- Browser visual QA was not executed; HTTP-level QA was executed.
- Staging UAT with tester sign-off has not been executed.
- Production cutover is not approved by this decision.

## Decision

GO for staging/local use of portal Core HTTP auth.

NO-GO for production cutover.

NO-GO for SSO, auto-login, token URL, or auth replacement.

Rationale:

- Core HTTP password endpoint is ready.
- TU Core HTTP client and portal auth service are wired.
- App access for `user_id=3` is true.
- Real HTTP-level login for `user_id=3` succeeded.
- Logout and wrong-password behavior are safe.
- No secret/password/hash/token leakage was observed.
- No write-back, migration, database change, or admin auth replacement occurred.
- TU tests passed.

## Required Staging Env

Use these values only in staging/local where approved:

```env
TU_PORTAL_AUTH_MODE=core_http
TU_CORE_HTTP_ENABLED=true
TU_CORE_READ_MODE=http-shadow
TU_CORE_BASE_URL=<staging-core-url>
TU_CORE_PROFILE_URL=<staging-core-profile-url>
TU_CORE_CLIENT_ID=<configured>
TU_CORE_CLIENT_SECRET=<configured-hidden>
TU_PORTAL_CORE_APP_CODE=tu-farmasi
```

Do not commit real credentials.

Do not enable `core_http` as the repository default.

## Rollback

Set:

```env
TU_PORTAL_AUTH_MODE=disabled
```

Then run:

```bash
php artisan optimize:clear
php artisan tu:portal-auth-status
php artisan tu:portal-login-preflight
```

Expected rollback state:

- portal auth mode: disabled
- portal login full readiness: not ready
- no production cutover

## Staging UAT Required Before Production

- Login dosen.
- Login mahasiswa.
- Wrong password.
- User without `tu-farmasi` app access.
- Inactive app access.
- Logout.
- Portal request submission.
- Portal attachment upload.
- Portal request tracking.
- Portal final archive download.
- Admin auth unaffected.
- No secret/path/private metadata leak.
- UAT tester sign-off.

## Security Confirmation

- no password storage: OK
- no token storage: OK
- no SSO: OK
- no auto-login: OK
- no token URL: OK
- no write-back: OK
- no auth replacement: OK
- no secret in docs: OK
- admin auth unaffected: OK
- default remains disabled: OK

## Commands Run

This report relies on the executed evidence from TU-CONNECT-9B:

- `php artisan optimize:clear`
- `php artisan tu:portal-login-preflight`
- `php artisan tu:core-smoke-test --user-id=3`
- HTTP-level login/logout checks against local TU server
- `php artisan test`
- `composer validate`

Additional validation for this Go/No-Go report was run in TU:

- `php artisan tu:portal-login-preflight`
- `php artisan test`
- `composer validate`

No password login was repeated for this decision report.

## Test Result

TU-CONNECT-9B evidence:

- `php artisan tu:core-smoke-test --user-id=3`: OK, has-access
- `php artisan test`: 268 passed / 1423 assertions
- `composer validate`: OK

Current report validation:

- `php artisan tu:portal-login-preflight`: OK, safe rollback state confirmed with `TU_PORTAL_AUTH_MODE=disabled` and status `NOT READY`.
- `php artisan test`: 268 passed / 1423 assertions.
- `composer validate`: OK, `./composer.json is valid`.

Core:

- `php artisan test`: not run because Core runtime/code was not changed; only this report was added.

## Guardrails Confirmation

- Tidak menjalankan migration.
- Tidak menjalankan migrate:fresh/reset/rollback.
- Tidak mengubah database TU/Core.
- Tidak cutover production.
- Tidak mengganti auth TU admin.
- Tidak membuat SSO.
- Tidak membuat auto-login.
- Tidak membuat token URL.
- Tidak menyimpan password/token.
- Tidak expose secret/password/hash/token.
- Tidak write-back.
- Tidak mengubah Core runtime/code.
- Tidak menyentuh KP/SAFA.

## Recommended Next Step

Recommended next step:

- TU-CONNECT-11 Mahasiswa Test User App Access + Login QA

Alternative staging path:

- TU-CONNECT-11 Staging Mode Enablement Runbook

Production remains blocked until staging UAT, mahasiswa login QA, negative app-access QA, and explicit approval are complete.
