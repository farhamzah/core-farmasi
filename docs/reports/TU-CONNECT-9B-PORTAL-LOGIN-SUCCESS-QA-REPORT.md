# TU-CONNECT-9B Portal Login Success QA Report

Tanggal: 2026-05-24

## Scope

QA local/staging untuk membuktikan portal login TU via Core HTTP client berhasil menggunakan akun test Core `user_id=3`.

Tahap ini bukan SSO, bukan auto-login lintas aplikasi, bukan token URL, bukan write-back ke Core, dan bukan production cutover.

## Previous Reports Reviewed

- `TU-CONNECT-9-PORTAL-LOGIN-LOCAL-STAGING-QA-REPORT.md`
- `CORE-TU-CONNECT-10-PREPARE-SAFE-PORTAL-LOGIN-TEST-ACCOUNT.md`

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
- Test password: available through safe local/staging channel

No password, hash, token, or client secret is included in this report.

## Preflight Result

Commands:

```bash
php artisan optimize:clear
php artisan tu:portal-login-preflight
php artisan tu:core-smoke-test --user-id=3
```

Result during temporary `core_http` mode:

- portal auth mode: `core_http`
- HTTP shadow readiness: ready
- HTTP app access readiness: ready
- HTTP password auth endpoint: ready
- HTTP portal login full readiness: ready
- can attempt Core login: yes
- user-level app access for `user_id=3`: OK, has-access
- status: READY

## Login Success Test

Core server was started temporarily on `127.0.0.1:8001`.

TU server was started temporarily on `127.0.0.1:8003`.

Browser automation:

- Status: NOT EXECUTED
- Reason: browser automation was not used for this pass.

HTTP-level login:

- Status: EXECUTED
- Route: `POST /portal/login`
- User: `user_id=3`
- User context: dosen
- Result: SUCCESS
- Redirect/final path after login: `/portal/pengajuan`
- Authenticated portal page `/portal/pengajuan`: accessible
- Password recorded: no

The HTTP-level test used a runtime-only password value and did not write it to a file, test, report, command output, or repository.

## Logout Result

Route:

```text
/portal/logout
```

Result:

- logout executed: OK
- portal identity session cleared: OK
- `/portal/pengajuan` after logout redirects to `/portal/login-required`: OK

## Negative Test Result

Wrong password:

- Status: EXECUTED
- Result: generic failure shown on `/portal/login`
- no detailed user existence/access reason exposed: OK
- no password/hash/token/secret output: OK

Missing app access:

- Status: SKIPPED
- Reason: no separate safe user without `tu-farmasi` access was provided for this pass.

Disabled mode / safe rollback:

- Status: EXECUTED
- Result: after rollback, `tu:portal-login-preflight` returns NOT READY because `TU_PORTAL_AUTH_MODE=disabled`
- full portal login disabled after rollback: OK

Admin auth unaffected:

- Status: EXECUTED
- `/admin/login` remained available: OK

## Session Safety Check

Rendered authenticated portal page was checked for sensitive terms and did not expose:

- password
- client secret
- generic secret marker
- remember token
- API token
- hash marker

Expected safe identity/session payload may contain:

- core user id
- name
- email
- user type
- lecturer/student/employee safe profile
- app access roles/permissions

Forbidden session/report payload:

- password
- token
- hash
- Core client secret
- raw app-client credentials

Existing automated tests also cover safe session payload mapping.

## Rollback Status

After QA, TU `.env` was restored to:

- `TU_PORTAL_AUTH_MODE=disabled`
- `TU_CORE_READ_MODE=http-shadow`
- `TU_CORE_HTTP_ENABLED=true`

Post-rollback commands:

```bash
php artisan optimize:clear
php artisan tu:portal-login-preflight
```

Post-rollback result:

- portal auth mode: disabled
- HTTP shadow readiness: ready
- HTTP app access readiness: ready
- HTTP password auth endpoint: ready
- HTTP portal login full readiness: not ready
- reason: `portal_auth_mode_not_core_http`
- status: NOT READY

This is expected and safe.

## Security Confirmation

- no password in report: OK
- no password/hash/token in session/report: OK
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
- local TU server: `php artisan serve --host=127.0.0.1 --port=8003`
- HTTP-level login/logout checks against local TU server
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

TU-CONNECT-10 Portal Core HTTP Auth Go/No-Go for Staging.
