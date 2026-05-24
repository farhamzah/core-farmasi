# CORE-TU-CONNECT-2 Issue TU API Client Report

## Scope
Tahap ini menyiapkan command aman untuk issue atau rotate API client `tu-farmasi` dari sisi Core.

Tahap ini tidak menjalankan real smoke test, tidak mengubah TU/KP/SAFA, tidak membuat SSO, tidak membuat token URL, tidak melakukan cutover, dan tidak menulis secret asli ke repository/report.

## Previous Report Reviewed
- `docs/reports/CORE-TU-CONNECT-1-READINESS-PACKAGE-REPORT.md`
- `docs/CORE-TU-CONNECTION-PACKAGE.md`
- `docs/CORE-APP-CLIENT-CREDENTIAL-SOP.md`

## Files Changed
Core:
- `app/Console/Commands/IssueTuApiClientCommand.php`
- `bootstrap/app.php`
- `tests/Feature/IssueTuApiClientCommandTest.php`
- `docs/CORE-TU-CONNECTION-PACKAGE.md`
- `docs/CORE-APP-CLIENT-CREDENTIAL-SOP.md`
- `docs/reports/CORE-TU-CONNECT-2-ISSUE-TU-API-CLIENT-REPORT.md`

TU/KP/SAFA:
- No file changed.

## Command
Command dibuat:

```bash
php artisan core:issue-tu-api-client
```

Dry-run behavior:
- default mode.
- cek application `tu-farmasi`.
- tampilkan required abilities.
- cek active API client count.
- tidak membuat secret.
- tidak menulis database.

Apply behavior:
- hanya berjalan jika `--apply` diberikan.
- jika belum ada active client, membuat `CoreApiClient` untuk `tu-farmasi`.
- abilities memakai required TU abilities.
- secret plaintext ditampilkan sekali di terminal.
- database hanya menyimpan `secret_hash`.
- jika active client sudah ada, command tidak membuat duplikat secara default.

Rotate behavior:
- `--apply --rotate-existing` merotasi active client existing.
- jika multiple active client, command meminta `--force-rotate`.
- secret baru ditampilkan sekali di terminal dan tidak disimpan plaintext.

Env template:
- `--show-env-template` menampilkan placeholder TU staging env.
- template memakai `<client_id>` dan `<copy-secret-once>`, bukan secret asli.

## Required Abilities
Required TU abilities:
- `read:users`
- `read:students`
- `read:lecturers`
- `read:employees`
- `read:study-programs`
- `read:departments`
- `read:app-access`
- `read:leadership`

## Security Confirmation
- Dry-run default: OK.
- Secret hanya dibuat dan ditampilkan sekali saat `--apply`: OK.
- `--apply` tidak dijalankan pada tahap ini: OK.
- No secret in report: OK.
- No secret in docs: OK.
- No secret hash output: OK.
- No password/token output: OK.
- No TU changes: OK.
- No KP changes: OK.
- No SAFA changes: OK.
- No SSO: OK.
- No auto-login: OK.
- No token URL: OK.
- No write-back: OK.
- No role access loosening: OK.

## Commands Run
- `php -l app\Console\Commands\IssueTuApiClientCommand.php`
- `php -l tests\Feature\IssueTuApiClientCommandTest.php`
- `php artisan test --filter=IssueTuApiClientCommandTest`
- `php artisan optimize:clear`
- `php artisan core:issue-tu-api-client`
- `php artisan core:tu-connection-readiness`
- `php artisan test`

Not run:
- `php artisan core:issue-tu-api-client --apply`
- TU smoke test.
- migrations.
- npm build, because no frontend asset changed.

## Current Readiness
Hasil `php artisan core:tu-connection-readiness` setelah dry-run:
- app registered: yes.
- app active: yes.
- app public visible: no.
- required roles missing: none.
- active API client count: 0.
- active user app access count: 0.
- endpoints available: yes.
- profile route available: yes.
- verdict: `missing_api_client`.

Karena `--apply` tidak dijalankan, verdict tetap `missing_api_client`. Ini sesuai guardrail tahap ini: tidak membuat real credential tanpa explicit apply.

## Test Result
- Targeted command test: 7 passed / 33 assertions.
- Full Core test suite: 183 passed / 911 assertions.

## Guardrails Confirmation
- Tidak menjalankan migrate:fresh.
- Tidak menjalankan migrate:reset.
- Tidak menjalankan migrate:rollback.
- Tidak menjalankan migration.
- Tidak drop database.
- Tidak menghapus data.
- Tidak menulis real secret ke file.
- Tidak menulis secret ke report.
- Tidak menampilkan secret hash.
- Tidak mengubah TU/KP/SAFA.
- Tidak menjalankan TU smoke test.
- Tidak membuat SSO/bypass login.
- Tidak membuat auto-login.
- Tidak membuat cross-app session.
- Tidak membuat token URL.
- Tidak write-back.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.
- Tidak hardcode secret/credential.

## Recommended Next Step
Karena `--apply` belum dijalankan:
1. Owner/admin menjalankan command secara eksplisit di environment yang dituju:
   ```bash
   php artisan core:issue-tu-api-client --apply --show-env-template
   ```
2. Salin secret yang tampil sekali ke secret manager/staging env.
3. Jangan menulis secret ke report, chat, screenshot, atau URL.
4. Jalankan:
   ```bash
   php artisan core:tu-connection-readiness
   ```
5. Jika verdict `ready_for_staging_config`, lanjut ke TU-CONNECT-1 Configure TU Staging Env & Run Smoke Test.
