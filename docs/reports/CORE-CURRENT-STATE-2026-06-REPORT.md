# CORE Current State June 2026 Report

Tanggal audit: 2026-06-01 04:37:43 +07:00

## Scope

Report ini adalah audit current state dan live validation untuk `apps/core-farmasi`. Tahap ini bukan fitur baru, tidak melakukan commit/push, tidak menjalankan migration destructive, tidak menjalankan import/rollback, dan tidak menyentuh aplikasi lain.

## Git State

- Branch: `main`
- Tracking: `origin/main`
- Latest local HEAD: `6440d6c Release core farmasi baseline`
- Remote:
  - fetch: `https://github.com/farhamzah/core-farmasi.git`
  - push: `https://github.com/farhamzah/core-farmasi.git`
- Commit status: belum commit.
- Push status: belum push.

Modified files:

- `.env.example`
- `database/seeders/CoreApplicationSeeder.php`
- `database/seeders/DatabaseSeeder.php`
- `tests/Feature/AppConnectionReadinessTest.php`
- `tests/Feature/LabAppRegistryPreparationTest.php`

Untracked files:

- `app/Console/Commands/TaAppReadinessCommand.php`
- `database/seeders/LabFarmasiDevUserSeeder.php`
- `docs/LAB-FARMASI-DEV-ROLE-TEST-USERS.md`
- `docs/reports/CORE-TA-APP-REGISTRY-READINESS-REPORT.md`
- `tests/Feature/TaAppRegistryReadinessTest.php`
- `docs/reports/CORE-CURRENT-STATE-2026-06-REPORT.md`

Sensitive file status:

- `.env` is ignored and not tracked.
- `vendor/` and `node_modules/` are ignored and not tracked.
- generated cache/log/report folders are ignored.
- `database/database.sqlite` is ignored.

## Runtime Environment

`php artisan about` succeeded and reported:

- Application Name: `Core Farmasi UBP`
- Laravel Version: `12.60.2`
- PHP Version: `8.2.12`
- Composer Version: `2.9.1`
- Environment: `local`
- Timezone: `Asia/Jakarta`
- Cache driver: `database`
- Database driver: `mysql`
- Queue driver: `database`
- Session driver: `database`
- Storage public link: linked
- Filament Version: `v5.6.5`
- Livewire Version: `v4.3.0`

DB live connectivity check:

- `Test-NetConnection 127.0.0.1 -Port 3306`: `TcpTestSucceeded=False`
- MySQL/MariaDB live validation status: blocked because local port `3306` refused connection.

## Validation Commands

| Command | Result |
| --- | --- |
| `git status --short` | Completed; local changes listed above. |
| `git branch -vv` | Completed; branch `main` tracks `origin/main`. |
| `git remote -v` | Completed; remote is `farhamzah/core-farmasi.git`. |
| `php artisan about` | OK. |
| `php artisan optimize:clear` | Failed at database cache clear due MySQL connection refused. |
| `Test-NetConnection 127.0.0.1 -Port 3306` | Failed; TCP connect false. |
| `php artisan migrate:status` | Failed due MySQL connection refused. |
| `php artisan route:list --path=api` | OK; 29 routes shown. |
| `php artisan route:list --path=admin --method=GET` | OK; 44 routes shown. |
| `php artisan list core` | OK; Core command namespace listed. |
| `php artisan core:app-connection-readiness ta-farmasi` | Failed due MySQL connection refused. |
| `php artisan core:app-connection-readiness lab-farmasi` | Failed due MySQL connection refused. |
| `php artisan core:app-connection-readiness helpdesk-farmasi` | Failed due MySQL connection refused. |
| `php artisan core:tu-connection-readiness` | Failed due MySQL connection refused. |
| `php artisan core:ta-app-readiness` | Failed due MySQL connection refused. |
| `php artisan core:lab-app-readiness` | Failed due MySQL connection refused. |
| `php artisan test` | OK. |

## Test Result

```text
Tests: 220 passed, 1130 assertions
```

Code health is healthy under the test environment. Live MySQL validation remains blocked until local MySQL/MariaDB is running and accepting connections on `127.0.0.1:3306`.

## App Registry / Readiness Summary

Live app registry readiness commands could not complete because MySQL refused connections. Based on code, seeders, tests, and reports:

| App | Current prepared status | Live readiness |
| --- | --- | --- |
| KP Farmasi | KP import and integration baseline v1 closed; Core has KP app access and internal endpoints. | Not revalidated live due DB connection refused. |
| TU Farmasi | App registry, app-client credential tooling, connection readiness, portal password verification endpoint, and staged auth reports exist. Production cutover remains NO-GO. | Not revalidated live due DB connection refused. |
| TA Farmasi | `CoreApplicationSeeder` prepares `ta-farmasi`; `core:ta-app-readiness` command and tests exist. | Not revalidated live due DB connection refused. |
| Lab Farmasi | Registry, role catalog, lab readiness/access commands, local dev test user seeder, and tests exist. | Not revalidated live due DB connection refused. |
| Helpdesk Farmasi | Future app registry/readiness service coverage exists. | Not revalidated live due DB connection refused. |
| SAFA UBP | Registered in Core app registry as a private/internal app. | Not revalidated live due DB connection refused. |

