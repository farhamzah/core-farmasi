# CORE-INTEGRATION-4 Staging Smoke Test SOP Report

## Scope
Tahap ini membuat SOP dan execution plan untuk real staging smoke test KP/TU terhadap Core API dan Core Profile Portal link. Tahap ini bukan real smoke test, bukan cutover, bukan SSO, bukan write-back, dan tidak membuat credential asli.

## Previous Reports Reviewed
- CORE-PROFILE-3 KP/TU Link-to-Core-Profile Report.
- CORE-INTEGRATION-2B KP Read-Only Adapter Report.
- CORE-INTEGRATION-2C KP Staging Smoke Test Plan Report.
- CORE-INTEGRATION-3 TU Read-Only Adapter Report.
- CORE-INTEGRATION-3B TU Staging Smoke Test Plan Report.
- `docs/CORE-CONSUMER-INTEGRATION-PLAN.md`.
- `docs/CORE-INTERNAL-API.md`.
- `docs/CORE-RELEASE-READINESS-CHECKLIST.md`.
- `docs/CORE-CENTRALIZED-PROFILE-PORTAL-PLAN.md`.
- KP/TU Core HTTP adapter read-only and staging smoke docs.

## Files Changed
Core:
- `apps/core-farmasi/docs/CORE-APP-CLIENT-CREDENTIAL-SOP.md`
- `apps/core-farmasi/docs/CORE-KP-TU-STAGING-SMOKE-EXECUTION-PLAN.md`
- `apps/core-farmasi/docs/CORE-PROFILE-CUTOVER-NOTES.md`
- `apps/core-farmasi/docs/templates/KP-TU-STAGING-SMOKE-RESULT-TEMPLATE.md`
- `apps/core-farmasi/docs/CORE-CONSUMER-INTEGRATION-PLAN.md`
- `apps/core-farmasi/README.md`
- `apps/core-farmasi/docs/reports/CORE-INTEGRATION-4-STAGING-SMOKE-SOP-REPORT.md`

KP:
- `apps/kp-farmasi/docs/CORE-HTTP-ADAPTER-STAGING-SMOKE-TEST.md`

TU:
- `apps/tu-farmasi/docs/CORE-HTTP-ADAPTER-STAGING-SMOKE-TEST.md`

## Credential SOP
`CORE-APP-CLIENT-CREDENTIAL-SOP.md` dibuat untuk mengatur:
- who can issue: hanya super-admin/admin-core authorized.
- required clients: `kp-farmasi` staging client dan `tu-farmasi` staging client.
- abilities KP: `read:users`, `read:students`, `read:lecturers`, `read:study-programs`, `read:app-access`, `read:leadership`.
- abilities TU: `read:users`, `read:students`, `read:lecturers`, `read:employees`, `read:study-programs`, `read:departments`, `read:app-access`, `read:leadership`.
- secret handling: secret shown once, masuk secret manager/env, tidak ditulis ke repo/chat/report/log/URL.
- rotation: rotate di Core, update env secara aman, clear config cache, smoke test, catat tanpa secret.
- revocation: revoke client, verifikasi rejected response, update catatan tanpa secret.
- emergency disable: set KP/TU HTTP enabled false, pertahankan KP legacy dan TU disabled, clear cache, revoke jika leak dicurigai.

## Smoke Execution Plan
`CORE-KP-TU-STAGING-SMOKE-EXECUTION-PLAN.md` dibuat untuk mengatur:
- preconditions: Core/KP/TU staging running, `/profile` accessible, app clients created, env set securely, logs accessible.
- KP steps: set env staging-only, run `php artisan config:clear`, run `php artisan kp:core-smoke-test`, verify users/students/lecturers/study programs/app access/leadership/profile link/API logs/no DB change.
- TU steps: set env staging-only, run `php artisan config:clear`, run `php artisan tu:core-smoke-test`, verify users/students/lecturers/employees/departments/study programs/person search/app access/leadership/profile link/API logs/no DB change.
- profile link checks: KP/TU link only when configured, no token/secret/session data, Core handles its own login, no SSO.
- negative tests: invalid secret 401, missing ability 403, revoked/disabled client rejected, rate limit 429, Core unavailable fail-safe, empty profile URL safe.
- expected results: read-only calls pass, API logs safe, no secret leak, no DB mutation, auth unchanged.
- go/no-go: Go only if endpoints/profile links/logs/negative tests safe; No-Go on secret leak, auth break, app crash, unexpected write, token URL, or repeated 401/403 from misconfig.
- rollback/disable: turn off KP/TU HTTP flags, clear cache, blank profile URL if needed, revoke app client if leak suspected.
- evidence: command summaries, API log counts, screenshots without secret, profile link screenshots without secret, Go/No-Go decision.

