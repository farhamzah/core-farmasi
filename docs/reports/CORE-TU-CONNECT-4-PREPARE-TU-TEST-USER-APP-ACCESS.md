# CORE-TU-CONNECT-4 Prepare TU Test User App Access Report

## Scope
Tahap ini menyiapkan satu user test app access TU di Core agar TU dapat menjalankan smoke test user-level:

```bash
php artisan tu:core-smoke-test --user-id=<USER_ID_TEST>
```

Tahap ini hanya membuat satu `user_app_accesses` untuk app code `tu-farmasi`. Tidak ada perubahan TU/KP/SAFA, tidak ada SSO, tidak ada token URL, tidak ada auth replacement, tidak ada password change, dan tidak ada migration.

## Previous Context
CORE-TU-CONNECT-3 sudah membuat API client `tu-farmasi`, menyimpan secret hanya di `apps/tu-farmasi/.env`, mengonfigurasi TU Core HTTP local/staging, dan menjalankan smoke test read-only.

Status sebelum tahap ini:
- `core:tu-connection-readiness`: OK.
- verdict: `ready_for_staging_config`.
- active user app access count: 0.
- TU smoke test app access check masih skipped karena belum ada `--user-id`.

## Files Changed
Core:
- `app/Console/Commands/SetupTuAppAccessCommand.php`
- `bootstrap/app.php`
- `tests/Feature/SetupTuAppAccessCommandTest.php`
- `docs/reports/CORE-TU-CONNECT-4-PREPARE-TU-TEST-USER-APP-ACCESS.md`

TU/KP/SAFA:
- No file changed.

## User Test Selection
- user_id: 3.
- type: dosen.
- selected display name: Koordinator KP.
- role_slug: `dosen`.
- reason: command auto-pick found an active safe test/demo/local candidate with lecturer relation.

Sensitive fields not displayed:
- password.
- password hash.
- token.
- API secret.

## Access Assignment
- app_code: `tu-farmasi`.
- role_slug: `dosen`.
- status: created.
- command/method used:
  - dry-run: `php artisan core:setup-tu-app-access --auto-pick`
  - apply: `php artisan core:setup-tu-app-access --auto-pick --apply`

Assignment behavior:
- one user only.
- no mass assignment.
- no existing access deleted.
- no existing access deactivated.
- no password changed.
- no global role changed.
- no new app role created.

## Readiness Before / After
Before:
- active user app access count: 0.
- verdict: `ready_for_staging_config`.

After:
- active user app access count: 1.
- verdict: `ready_for_staging_config`.

Final readiness confirms:
- app registered: yes.
- app active: yes.
- app public visible: no.
- required roles missing: none.
- active API client count: 1.
- active user app access count: 1.
- endpoints available: yes.
- profile route available: yes.

## Security Confirmation
- No password/hash output.
- No secret output.
- No token output.
- No mass assignment.
- No SSO.
- No auto-login.
- No cross-app session.
- No token URL.
- No auth replacement.
- No password change.
- No TU changes.
- No KP changes.
- No SAFA changes.
- No migration.
- No migrate:fresh/reset/rollback.

## Commands Run
- `php artisan core:tu-connection-readiness`
- `php -l app\Console\Commands\SetupTuAppAccessCommand.php`
- `php artisan core:setup-tu-app-access --auto-pick`
- `php artisan core:setup-tu-app-access --auto-pick --apply`
- `php artisan test --filter=SetupTuAppAccessCommandTest`
- `php artisan test`

Not run:
- TU smoke test.
- migrations.
- migrate:fresh/reset/rollback.
- KP/SAFA commands.

## Test Result
- Targeted setup command test: 4 passed / 13 assertions.
- Core full test suite: 187 passed / 924 assertions.

## Next Step for TU
User test sudah siap. Jalankan dari TU:

```bash
cd apps/tu-farmasi
php artisan tu:core-smoke-test --user-id=3
```

Expected:
- TU app access checked: `has-access`.
- no secret output.
- no write-back.
- no auth replacement.
