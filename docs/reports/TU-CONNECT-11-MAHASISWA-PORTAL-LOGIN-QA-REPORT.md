# TU-CONNECT-11 Mahasiswa Portal Login QA Report

Tanggal: 2026-05-24

## Scope

Menyiapkan satu user test mahasiswa aman di Core, memastikan app access `tu-farmasi` aktif, lalu menguji portal login TU via Core HTTP untuk mahasiswa di local/staging.

Tahap ini bukan SSO, bukan auto-login lintas aplikasi, bukan token URL, bukan write-back dari TU ke Core, dan bukan production cutover.

## Previous Evidence

TU-CONNECT-10 memberi keputusan:

- GO for staging/local use only.
- Login dosen `user_id=3`: success.
- App access `user_id=3`: true / has-access.
- Logout: OK.
- Wrong password: OK, generic failure.
- Session safety: OK.
- Production: NO-GO.
- SSO/auto-login/token URL: NO-GO.

Limitation yang ditutup pada tahap ini:

- Mahasiswa login belum dibuktikan.

## Mahasiswa Test User

Selected existing safe local/staging test user:

- user_id: 4
- type: mahasiswa
- student profile: available
- core_student_id: 1
- student number / NIM: available
- app_code: `tu-farmasi`
- role_slug: `mahasiswa`
- app access: active
- password test: available through safe local/staging channel
- password recorded in report: no

No password, hash, token, or client secret is included in this report.

## Core App Access Setup

Dry-run:

```bash
php artisan core:setup-tu-app-access --user-id=4 --role=mahasiswa
```

Result:

- selected user: `user_id=4`
- profile type: mahasiswa
- app code: `tu-farmasi`
- role slug: `mahasiswa`
- access status: would-create

Apply:

```bash
php artisan core:setup-tu-app-access --user-id=4 --role=mahasiswa --apply
```

Result:

- TU app access created.
- Scope was limited to one explicitly selected safe test user.
- No mass assignment.

Core readiness after apply:

- app registered: yes
- app active: yes
- active API client count: 1
- active user app access count: 2
- portal verify endpoint available: yes
- readiness verdict: `ready_for_staging_config`

## App Access Smoke Result

Command:

```bash
php artisan tu:core-smoke-test --user-id=4
```

Result:

- TU app access checked: has-access
- no secret output: OK
- no password/hash/token output: OK
- no write-back: OK

## Portal Login QA Result

Temporary TU QA mode:

```env
TU_PORTAL_AUTH_MODE=core_http
TU_CORE_READ_MODE=http-shadow
TU_CORE_HTTP_ENABLED=true
```

Preflight:

```bash
php artisan optimize:clear
php artisan tu:portal-login-preflight
```

Result:

- portal auth mode: `core_http`
- HTTP shadow readiness: ready
- HTTP app access readiness: ready
- HTTP password auth endpoint: ready
- HTTP portal login full readiness: ready
- can attempt Core login: yes
- status: READY

HTTP-level email login:

- route: `POST /portal/login`
- login identifier: email
- user_id: 4
- result: success
- final path: `/portal/pengajuan`
- `/portal/pengajuan` accessible after login: yes
- rendered page sensitive term check: no password/hash/token/secret marker exposed
- password recorded: no

HTTP-level NIM login:

- route: `POST /portal/login`
- login identifier: NIM / student number
- user_id: 4
- result: success
- final path: `/portal/pengajuan`
- `/portal/pengajuan` accessible after login: yes
- rendered page sensitive term check: no password/hash/token/secret marker exposed
- password recorded: no

Identity mapping check through `CoreHttpPortalAuthService`:

- source: `core_http`
- core_user_id: 4
- core_student_id: 1
- user_type: `student`
- identifier present: yes
- roles: `mahasiswa`

## Logout Result

Route:

```text
/portal/logout
```

Result:

- logout executed: OK
- portal identity session cleared: OK
- `/portal/pengajuan` after logout redirects to `/portal/login-required`: OK

