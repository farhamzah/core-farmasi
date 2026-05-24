# CORE-QA-3 Cross-App Final Regression & Handoff Report

## Scope
Tahap ini melakukan final cross-app regression dan handoff documentation untuk Core, KP, dan TU sebelum real staging smoke test.

Tahap ini bukan fitur baru, bukan real staging smoke test, bukan production cutover, bukan SSO, dan tidak mengubah database.

## Previous Reports Reviewed
- CORE-INTEGRATION-4 Staging Smoke Test SOP Report.
- CORE-PROFILE-3 KP/TU Link-to-Core-Profile Report.
- CORE-INTEGRATION-2B KP Read-Only Adapter Report.
- CORE-INTEGRATION-2C KP Staging Smoke Test Plan Report.
- CORE-INTEGRATION-3 TU Read-Only Adapter Report.
- CORE-INTEGRATION-3B TU Staging Smoke Test Plan Report.

## Files Changed
Core:
- `apps/core-farmasi/docs/CORE-CROSS-APP-HANDOFF.md`
- `apps/core-farmasi/docs/CORE-ARCHITECTURE-SUMMARY.md`
- `apps/core-farmasi/docs/CORE-INTERNAL-API.md`
- `apps/core-farmasi/README.md`
- `apps/core-farmasi/docs/reports/CORE-QA-3-CROSS-APP-FINAL-REGRESSION-HANDOFF-REPORT.md`

KP:
- No file changed.

TU:
- No file changed.

## Docs Consistency Review
Core docs reviewed:
- `README.md`
- `docs/CORE-ARCHITECTURE-SUMMARY.md`
- `docs/CORE-INTERNAL-API.md`
- `docs/CORE-CONSUMER-INTEGRATION-PLAN.md`
- `docs/CORE-RELEASE-READINESS-CHECKLIST.md`
- `docs/CORE-APP-CLIENT-CREDENTIAL-SOP.md`
- `docs/CORE-KP-TU-STAGING-SMOKE-EXECUTION-PLAN.md`
- `docs/CORE-PROFILE-CUTOVER-NOTES.md`

KP docs reviewed:
- `apps/kp-farmasi/README.md`
- `apps/kp-farmasi/docs/CORE-HTTP-ADAPTER-READONLY.md`
- `apps/kp-farmasi/docs/CORE-HTTP-ADAPTER-STAGING-SMOKE-TEST.md`

TU docs reviewed:
- `apps/tu-farmasi/README.md`
- `apps/tu-farmasi/docs/CORE-HTTP-ADAPTER-READONLY.md`
- `apps/tu-farmasi/docs/CORE-HTTP-ADAPTER-STAGING-SMOKE-TEST.md`

Review result:
- Core is consistently documented as source of truth.
- KP/TU are consistently documented as read-only consumers.
- No SSO, no auto-login, no write-back, and no token URL are consistently stated.
- Profile edits are centralized in Core.
- KP/TU profile links point to Core Profile Portal and must not carry token/secret/session data.
- Legacy/local forms remain until a separate cutover stage.
- Real staging smoke test remains pending until credentials are available.
- Minor stale roadmap references were updated in Core architecture/API/README docs.

## Env / Config Safety Review
KP:
- `.env.example` keeps `KP_CORE_HTTP_ENABLED=false`.
- `.env.example` keeps `KP_CORE_READ_MODE=legacy`.
- `KP_CORE_CLIENT_ID=` is empty placeholder.
- `KP_CORE_CLIENT_SECRET=` is empty placeholder.
- `KP_CORE_PROFILE_URL=` is empty placeholder.
- `config/core_farmasi.php` defaults enabled false, read mode legacy, fail silently true, and no hardcoded secret.
- Profile URL fallback derives from base URL only and service tests cover no token query.

TU:
- `.env.example` keeps `TU_CORE_HTTP_ENABLED=false`.
- `.env.example` keeps `TU_CORE_READ_MODE=disabled`.
- `TU_CORE_CLIENT_ID=` is empty placeholder.
- `TU_CORE_CLIENT_SECRET=` is empty placeholder.
- `TU_CORE_PROFILE_URL=` is empty placeholder.
- `config/core_farmasi.php` defaults enabled false, read mode disabled, fail silently true, and no hardcoded secret.
- Profile URL fallback derives from base URL only and service tests cover no token query.

