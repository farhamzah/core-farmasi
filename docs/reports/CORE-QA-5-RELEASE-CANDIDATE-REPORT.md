# CORE-QA-5 Release Candidate Report

## Scope
Release candidate summary for the Core + KP + TU baseline before real staging smoke test execution.

This stage is documentation and final verification only. It does not add features, does not change runtime code, does not run migrations, does not execute real staging smoke tests, and does not perform production cutover.

## Files Changed
Core:
- `apps/core-farmasi/docs/reports/CORE-QA-5-RELEASE-CANDIDATE-REPORT.md`
- `apps/core-farmasi/docs/CORE-CROSS-APP-HANDOFF.md`

KP:
- No file changed.

TU:
- No file changed.

## Current Status
Core Farmasi UBP is ready as the local/staging integration baseline for identity, master profiles, app access, import operations, data quality, internal API, centralized Profile Portal, and consumer integration documentation.

KP Farmasi has a read-only Core adapter, smoke command, and Core Profile Portal link support. KP remains default safe with legacy mode preserved and integration disabled unless staging env explicitly enables it.

TU Farmasi has a read-only Core adapter, smoke command, Core Profile Portal link support, and person picker helper skeleton. TU remains default safe with disabled mode preserved.

Staging readiness: ready for real staging smoke test after app-client credentials are issued, stored securely, and staging env values are configured.

Production readiness: not ready as a process verdict. Production still requires real staging smoke execution, secret manager setup validation, go/no-go approval, and optional real backup/restore verification.

## Docs Consistency Review
Core docs are consistent that Core is the source of truth for identity, master profile data, profile edits, app access, leadership, internal API, and integration SOPs.

KP docs and config remain consistent with read-only Core integration, default legacy mode, no SSO, no token URL, no write-back, and Core Profile Portal link support.

TU docs and config remain consistent with read-only Core integration, default disabled mode, no SSO, no token URL, no write-back, Core Profile Portal link support, and person picker/leadership read-only planning.

Real staging smoke test remains pending credentials. Production remains not ready until staging smoke execution and go/no-go are completed.

## Module Summary
Core modules:
- UI light theme with blue pharmacy accent.
- centralized Profile Portal at `/profile`.
- identity, user, username, password, and change-password flows.
- students, lecturers, employees/tendik/staf/laboran master profiles.
- leadership assignments.
- app registry and app role catalog.
- user app access.
- import center.
- import execute and rollback.
- data quality dashboard.
- internal app launcher.
- internal API.
- app client credentials.
- API audit, per-client rate limit, and log pruning.
- consumer integration docs, credential SOP, smoke execution plan, backup/restore SOP, and secret management readiness.

KP modules:
- read-only Core adapter.
- `php artisan kp:core-smoke-test` smoke command.
- Core profile link support.
- default legacy mode preserved.

TU modules:
- read-only Core adapter.
- `php artisan tu:core-smoke-test` smoke command.
- Core profile link support.
- default disabled mode preserved.

## Security Summary
- No SSO.
- No auto-login.
- No cross-app session.
- No token URL.
- No write-back from KP/TU to Core.
- No real secret in repository docs or env examples.
- Profile edits are centralized in Core.
- Sensitive fields are hidden from app-client responses and UI surfaces.
- App client secret handling is covered by `CORE-APP-CLIENT-CREDENTIAL-SOP.md`.
- Backup/restore readiness is covered by `CORE-BACKUP-RESTORE-SOP.md`.
- Secret management readiness is covered by `CORE-SECRET-MANAGEMENT-READINESS.md`.

## Test Results
- Core: 169 passed / 854 assertions.
- KP: 129 passed / 613 assertions.
- TU: 253 passed / 1316 assertions.

Note: TU was run after `php artisan optimize:clear`; the final full suite passed.

## Commands Run
Core:
- `php artisan test`

KP:
- `php artisan test`

TU:
- `php artisan optimize:clear`
- `php artisan test`

Not run:
- migrations.
- real smoke commands.
- production commands.
- npm build, because this stage changed only Markdown documentation.

## Readiness Verdict
- Ready for local/staging integration baseline: yes.
- Ready for real staging smoke test: yes, after credentials are issued and stored securely in staging environment/secret manager.
- Ready for production: no. Production requires real staging smoke test execution, API log verification, secret management validation, go/no-go approval, and optional real backup/restore verification.

## Remaining Blockers
- Staging app-client credentials have not been issued yet.
- Secret manager / secure staging env setup has not been validated with real values.
- Real KP/TU staging smoke test has not been run.
- Go/no-go approval has not been completed.
- Optional real backup/restore verification has not been completed.

## Required Next Actions
1. Create KP and TU app clients in Core staging.
2. Store secrets in secret manager or protected staging environment.
3. Configure KP/TU staging env without committing secrets.
4. Run KP/TU smoke commands on staging.
5. Check Core API request logs.
6. Fill the KP/TU smoke result template.
7. Decide go/no-go.
8. Keep integration disabled if no-go.

## Guardrails Preserved
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
- Tidak mengubah default KP legacy.
- Tidak mengubah default TU disabled.
- Tidak menghapus legacy profile form.
- Tidak menyentuh SAFA.
- Tidak membuat Core public.

## Recommended Next Step
CORE-INTEGRATION-4B Real Staging Smoke Test Execution when credentials are ready.
