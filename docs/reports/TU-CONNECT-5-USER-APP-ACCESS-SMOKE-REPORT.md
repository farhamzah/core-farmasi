# TU-CONNECT-5 User-Level App Access Smoke Report

## Scope

Tahap ini menjalankan smoke test read-only dari TU Farmasi ke Core untuk membuktikan user-level app access check `tu-farmasi` bekerja dari sisi TU.

Command utama:

```bash
php artisan tu:core-smoke-test --user-id=3
```

Tahap ini tidak membuat SSO, tidak membuat auto-login, tidak mengganti auth TU, tidak membuat token URL, tidak melakukan write-back, dan tidak menjalankan migration.

## Previous Context

CORE-TU-CONNECT-4 Prepare TU Test User App Access selesai dengan hasil:

- user id test: `3`
- type: dosen
- role slug: `dosen`
- app code: `tu-farmasi`
- access status: created
- active user app access count before: `0`
- active user app access count after: `1`
- Core readiness verdict: `ready_for_staging_config`
- no password/hash/secret output
- no SSO, no token URL, no auth replacement, no migration

## Files Changed

Core:

- `apps/core-farmasi/docs/reports/TU-CONNECT-5-USER-APP-ACCESS-SMOKE-REPORT.md`

TU:

- Tidak ada file TU yang diubah.

KP/SAFA:

- Tidak disentuh.

## Environment

TU `.env` local/staging sudah dikonfigurasi dan dicek secara sanitized:

- `TU_CORE_HTTP_ENABLED=true`
- `TU_CORE_READ_MODE=http-shadow`
- `TU_CORE_BASE_URL=configured`
- `TU_CORE_PROFILE_URL=configured`
- `TU_CORE_CLIENT_ID=configured`
- `TU_CORE_CLIENT_SECRET=configured but hidden`

Secret tidak dicetak dan tidak dicatat di report.

## Core Server

`TU_CORE_BASE_URL` mengarah ke:

```text
http://127.0.0.1:8001
```

Pada awal pengecekan, port `8001` belum listen sehingga Core local server dijalankan sementara:

```bash
php artisan serve --host=127.0.0.1 --port=8001
```

Health check:

```text
GET http://127.0.0.1:8001/api/v1/health => {"status":"ok"}
```

Server lokal yang dijalankan untuk smoke test kemudian dihentikan setelah verifikasi.

## Commands Run

TU:

```bash
php artisan optimize:clear
php artisan tu:core-smoke-test
php artisan tu:core-smoke-test --user-id=3
php artisan test
```

Core:

```bash
php artisan core:tu-connection-readiness
php artisan serve --host=127.0.0.1 --port=8001
```

Not run:

```bash
php artisan migrate
php artisan migrate:fresh
php artisan migrate:rollback
```

## Smoke Result

### Basic Smoke

Final result after Core server was reachable:

- basic smoke: OK
- departments checked: 1
- study programs checked: 1
- students checked: 0
- lecturers checked: 0
- employees checked: 0
- people search checked: 0
- leadership checked: safe-null
- TU app access checked: skipped
- no secret output: OK

### User-Level Smoke

Command:

```bash
php artisan tu:core-smoke-test --user-id=3
```

Final result:

- user-level smoke: OK
- user id: `3`
- app access check: executed
- has_access: true
- command output: `TU app access checked: has-access`
- roles: `dosen` from prepared Core test user context; smoke command does not print role list
- no secret output: OK

## Core Readiness

Command:

```bash
php artisan core:tu-connection-readiness
```

Result:

- app code: `tu-farmasi`
- app registered: yes
- app active: yes
- app public visible: no
- required roles missing: none
- active API client count: 1
- active user app access count: 1
- endpoints available: yes
- profile route available: yes
- readiness verdict: `ready_for_staging_config`

## Security Confirmation

- no secret output: OK
- no password/hash/token output: OK
- no write-back: OK
- no SSO: OK
- no auto-login: OK
- no token URL: OK
- no auth replacement: OK
- no database migration: OK
- no database mutation from TU smoke: OK
- no Core user creation from TU: OK
- no app access assignment from TU: OK
- no KP/SAFA changes: OK

## Test Result

TU tests:

- `php artisan test`: 256 passed / 1343 assertions

Core tests:

- not run in this stage; Core code/runtime was not changed, only this report was added

## Guardrails Confirmation

- Tidak menjalankan migration.
- Tidak menjalankan `migrate:fresh`.
- Tidak mengubah database TU/Core.
- Tidak cutover.
- Tidak mengganti auth TU.
- Tidak membuat SSO/bypass login.
- Tidak membuat auto-login.
- Tidak membuat token URL.
- Tidak menulis real secret ke report.
- Tidak expose password/hash/token/secret.
- Tidak write-back.
- Tidak menyentuh KP/SAFA.

## Notes

- Sebelum Core local server aktif, smoke user-level sempat menghasilkan `no-access` karena Core HTTP endpoint tidak reachable dan TU client berjalan fail-silent.
- Setelah Core server lokal aktif dan health check OK, smoke test ulang dengan `--user-id=3` menghasilkan `has-access`.
- Smoke command saat ini hanya menampilkan `has-access`/`no-access`, tidak menampilkan daftar roles. Role `dosen` dicatat berdasarkan konteks user test yang disiapkan di Core.

## Recommended Next Step

Karena app access user-level berhasil:

- **TU-CONNECT-6 Go/No-Go for HTTP Shadow Mode**