Secret scan:
- No real app client id/secret/token found in KP/TU Core integration env placeholders.
- Only safe default examples such as `REDIS_PASSWORD=null` and `MAIL_PASSWORD=null` were present.

## Test Results
Core:
- `php artisan optimize:clear` - OK.
- `php artisan test` - OK, 169 passed / 854 assertions.

KP:
- `php artisan optimize:clear` - OK.
- `php artisan test` - OK, 129 passed / 613 assertions.

TU:
- `php artisan optimize:clear` - OK.
- `php artisan test` - OK, 253 passed / 1316 assertions.

## Route / Config Checks
Core:
- `php artisan route:list` - OK, 88 routes.
- `/admin/login` remains the official admin login route.
- `/profile` and `/profile/edit` are protected profile portal routes.
- App-client directory, app access, and leadership endpoints remain present.

KP/TU:
- Route list was not run because this stage made no route changes in KP/TU. Existing full test suites cover route and portal smoke behavior.

## Handoff Summary
What is ready:
- Core identity/master/profile/API baseline.
- Core Profile Portal.
- KP read-only Core adapter skeleton.
- TU read-only Core adapter skeleton.
- KP/TU Core Profile Portal links when profile URL is configured.
- App client credential SOP.
- Combined KP/TU staging smoke execution plan.
- Smoke result template.
- Full local test baseline across Core/KP/TU.

What is not ready:
- Real staging credentials have not been issued.
- Real staging smoke test has not been executed.
- Production is not ready as a process verdict.
- Profile field cutover in KP/TU has not been performed.
- SSO is not designed or implemented.

Required before real staging:
- Create `kp-farmasi` and `tu-farmasi` staging app clients in Core.
- Assign minimal read abilities.
- Store secrets securely in staging secret manager/environment.
- Configure KP/TU staging env and profile URLs.
- Clear config cache.
- Run KP/TU smoke commands.
- Check Core API request logs.
- Check KP/TU logs for secret leaks.
- Verify profile link contains no token/secret.
- Complete Go/No-Go result template.

No-go risks:
- Secret leak.
- Missing ability causing repeated 403.
- App code mismatch causing 401/403.
- Core unavailable breaks KP/TU.
- Unexpected database mutation.
- Profile URL contains token/secret/session data.

## Security Confirmation
- No SSO.
- No auto-login.
- No cross-app session.
- No token URL.
- No write-back.
- No cutover.
- No real secret written.
- No database change.
- Default KP legacy preserved.
- Default TU disabled preserved.
- Profile edits centralized in Core.
- KP/TU remain read-only for canonical profile data.
- Existing legacy/local profile forms remain documented for future cutover.

## Guardrails Confirmation
- Tidak menjalankan migration.
- Tidak menjalankan migrate:fresh.
- Tidak menjalankan migrate:reset.
- Tidak menjalankan migrate:rollback.
- Tidak drop database.
- Tidak menghapus data.
- Tidak mengubah database Core/KP/TU.
- Tidak execute import.
- Tidak run real smoke test.
- Tidak cutover.
- Tidak mengganti auth KP/TU.
- Tidak membuat SSO/bypass login.
- Tidak membuat auto-login.
- Tidak membuat cross-app session.
- Tidak membuat token URL.
- Tidak menulis real secret.
- Tidak expose password/hash/token/secret.
- Tidak write-back.
- Tidak mengaktifkan integration default.
- Default legacy/disabled tetap.
- Tidak menghapus legacy profile form.
- Tidak menyentuh SAFA.
- Tidak membuat Core public.

## Recommended Next Step
Rekomendasi tahap berikutnya:
- CORE-INTEGRATION-4B Real Staging Smoke Test Execution jika credentials tersedia.
- Alternatif: lanjut modul lain sesuai prioritas owner.
