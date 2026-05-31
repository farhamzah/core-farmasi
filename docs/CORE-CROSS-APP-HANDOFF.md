# Core / KP / TU Cross-App Handoff

## Current Status
Core Farmasi UBP is ready for real staging smoke preparation as the source of truth for identity, master profile, app access, leadership, import, data quality, and internal API.

Current Core source-of-truth contract:
- `docs/CORE-CENTRAL-DATA-CONTRACT.md`
- `docs/CORE-MASTER-CRUD-MATRIX.md`

KP Farmasi and TU Farmasi already have read-only Core HTTP adapter skeletons. Both remain default safe:
- KP keeps legacy mode.
- TU keeps disabled mode.
- No production cutover has been performed.
- No real staging credential has been written to repository.

Real staging smoke test is still pending until app client credentials and staging environment variables are prepared securely.

## Core Capabilities
Core provides:
- canonical users and password policy.
- student, lecturer, and employee/tendik/staf/laboran master profiles.
- centralized Profile Portal at `/profile`.
- safe contact self-service update for phone/address.
- global roles and dynamic app-specific roles.
- dynamic app registry and user app access.
- leadership assignments for official positions.
- import center with validation, admin decisions, execute, and rollback for supported types.
- data quality dashboard.
- internal API with app-client credentials.
- app-client directory endpoints for users, students, lecturers, employees, study programs, and departments.
- app access check endpoint.
- current leadership endpoint.
- API audit logging, per-client rate limit, and log pruning.
- app client credential SOP and KP/TU smoke execution plan.

## KP Adapter Status
KP has:
- `config/core_farmasi.php`.
- `.env.example` placeholders.
- `CoreFarmasiClient` read-only HTTP adapter.
- command `php artisan kp:core-smoke-test`.
- Core profile link helper and UI link when `KP_CORE_PROFILE_URL` or base URL is configured.
- default `KP_CORE_HTTP_ENABLED=false`.
- default `KP_CORE_READ_MODE=legacy`.
- no token URL.
- no SSO.
- no write-back.
- existing legacy/local profile form preserved as legacy/operational.

## TU Adapter Status
TU has:
- `config/core_farmasi.php`.
- `.env.example` placeholders.
- `CoreFarmasiClient` read-only HTTP adapter.
- command `php artisan tu:core-smoke-test`.
- `searchPeople` read-only helper for person picker skeleton.
- Core profile link helper and UI link when `TU_CORE_PROFILE_URL` or base URL is configured.
- default `TU_CORE_HTTP_ENABLED=false`.
- default `TU_CORE_READ_MODE=disabled`.
- no token URL.
- no SSO.
- no write-back.
- existing local portal/workflow preserved.

## Profile Portal Status
Core Profile Portal:
- route: `/profile`.
- edit route: `/profile/edit`.
- protected by login.
- self-only.
- non-admin users still cannot access `/admin`.
- editable safe fields: phone/address on available linked profile.
- official identity fields remain locked.
- roles, app access, status, prodi, department, leadership, NIM/NIDN/NIP/employee number remain admin-only.

KP/TU profile link behavior:
- link is shown only when profile URL is configured.
- link must not contain token, secret, or cross-app session data.
- user may need to login separately to Core because there is no SSO.

## API Credential SOP
Credential SOP is documented in:
- `docs/CORE-APP-CLIENT-CREDENTIAL-SOP.md`

Key rules:
- only authorized Core admin/super-admin may issue credentials.
- KP and TU staging clients are separate.
- secret is shown once and must be stored in secret manager/environment.
- secret must never be committed, printed in reports, sent in chat, or included in URL.
- rotate/revoke and emergency disable procedures are documented.

## Smoke Test SOP
Smoke execution plan is documented in:
- `docs/CORE-KP-TU-STAGING-SMOKE-EXECUTION-PLAN.md`
- `docs/templates/KP-TU-STAGING-SMOKE-RESULT-TEMPLATE.md`

KP smoke checklist:
- `apps/kp-farmasi/docs/CORE-HTTP-ADAPTER-STAGING-SMOKE-TEST.md`

TU smoke checklist:
- `apps/tu-farmasi/docs/CORE-HTTP-ADAPTER-STAGING-SMOKE-TEST.md`

The real smoke test must verify:
- Core API connectivity.
- app-client abilities.
- app access check.
- current leadership.
- directory reads.
- profile link safety.
- 401/403/429 negative behavior.
- Core API logs.
- no secret leak.
- no DB mutation.

## Current Test Results
Latest release candidate baseline from CORE-QA-5:
- Core: 169 passed / 854 assertions.
- KP: 129 passed / 613 assertions.
- TU: 253 passed / 1316 assertions.

Release candidate summary:
- `docs/reports/CORE-QA-5-RELEASE-CANDIDATE-REPORT.md`

Current state audit:
- `docs/reports/CORE-CURRENT-STATE-2026-06-REPORT.md`

## What Is Ready
- Core local/staging integration baseline.
- KP read-only adapter skeleton.
- TU read-only adapter skeleton.
- Core Profile Portal.
- KP/TU links to Core Profile Portal.
- App client credential SOP.
- Combined KP/TU staging smoke execution plan.
- Result template for staging smoke evidence.
- Default-safe env/config.

## What Is Not Ready
- Real staging credentials are not issued yet.
- Real staging smoke test has not been run.
- Production cutover is not ready.
- Profile field cutover in KP/TU is not done.
- SSO is not designed or implemented.
- Monitoring/alerting beyond existing API logs is not finalized.

## Required Before Real Staging
1. Create `kp-farmasi` and `tu-farmasi` staging app clients in Core.
2. Assign only required read abilities.
3. Store client secrets securely in secret manager/staging env.
4. Configure staging env:
   - KP Core HTTP settings and profile URL.
   - TU Core HTTP settings and profile URL.
5. Clear config cache in KP/TU.
6. Run `php artisan kp:core-smoke-test`.
7. Run `php artisan tu:core-smoke-test`.
8. Verify Core API request logs.
9. Verify KP/TU logs contain no secrets.
10. Verify profile links contain no token/secret.
11. Confirm no DB mutation.
12. Complete Go/No-Go result template.

## Do Not Do
- Do not run production cutover.
- Do not create SSO.
- Do not create auto-login.
- Do not create cross-app session.
- Do not put token or secret in URL.
- Do not write real secret to repo/docs/report/log.
- Do not write back from KP/TU to Core.
- Do not change KP/TU auth.
- Do not enable integration by default.
- Do not remove legacy/local profile forms before a separate cutover stage.
- Do not make Core public.