## Profile Cutover Notes
`CORE-PROFILE-CUTOVER-NOTES.md` dibuat untuk menegaskan:
- profile edits centralized in Core.
- KP/TU link to Core Profile Portal.
- existing local profile forms remain legacy/operational for now.
- app-specific operational fields remain local.
- future cutover must inventory duplicate fields, make Core-owned fields read-only in KP/TU, keep app-specific fields editable locally, run staging validation, and get owner approval.
- no immediate cutover, no SSO, no write-back, no auth replacement, and no KP/TU database migration.

## Result Template
Template dibuat:
- `apps/core-farmasi/docs/templates/KP-TU-STAGING-SMOKE-RESULT-TEMPLATE.md`

Template mencakup:
- date/time, environment, tester.
- Core URL and client metadata without secret.
- KP result.
- TU result.
- profile link result.
- negative test result.
- API log check.
- secret leak check.
- Go/No-Go.
- notes.

## Security Confirmation
- No real secret written.
- No real smoke execution run.
- No SSO.
- No auto-login.
- No cross-app session.
- No token URL.
- No write-back.
- No cutover.
- Default KP legacy preserved.
- Default TU disabled preserved.
- No database changes.
- No migration.
- No profile URL token.
- No production command.
- No legacy profile form removal.
- SAFA not touched.

## Commands Run
Core:
- `php artisan test` - OK, 169 passed / 854 assertions.

KP:
- `php artisan test` - OK, 129 passed / 613 assertions.

TU:
- `php artisan test` - OK, 253 passed / 1316 assertions.

Not run:
- migrations, because this stage is docs/SOP only.
- real smoke commands, because staging credentials/endpoints are not being used in this stage.
- npm build, because no frontend assets changed.

## Test Result
- Core: 169 passed / 854 assertions.
- KP: 129 passed / 613 assertions.
- TU: 253 passed / 1316 assertions.

## Guardrails Confirmation
- Tidak menjalankan migration.
- Tidak menjalankan migrate:fresh.
- Tidak menjalankan migrate:reset.
- Tidak menjalankan migrate:rollback.
- Tidak drop database.
- Tidak menghapus data.
- Tidak mengubah database Core/KP/TU.
- Tidak execute import.
- Tidak cutover.
- Tidak mengganti auth KP/TU.
- Tidak membuat SSO/bypass login.
- Tidak membuat auto-login.
- Tidak membuat cross-app session.
- Tidak membuat token URL.
- Tidak membuat real credential di repo.
- Tidak menulis real client secret.
- Tidak expose password/hash/token/secret.
- Tidak write-back ke Core.
- Tidak mengaktifkan integration default di repo.
- Tidak mengubah default KP legacy.
- Tidak mengubah default TU disabled.
- Tidak menghapus legacy profile form.
- Tidak menyentuh SAFA.
- Tidak membuat Core public.
- Tidak menjalankan real smoke test.

## Risks / Notes
- Real staging smoke test belum dijalankan.
- Credential harus dibuat manual di Core Admin oleh authorized admin.
- Secret harus masuk secret manager/staging env, bukan repository.
- Production belum aktif.
- Real Core profile URL belum dikonfigurasi di staging/production.
- Profile cutover field utama belum dilakukan.
- Karena tidak ada SSO, user mungkin perlu login Core secara terpisah.

## Recommended Next Step
Rekomendasi tahap berikutnya:
- CORE-INTEGRATION-4B Real Staging Smoke Test Execution, only after credentials are available.
- Alternatif: CORE-QA-3 Cross-App Final Regression.
- Alternatif: mulai modul berikutnya sesuai prioritas owner.
