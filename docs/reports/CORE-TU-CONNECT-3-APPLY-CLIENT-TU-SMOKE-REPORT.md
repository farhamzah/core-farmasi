# CORE-TU-CONNECT-3 Apply Client & TU Smoke Report

## Scope
Tahap ini membuat TU API client di Core dan mengonfigurasi TU `.env` lokal/staging untuk smoke test read-only.

Tahap ini tidak membuat SSO, tidak membuat auto-login, tidak membuat token URL, tidak melakukan write-back, tidak mengganti auth TU, dan tidak melakukan production cutover.

## Files Changed
Core:
- `apps/core-farmasi/docs/reports/CORE-TU-CONNECT-3-APPLY-CLIENT-TU-SMOKE-REPORT.md`

TU:
- `apps/tu-farmasi/.env` updated locally with TU Core HTTP settings and secret hidden from report.

Temporary helper:
- `apps/core-farmasi/storage/app/codex-issue-tu-client.ps1` was created temporarily to move the one-time secret into TU `.env` without printing it, then deleted.

KP/SAFA:
- No file changed.

## API Client
- app_code: `tu-farmasi`.
- client created through `php artisan core:issue-tu-api-client --apply --show-env-template`.
- client rotated once because the first local `.env` write was denied after creation and the original one-time secret could not be shown or reused.
- required abilities present:
  - `read:users`
  - `read:students`
  - `read:lecturers`
  - `read:employees`
  - `read:study-programs`
  - `read:departments`
  - `read:app-access`
  - `read:leadership`
- secret was shown once by the command, captured in memory, and written only to TU `.env`.
- no secret is recorded in this report.
- no secret hash is printed in this report.

## TU Env
TU `.env` local/staging configured:
- `TU_CORE_HTTP_ENABLED=true`
- `TU_CORE_READ_MODE=http-shadow`
- `TU_CORE_BASE_URL` configured to local Core server.
- `TU_CORE_PROFILE_URL` configured to local Core `/profile`.
- `TU_CORE_APP_CODE=tu-farmasi`
- `TU_CORE_CLIENT_ID` configured.
- `TU_CORE_CLIENT_SECRET` configured but hidden.
- `TU_CORE_TIMEOUT=5`
- `TU_CORE_CONNECT_TIMEOUT=3`
- `TU_CORE_VERIFY_SSL=false` for local HTTP.
- `TU_CORE_FAIL_SILENTLY=true`

No `.env.example` secret was written.

## Readiness
Final `php artisan core:tu-connection-readiness` result:
- app registered: yes.
- app active: yes.
- app public visible: no.
- required roles missing: none.
- active API client count: 1.
- active user app access count: 0.
- endpoints available: yes.
- profile route available: yes.
- abilities: OK, 8 required abilities present.
- verdict: `ready_for_staging_config`.

## Smoke Test
`php artisan tu:core-smoke-test` completed successfully:
- departments: checked.
- study programs: checked.
- students: checked, safe empty result.
- lecturers: checked, safe empty result.
- employees: checked, safe empty result.
- people search: checked, safe empty result.
- leadership: checked, safe null.
- app access: skipped because no user id was supplied.
- no secret output: OK.
- no DB write from smoke test: OK by command design.

## API Logs
Core API request log check:
- TU requests logged: 9.
- latest logged TU requests include directory and leadership endpoints.
- app_code `tu-farmasi` recorded.
- no secret/token/password-like error message found in checked TU API logs.
- request body/header secret is not stored by Core API logging design.

## Security Confirmation
- No SSO.
- No auto-login.
- No cross-app session.
- No write-back.
- No token URL.
- No secret in report/docs.
- No secret in `.env.example`.
- No password/hash/token exposure.
- TU auth not replaced.
- No DB migrations.
- Core DB changed only by creating/rotating `core_api_clients` through the approved command.
- TU DB was not changed.
- KP/SAFA were not touched.

## Commands Run
Core:
- `php artisan core:tu-connection-readiness` before apply.
- `php artisan core:issue-tu-api-client --apply --show-env-template` through a sanitized capture script.
- `php artisan core:issue-tu-api-client --apply --rotate-existing --show-env-template` through a sanitized capture script.
- `php artisan core:tu-connection-readiness` after apply/rotate.
- `php artisan tinker --execute=...` for sanitized API log count and selected safe fields.
- `php artisan test`

TU:
- `php artisan optimize:clear`
- `php artisan tu:core-smoke-test`
- `php artisan test`

Local server:
- `php artisan serve --host=127.0.0.1 --port=8001` was started for Core HTTP smoke test.
- local Core server process was stopped after verification.

Not run:
- migrations.
- migrate:fresh/reset/rollback.
- production commands.
- KP/SAFA commands.

## Test Result
- Core tests: 183 passed / 911 assertions.
- TU tests: 256 passed / 1343 assertions.

## Guardrails Confirmation
- Tidak menjalankan migrate:fresh.
- Tidak menjalankan migrate:reset.
- Tidak menjalankan migrate:rollback.
- Tidak drop database.
- Tidak mengganti auth TU.
- Tidak membuat SSO/bypass login.
- Tidak membuat auto-login.
- Tidak membuat cross-app session.
- Tidak membuat token URL.
- Tidak menulis secret ke docs/report.
- Tidak menulis secret ke `.env.example`.
- Tidak expose password/hash/token/secret.
- Tidak write-back.
- Tidak menyentuh KP/SAFA.
- Tidak production cutover.

## Risks / Notes
- Ini konfigurasi local/staging environment. Staging/prod tetap perlu secret manager resmi.
- Jika Core server lokal dimatikan, TU smoke test HTTP tidak bisa berjalan sampai Core server/staging URL aktif.
- Production belum cutover.
- Untuk staging nyata, ulangi dengan staging URL, staging app client, dan secret manager.
- TU app access check masih skipped pada smoke test ini karena tidak ada `--user-id` Core yang diberikan.

## Recommended Next Step
Rekomendasi tahap berikutnya:
- TU-CONNECT-2 Staging Go/No-Go & Read Mode Decision.
- Alternatif: Core/TU handoff update setelah user id staging untuk app access check tersedia.
