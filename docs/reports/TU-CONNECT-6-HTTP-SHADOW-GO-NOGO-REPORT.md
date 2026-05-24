# TU-CONNECT-6 HTTP Shadow Go/No-Go Report

## Scope

Evaluasi kesiapan HTTP shadow mode TU-Core berdasarkan smoke test dasar, user-level app access check, hasil test TU, dan guardrail keamanan.

HTTP shadow mode berarti:

- TU membaca data Core melalui HTTP client/app client.
- TU tetap tidak melakukan write-back ke Core.
- TU belum mengganti auth penuh.
- TU belum SSO.
- TU belum auto-login.
- TU belum token URL.
- TU belum cutover production.
- Mode ini hanya untuk local/staging verification dan persiapan integrasi.

## Previous Evidence

### CORE-TU-CONNECT-3 Summary

- API client `tu-farmasi` dibuat/rotated di Core.
- Required abilities tersedia:
  - `read:users`
  - `read:students`
  - `read:lecturers`
  - `read:employees`
  - `read:study-programs`
  - `read:departments`
  - `read:app-access`
  - `read:leadership`
- Secret hanya disimpan di `apps/tu-farmasi/.env` lokal/staging.
- Basic TU smoke test OK.
- App access user-level belum diuji karena belum ada user id.
- No SSO, no auto-login, no token URL, no write-back, no auth replacement.

### TU-CONNECT-4 Summary

- TU smoke dasar OK.
- Core readiness OK untuk staging config.
- Active user app access count masih `0`.
- User-level app access smoke skipped karena belum ada safe user id.
- No secret/password/hash/token output.
- No write-back, no migration, no auth replacement.

### TU-CONNECT-5 Summary

- User test Core disiapkan:
  - user id: `3`
  - type/context: dosen
  - role slug: `dosen`
  - app code: `tu-farmasi`
- Core readiness active user app access count: `1`.
- Basic smoke OK.
- User-level smoke `--user-id=3` OK.
- `has_access=true`.
- No secret/password/hash/token output.
- No write-back, no SSO, no token URL, no auth replacement.
- TU tests: 256 passed / 1343 assertions.

## Current Environment

TU `.env` local/staging status:

- `TU_CORE_HTTP_ENABLED=true`
- `TU_CORE_READ_MODE=http-shadow`
- `TU_CORE_BASE_URL=configured`
- `TU_CORE_PROFILE_URL=configured`
- `TU_CORE_CLIENT_ID=configured`
- `TU_CORE_CLIENT_SECRET=hidden`
- app code: `tu-farmasi`

Secret tidak dicetak dan tidak dicatat di report.

## Current Smoke Results

Core local server:

- `TU_CORE_BASE_URL` mengarah ke `http://127.0.0.1:8001`.
- Core server lokal belum aktif di awal tahap, lalu dijalankan sementara untuk smoke test.
- Health check `GET /api/v1/health`: OK.
- Server lokal yang dijalankan untuk smoke test dihentikan setelah validasi.

### Basic Smoke

Command:

```bash
php artisan optimize:clear
php artisan tu:core-smoke-test
```

Result:

- basic smoke: OK
- departments checked: 1
- study programs checked: 1
- students checked: 0
- lecturers checked: 0
- employees checked: 0
- people search checked: 0
- leadership checked: safe-null
- app access: skipped because no user id in basic smoke
- no secret output: OK

### User-Level Smoke

Command:

```bash
php artisan tu:core-smoke-test --user-id=3
```

Result:

- user-level smoke: OK
- user id: `3`
- has_access: true
- command output: `TU app access checked: has-access`
- role/context: `dosen` from prepared Core test user; smoke command does not print roles
- no secret output: OK

## Security Guardrails

- no secret output: OK
- no password/hash/token output: OK
- no write-back: OK
- no SSO: OK
- no auto-login: OK
- no token URL: OK
- no auth replacement: OK
- no migration/database change from this stage: OK
- no KP/SAFA changes: OK
- no production cutover: OK

## Known Limitations

- Portal login Core belum cutover.
- Production mode belum aktif.
- Core server local/staging dependency tetap diperlukan untuk HTTP shadow.
- Manual browser UAT belum fully executed.
- Credential/secret tetap hanya di `.env` local/staging; staging/prod perlu secret manager resmi.
- User test yang diuji baru konteks dosen; mahasiswa test user belum diuji jika belum tersedia.
- Smoke command hanya menampilkan `has-access`/`no-access`, bukan daftar role.

## Go/No-Go Decision

Decision: **GO for HTTP shadow mode in local/staging only.**

Not approved:

- **NOT GO for production cutover.**
- **NOT GO for SSO.**
- **NOT GO for auto-login.**
- **NOT GO for token URL.**
- **NOT GO for replacing TU auth.**

## Decision Criteria

| Criteria | Result |
| --- | --- |
| Basic smoke OK | yes |
| User-level app access OK | yes |
| `has_access=true` for user id 3 | yes |
| No secret leak | yes |
| No password/hash/token output | yes |
| No write-back | yes |
| TU tests pass | yes |
| Composer validate pass | yes |
| Guardrails intact | yes |

## Commands Run

TU:

```bash
php artisan optimize:clear
php artisan tu:core-smoke-test
php artisan tu:core-smoke-test --user-id=3
composer validate
php artisan test
```

Core:

```bash
php artisan serve --host=127.0.0.1 --port=8001
```

Core tests were not run in this stage because Core runtime/code was not changed; only this documentation report was added.

Not run:

```bash
php artisan migrate
php artisan migrate:fresh
php artisan migrate:rollback
```

## Test Results

TU:

- `php artisan test`: 256 passed / 1343 assertions.
- `composer validate`: valid.

Core:

- not run; no Core runtime/code changes.

## Recommended Next Step

Because HTTP shadow mode is GO for local/staging only:

- **TU-CONNECT-7 Portal Auth Integration Plan using HTTP Shadow**

Alternative implementation track:

- **TU-79 Portal Login With Core HTTP Client Skeleton**

Before any production cutover:

- run staging browser UAT
- verify student test user app access
- use a staging/prod secret manager
- document rollback
- keep no SSO/no token URL/no auto-login guardrails unless explicitly planned and approved

