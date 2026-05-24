# TU-CONNECT-9 Portal Login Local/Staging Manual QA Report

Tanggal: 2026-05-24

## Scope

QA local/staging untuk portal login TU via Core HTTP client. Tahap ini memvalidasi readiness `core_http`, app access user test, endpoint password verify readiness, failure handling generik, rollback mode aman, dan guardrail keamanan.

Tahap ini bukan SSO, bukan auto-login lintas aplikasi, bukan token URL, dan bukan production cutover.

## Previous Report Reviewed

- `TU-CONNECT-8-PORTAL-LOGIN-CORE-HTTP-CLIENT-REPORT.md`

## Environment Mode Used for QA

Initial local TU env values:

- `TU_PORTAL_AUTH_MODE=disabled`
- `TU_CORE_READ_MODE=http-shadow`
- `TU_CORE_HTTP_ENABLED=true`

Temporary QA mode:

- `TU_PORTAL_AUTH_MODE=core_http`
- `TU_CORE_READ_MODE=http-shadow`
- `TU_CORE_HTTP_ENABLED=true`

Credential status:

- Core base URL: configured
- Core client id: configured
- Core client secret: configured but hidden
- Test password: not available in safe local env keys checked

No secret or password is included in this report.

## Preflight Result

Command:

```bash
php artisan optimize:clear
php artisan tu:portal-login-preflight
```

Result during temporary `core_http` mode:

- portal auth mode: `core_http`
- HTTP shadow readiness: ready
- HTTP app access readiness: ready
- HTTP password auth endpoint: ready
- HTTP portal login full readiness: ready
- can attempt Core login: yes
- status: READY
- DB Core direct query: no

## Smoke Test Result

Core server was started temporarily on `127.0.0.1:8001` and stopped after QA.

Command:

```bash
php artisan tu:core-smoke-test --user-id=3
```

Result:

- basic HTTP smoke: OK
- app access smoke: OK
- `user_id=3`: has-access
- no secret output: OK
- no password/hash/token output: OK

## Login Test Result

Browser/manual login:

- Status: NOT EXECUTED
- Reason: safe password for user test was not available in local/staging environment.

HTTP-level success login:

- Status: NOT EXECUTED
- Reason: safe password for user test was not available.

User:

- `user_id=3`
- context: dosen
- app access: has-access

Password is not recorded.

## Negative Test Result

HTTP-level generic failure was executed with a dummy invalid login/password through `CoreHttpPortalAuthService`.

Result:

- invalid login/password returns `null`: OK
- failure remains generic: OK
- no password is written to report/session/database: OK

Skipped negative checks:

- wrong password for real user: skipped because real/safe test password unavailable
- user without app access: skipped because no safe user id was provided for this scenario
- inactive app access: skipped because no safe user id was provided for this scenario

Existing automated tests cover:

- disabled config does not call HTTP
- Core 401/403/422/429/500 handled safely
- controller failure message is generic
- logout clears portal identity session
- admin auth remains unaffected

## Session Safety Check

Successful real session was not created because real password login was not executed.

Automated tests confirm safe session payload on mocked Core success:

- allowed: core user id, name, email, user type, lecturer/student/employee safe profile, roles, app access
- not allowed: password, token, hash, app client secret

## Rollback Status

After QA, TU `.env` was restored to:

- `TU_PORTAL_AUTH_MODE=disabled`
- `TU_CORE_READ_MODE=http-shadow`
- `TU_CORE_HTTP_ENABLED=true`

Post-rollback commands:

```bash
php artisan optimize:clear
php artisan tu:portal-auth-status
php artisan tu:portal-login-preflight
```

Post-rollback result:

- portal auth mode: disabled
- portal identity resolver: disabled
- HTTP shadow readiness: ready
- HTTP app access readiness: ready
- HTTP password auth endpoint: ready
- full portal login: not ready because portal mode is disabled
- status: NOT READY

This is expected and safe.

## Security Confirmation

- no password in report: OK
- no password/hash/token in report: OK
- no secret output: OK
- no write-back: OK
- no SSO: OK
- no auto-login: OK
- no token URL: OK
- admin auth unaffected: OK
- no production cutover: OK
- no Core runtime/code changes: OK
- no KP/SAFA changes: OK

## Commands Run

TU:

- `php artisan optimize:clear`
- `php artisan tu:portal-login-preflight`
- `php artisan tu:core-smoke-test --user-id=3`
- HTTP-level negative attempt through `php artisan tinker --execute`
- `php artisan tu:portal-auth-status`
- `php artisan test`
- `composer validate`

Core:

- local Core server started temporarily with `php artisan serve --host=127.0.0.1 --port=8001`
- local Core server stopped after QA

## Test Result

TU:

- `php artisan tu:core-smoke-test --user-id=3`: OK, has-access
- `php artisan test`: 268 passed / 1423 assertions
- `composer validate`: OK, `./composer.json is valid`

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

Prepare safe test password/account and repeat TU-CONNECT-9 for real login success verification.

If safe password is provided and login succeeds:

- TU-CONNECT-10 Portal Core HTTP Auth Go/No-Go for Staging
