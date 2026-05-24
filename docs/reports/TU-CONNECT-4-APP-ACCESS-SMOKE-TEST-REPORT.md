# TU-CONNECT-4 App Access Smoke Test Report

## Scope

Tahap ini menguji kesiapan TU Farmasi untuk melakukan read-only app access check ke Core melalui command smoke test TU.

Target ideal tahap ini:

- menjalankan `php artisan tu:core-smoke-test`
- menjalankan `php artisan tu:core-smoke-test --user-id=<core-user-id-test>` jika user id test tersedia
- memastikan output tidak mengekspos secret/password/hash/token
- memastikan tidak ada write-back, SSO, auto-login, token URL, auth replacement, atau migration

## Previous Report Reviewed

Report sebelumnya yang direview:

- `docs/reports/CORE-TU-CONNECT-3-APPLY-CLIENT-TU-SMOKE-REPORT.md`

Hasil penting dari report sebelumnya:

- API client `tu-farmasi` sudah dibuat/rotated.
- Secret hanya disimpan di `apps/tu-farmasi/.env` lokal/staging.
- TU smoke dasar sebelumnya OK.
- App access check sebelumnya skipped karena tidak ada `--user-id`.
- Active user app access count saat itu: `0`.

## Files Changed

Core:

- `apps/core-farmasi/docs/reports/TU-CONNECT-4-APP-ACCESS-SMOKE-TEST-REPORT.md`

TU:

- Tidak ada file TU yang diubah.

KP/SAFA:

- Tidak disentuh.

## Environment

TU `.env` lokal/staging dicek secara sanitized:

- `TU_CORE_HTTP_ENABLED=true`
- `TU_CORE_READ_MODE=http-shadow`
- `TU_CORE_BASE_URL=configured`
- `TU_CORE_PROFILE_URL=configured`
- `TU_CORE_CLIENT_ID=configured`
- `TU_CORE_CLIENT_SECRET=configured but hidden`

Secret tidak dicetak dan tidak disalin ke report.

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
- active user app access count: 0
- endpoints available: yes
- profile route available: yes
- readiness verdict: `ready_for_staging_config`

## Smoke Test

### Basic Smoke

Command:

```bash
php artisan optimize:clear
php artisan tu:core-smoke-test
```

Result:

- basic smoke: OK
- departments checked: 0
- study programs checked: 0
- students checked: 0
- lecturers checked: 0
- employees checked: 0
- people search checked: 0
- leadership checked: safe-null
- TU app access checked: skipped
- command completed successfully

### App Access Smoke With User ID

Status: **skipped**.

Reason:

- No explicit user id test was provided.
- Core readiness reports active user app access count: `0`.
- No safe existing TU app access user candidate was available.
- TU must not guess user id, create users, or assign app access from TU.

No `php artisan tu:core-smoke-test --user-id=...` command was run.

## App Access Result

| Field | Result |
| --- | --- |
| user id test | not available |
| app access smoke | skipped |
| has_access | skipped |
| roles | skipped |

## Security Confirmation

- no secret output: OK
- no password/hash/token output: OK
- no write-back: OK
- no SSO: OK
- no auto-login: OK
- no token URL: OK
- no auth replacement: OK
- no DB migration: OK
- no user created from TU: OK
- no app access assigned from TU: OK
- no Core/KP/SAFA application changes: OK

## Commands Run

TU:

```bash
php artisan optimize:clear
php artisan tu:core-smoke-test
php artisan test
```

Core:

```bash
php artisan core:tu-connection-readiness
php artisan test
```

Not run:

```bash
php artisan tu:core-smoke-test --user-id=...
php artisan migrate
php artisan migrate:fresh
```

## Test Result

TU tests:

- `php artisan test`: 256 passed / 1343 assertions.

Core tests:

- `php artisan test`: 183 passed / 911 assertions.

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
- Tidak membuat user baru dari TU.
- Tidak assign akses baru dari TU.
- Tidak menyentuh KP/SAFA.

## Risks / Notes

- App access user-level belum terbukti karena tidak ada user test dengan active app access `tu-farmasi`.
- Core readiness masih `ready_for_staging_config`, tetapi active user app access count `0`.
- Tahap berikutnya membutuhkan penyiapan user test Core secara aman dari sisi Core/admin, bukan dari TU.

## Recommended Next Step

Karena app access smoke test dengan `--user-id` belum bisa dijalankan:

- **CORE-TU-CONNECT-4 Prepare TU Test User App Access**

Setelah user test tersedia dan aman:

- ulangi `php artisan tu:core-smoke-test --user-id=<safe-core-user-id>`
- lanjut ke **TU-CONNECT-5 Go/No-Go for HTTP Shadow Mode** jika app access check OK