## Negative Tests

Wrong password:

- result: generic failure shown on `/portal/login`
- no detailed user existence/access reason exposed: OK
- no password/hash/token/secret output: OK

Disabled mode / no full portal login after rollback:

- result: `tu:portal-login-preflight` returned NOT READY because `TU_PORTAL_AUTH_MODE=disabled`
- full portal login disabled after rollback: OK

Admin auth unaffected:

- `/admin/login` remained available: OK

Missing app access:

- skipped
- reason: no separate safe user without `tu-farmasi` access was provided for this pass

## Session Safety

Expected safe identity/session payload may contain:

- core_user_id
- core_student_id
- name
- email
- identifier / NIM
- user_type `student`
- app access roles/permissions

Forbidden session/report payload:

- password
- token
- hash
- Core client secret
- raw app-client credentials

HTTP-level page checks and service-level identity mapping confirmed no password/hash/token/secret was exposed in the tested output. Existing automated tests also cover safe session payload behavior.

## Rollback Status

After QA, TU `.env` was restored to:

```env
TU_PORTAL_AUTH_MODE=disabled
TU_CORE_READ_MODE=http-shadow
TU_CORE_HTTP_ENABLED=true
```

Post-rollback commands:

```bash
php artisan optimize:clear
php artisan tu:portal-auth-status
php artisan tu:portal-login-preflight
```

Post-rollback result:

- portal auth mode: disabled
- can attempt Core login: no
- HTTP portal login full readiness: not ready
- reason: `portal_auth_mode_not_core_http`
- status: NOT READY

This is expected and safe.

## Security Confirmation

- no password in report: OK
- no password/hash/token in session/report: OK
- no secret output: OK
- no write-back from TU: OK
- no SSO: OK
- no auto-login: OK
- no token URL: OK
- no admin auth replacement: OK
- no production cutover: OK
- no KP/SAFA changes: OK

## Commands Run

Core:

- `php artisan core:setup-tu-app-access --user-id=4 --role=mahasiswa`
- `php artisan core:setup-tu-app-access --user-id=4 --role=mahasiswa --apply`
- `php artisan core:tu-connection-readiness`
- `php artisan test`

TU:

- `php artisan optimize:clear`
- `php artisan tu:portal-login-preflight`
- `php artisan tu:portal-auth-status`
- `php artisan tu:core-smoke-test --user-id=4`
- local TU server: `php artisan serve --host=127.0.0.1 --port=8003`
- HTTP-level email login/logout checks against local TU server
- HTTP-level NIM login checks against local TU server
- `php artisan test`
- `composer validate`

## Test Result

TU:

- `php artisan tu:core-smoke-test --user-id=4`: OK, has-access
- `php artisan test`: 268 passed / 1423 assertions
- `composer validate`: OK, `./composer.json is valid`

Core:

- `php artisan core:tu-connection-readiness`: OK, verdict `ready_for_staging_config`
- `php artisan test`: 206 passed / 998 assertions

## Limitations

- Browser visual QA was not executed; HTTP-level QA was executed.
- Missing app access negative scenario remains skipped until a separate safe user without TU access is provided.
- Production cutover remains blocked.
- Staging UAT/sign-off remains required.

## Guardrails Confirmation

- Tidak menjalankan migration.
- Tidak menjalankan migrate:fresh/reset/rollback.
- Tidak mengubah database massal.
- Hanya membuat satu app access untuk satu user test mahasiswa yang dipilih eksplisit.
- Tidak cutover production.
- Tidak mengganti auth TU admin.
- Tidak membuat SSO.
- Tidak membuat auto-login.
- Tidak membuat token URL.
- Tidak menyimpan password/token.
- Tidak expose secret/password/hash/token.
- Tidak write-back dari TU.
- Tidak menyentuh KP/SAFA.

## Recommended Next Step

TU-CONNECT-12 Staging Auth UAT Checklist for Dosen + Mahasiswa.