## Core Module Summary

Core currently contains:

- master data for users, students, lecturers, employees/tendik/staf/laboran, departments, and study programs.
- centralized identity fields, username/identity number support, password policy, initial password reset, and change-password flow.
- centralized Profile Portal with safe self-service contact edits.
- global roles, dynamic app registry, app-specific role catalog, and user app access.
- leadership assignments for official positions such as Dekan/Kaprodi.
- import center with upload, preview, validation, admin decision UI, execute, and rollback safety.
- data quality dashboard.
- internal app launcher.
- internal app-client API with directory endpoints, app access check, current leadership endpoint, audit log, per-client rate limit, and log pruning.
- KP import/migration guardrails and rollback preview.
- TU connection readiness, API client issuance/grant tooling, and portal password verification endpoint.
- TA, Lab, and Helpdesk readiness/registry preparation.

## Local Changes Review

| File | Purpose / function | Safe to commit |
| --- | --- | --- |
| `.env.example` | Adds local placeholder URLs for TA Farmasi. | Yes, placeholder only. |
| `database/seeders/CoreApplicationSeeder.php` | Updates TA registry metadata/default local URLs; adds Lab alias roles `admin_lab` and `koordinator_lab`. | Yes, but review role alias naming consistency before commit. |
| `database/seeders/DatabaseSeeder.php` | Calls Lab demo user seeder only in `local`/`testing`. | Yes, guarded local/testing only. |
| `tests/Feature/AppConnectionReadinessTest.php` | Aligns expected TA app name to `TA Farmasi UBP`. | Yes. |
| `tests/Feature/LabAppRegistryPreparationTest.php` | Adds coverage for Lab demo users and new Lab role aliases. | Yes. |
| `app/Console/Commands/TaAppReadinessCommand.php` | Read-only readiness command for `ta-farmasi` registry/role catalog. | Yes. |
| `database/seeders/LabFarmasiDevUserSeeder.php` | Local/testing-only demo users for Lab role testing. | Yes, after confirming demo users are desired in local/test seeding. |
| `docs/LAB-FARMASI-DEV-ROLE-TEST-USERS.md` | Documents local Lab demo users and guardrails. | Yes. |
| `docs/reports/CORE-TA-APP-REGISTRY-READINESS-REPORT.md` | Documents TA registry readiness stage. | Yes. |
| `tests/Feature/TaAppRegistryReadinessTest.php` | Covers TA registry readiness command and no mass access grant. | Yes. |
| `docs/reports/CORE-CURRENT-STATE-2026-06-REPORT.md` | This current state audit report. | Yes. |

## Secret Safety Review

- `.env` committed: no.
- `.env` tracked: no.
- vendor/node_modules included: no.
- database dump included: no.
- database sqlite tracked: no.
- real secret found: no evidence found in tracked source scan.
- scan findings: placeholders/SOP/test strings only, including `<copy-secret-once>`, `CORE_FARMASI_CLIENT_SECRET=`, `TU_CORE_CLIENT_SECRET=<staging-client-secret>`, and tests using local `$secret` variables.

Command used:

```bash
git grep -n "CLIENT_SECRET\|client_secret\|PASSWORD=\|BEGIN PRIVATE KEY\|SECRET=\|TOKEN=" -- . ":(exclude).env.example" ":(exclude)docs/**/*.md"
```

Note: due Git pathspec behavior on Windows, docs placeholders were still reported; reviewed findings are placeholders, not real secrets.

## Current Verdict

- local code health: healthy.
- test health: healthy.
- DB live validation: blocked by MySQL/MariaDB not accepting TCP connection on `127.0.0.1:3306`.
- ready to commit: yes with notes, after reviewing that local/testing Lab demo users are intentionally included.
- ready to push: yes with notes after commit, but live DB validation should ideally be rerun after MySQL is active.
- production ready: no. Production still requires staging/prod process, secret handling, go/no-go approval, and production environment validation.

## Recommended Next Step

1. Start MySQL/MariaDB locally and rerun live validation:

```powershell
php artisan optimize:clear
php artisan about
php artisan migrate:status
php artisan core:app-connection-readiness ta-farmasi
php artisan core:app-connection-readiness lab-farmasi
php artisan core:app-connection-readiness helpdesk-farmasi
php artisan core:tu-connection-readiness
php artisan core:ta-app-readiness
php artisan core:lab-app-readiness
```

2. If MySQL live validation passes, continue to `CORE-GIT-1 Commit & Push Core Baseline`.
3. If MySQL still fails, fix/start local DB service first and do not claim live DB readiness.
4. If secret risk appears before commit, clean sensitive files before staging.

## Guardrails Confirmation

Confirmed during this stage:

- did not run `migrate:fresh`.
- did not run `migrate:reset`.
- did not run `migrate:rollback`.
- did not drop database.
- did not delete data.
- did not execute import.
- did not rollback import.
- did not commit.
- did not push.
- did not write secrets.
- did not modify `.env`.
- did not touch other apps.
