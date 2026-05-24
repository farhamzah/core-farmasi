# CORE-TA-LAB-CONNECT-0 Readiness Package Report

## Scope
Tahap ini menyiapkan readiness package Core untuk future consumer TA Farmasi dan Lab Farmasi. Scope hanya Core: app registry, app role catalog, required abilities, readiness service/command, connection package docs, dan tests. Tahap ini bukan real connection, bukan adapter implementation, bukan real secret issuance, bukan smoke test nyata, dan bukan cutover.

## Files Changed
- `database/seeders/CoreApplicationSeeder.php`
- `app/Services/AppConnectionReadinessService.php`
- `app/Console/Commands/AppConnectionReadinessCommand.php`
- `bootstrap/app.php`
- `tests/Feature/AppConnectionReadinessTest.php`
- `tests/Feature/LabAppRegistryPreparationTest.php`
- `docs/CORE-TA-CONNECTION-PACKAGE.md`
- `docs/CORE-LAB-CONNECTION-PACKAGE.md`
- `docs/CORE-CONSUMER-INTEGRATION-PLAN.md`
- `docs/CORE-APP-CLIENT-CREDENTIAL-SOP.md`
- `docs/reports/CORE-TA-LAB-CONNECT-0-READINESS-PACKAGE-REPORT.md`

## App Registry
TA:
- app_code: `ta-farmasi`
- name: `TA Farmasi`
- active: yes
- public visible: no
- requires login: yes
- sensitive: no

Lab:
- app_code: `lab-farmasi`
- name: `Lab Farmasi`
- active: yes
- public visible: no
- requires login: yes
- sensitive: no

Seeder uses `updateOrCreate` and does not delete existing apps.

## App Role Catalog
TA required roles:
- `mahasiswa`
- `dosen`
- `dosen-pembimbing`
- `penguji`
- `koordinator-ta`
- `admin-ta`
- `kaprodi`
- `dekan`
- `validator`

TA roles present/missing:
- present: OK
- missing: none

Lab required roles:
- `mahasiswa`
- `dosen`
- `laboran`
- `kepala-lab`
- `admin-lab`
- `pengguna-lab`
- `peminjam-alat`
- `teknisi`
- `viewer`

Lab roles present/missing:
- present: OK
- missing: none

Existing older Lab role slugs such as `lab-admin` remain preserved; this stage does not delete existing role catalog entries.

## Readiness Service/Command
Created generic readiness service:
- `App\Services\AppConnectionReadinessService`

Created generic command:

```bash
php artisan core:app-connection-readiness {app_code}
```

Supported app codes:
- `ta-farmasi`
- `lab-farmasi`

Checks:
- app exists/active/non-public.
- required roles present/missing.
- active API client count.
- ability coverage per active client.
- active user app access count.
- internal endpoint registry.
- Core `/profile` route.
- readiness verdict.

The command is read-only and does not print secret, secret hash, password, token, or credential.

## TA Connection Package
Created:
- `docs/CORE-TA-CONNECTION-PACKAGE.md`

Contains:
- app_code `ta-farmasi`.
- required roles.
- required abilities.
- Core endpoint mapping.
- Core profile URL.
- TA env placeholders with default disabled/read-only posture.
- credential handling notes.
- readiness command.
- future adapter task notes.

## Lab Connection Package
Created:
- `docs/CORE-LAB-CONNECTION-PACKAGE.md`

Contains:
- app_code `lab-farmasi`.
- required roles.
- required abilities.
- Core endpoint mapping.
- Core profile URL.
- Lab env placeholders with default disabled/read-only posture.
- credential handling notes.
- readiness command.
- future adapter task notes.

## Current Readiness
TA:
- app: OK
- roles: OK
- API client: missing
- abilities: missing because no active TA API client exists
- active user app access count: 1
- endpoints: OK
- profile route: OK
- verdict: `missing_api_client`

Lab:
- app: OK
- roles: OK
- API client: missing
- abilities: missing because no active Lab API client exists
- active user app access count: 0
- endpoints: OK
- profile route: OK
- verdict: `missing_api_client`

This is expected because the stage intentionally does not create real API client credentials.

## Security Confirmation
- no real secret: OK
- no secret output: OK
- no secret hash output: OK
- no SSO: OK
- no auto-login: OK
- no write-back: OK
- no token URL: OK
- no app consumer changes: OK
- no TA/Lab/KP/TU/SAFA changes: OK
- no consumer commands run: OK
- no migration: OK

## Commands Run
- `php artisan test --filter=AppConnectionReadinessTest`
- `php artisan db:seed --class=CoreApplicationSeeder`
- `php artisan core:app-connection-readiness ta-farmasi`
- `php artisan core:app-connection-readiness lab-farmasi`
- `php artisan optimize:clear`
- `php artisan test`
- `php artisan test --filter=LabAppRegistryPreparationTest`
- `php artisan test`

Note: one parallel readiness invocation immediately after cache clear hit a transient `bootstrap/cache/packages.php` rename lock on Windows. The command was rerun serially and passed.

## Test Result
- `php artisan test --filter=AppConnectionReadinessTest`: 6 passed / 42 assertions.
- `php artisan test --filter=LabAppRegistryPreparationTest`: 2 passed / 19 assertions.
- `php artisan test`: 217 passed / 1075 assertions.

## Guardrails Confirmation
- Tidak menjalankan migrate:fresh/reset/rollback.
- Tidak drop database.
- Tidak menghapus data.
- Tidak menulis real secret.
- Tidak menampilkan secret/hash/token/password.
- Tidak mengubah TA/Lab/KP/TU/SAFA.
- Tidak menjalankan TA/Lab commands.
- Tidak membuat SSO/bypass login.
- Tidak membuat auto-login.
- Tidak membuat token URL.
- Tidak write-back.
- Tidak mengganti Core auth.
- Tidak mengubah password user.
- Tidak membuat user baru.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.
- Tidak membuat Core public.

## Recommended Next Step
- `TA-CORE-CONNECT-0 Core Read-Only Adapter Skeleton in TA`
- `LAB-CORE-CONNECT-0 Core Read-Only Adapter Skeleton in Lab`
- atau issue staging clients untuk TA/Lab hanya saat adapter dan staging smoke plan sudah siap.
