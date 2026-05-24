# CORE-TU-CONNECT-9 Grant TU Portal Auth Ability Report

## Scope
Tahap ini menambahkan ability `verify:tu-portal-auth` ke active API client `tu-farmasi` agar endpoint portal password verification bisa dipakai oleh TU. Perubahan dilakukan di Core saja, tanpa mengubah TU runtime, tanpa membuat SSO/token, dan tanpa rotasi secret.

## Previous Report Reviewed
- `docs/reports/CORE-TU-CONNECT-8-PORTAL-PASSWORD-VERIFY-ENDPOINT-DESIGN.md`

## Files Changed
- `app/Console/Commands/GrantTuApiClientAbilityCommand.php`
- `bootstrap/app.php`
- `tests/Feature/GrantTuApiClientAbilityCommandTest.php`
- `docs/CORE-TU-CONNECTION-PACKAGE.md`
- `docs/CORE-APP-CLIENT-CREDENTIAL-SOP.md`
- `docs/reports/CORE-TU-CONNECT-9-GRANT-TU-PORTAL-AUTH-ABILITY-REPORT.md`

## Readiness Before
Initial `php artisan core:tu-connection-readiness`:
- app registered: yes
- app active: yes
- app public visible: no
- required roles missing: none
- active API client count: 1
- active user app access count: 1
- endpoints available: yes
- portal verify endpoint available: yes
- profile route available: yes
- verdict before: `missing_api_client`
- missing abilities: `verify:tu-portal-auth`

## Ability Update
Command created:

```bash
php artisan core:grant-tu-api-client-ability
```

Dry-run behavior:
- targets active API client `tu-farmasi`.
- reports existing ability count and missing ability names.
- does not write database.
- does not read, print, rotate, or expose secret/hash.

Apply behavior:

```bash
php artisan core:grant-tu-api-client-ability --apply --all-required
```

Applied behavior:
- added missing ability `verify:tu-portal-auth`.
- preserved existing read abilities.
- did not remove any ability.
- did not rotate secret.
- did not change client id.
- did not print secret or secret hash.

Abilities before:
- 8 read abilities.
- missing `verify:tu-portal-auth`.

Abilities after:
- 9 abilities.
- no missing required abilities.

## Readiness After
Final `php artisan core:tu-connection-readiness`:
- app OK: yes
- roles OK: yes
- API client OK: yes
- abilities OK: yes
- endpoints OK: yes
- portal verify endpoint OK: yes
- profile route OK: yes
- active API client count: 1
- active user app access count: 1
- verdict after: `ready_for_staging_config`

## Security Confirmation
- no secret output: OK
- no secret hash output: OK
- secret rotation: no
- no SSO: OK
- no auto-login: OK
- no token URL: OK
- no TU changes: OK
- no KP/SAFA changes: OK
- no password change: OK
- no user creation: OK
- no Core auth replacement: OK
- no migration: OK

## Commands Run
- `php artisan core:tu-connection-readiness`
- `php artisan test --filter=GrantTuApiClientAbilityCommandTest`
- `php artisan core:grant-tu-api-client-ability`
- `php artisan core:grant-tu-api-client-ability --apply --all-required`
- `php artisan core:tu-connection-readiness`
- `php artisan optimize:clear`
- `php artisan test`
- `php artisan core:tu-connection-readiness`

## Test Result
- `php artisan test --filter=GrantTuApiClientAbilityCommandTest`: 4 passed / 23 assertions.
- `php artisan test`: 206 passed / 998 assertions.

## Guardrails Confirmation
- Tidak menjalankan migrate:fresh/reset/rollback.
- Tidak menjalankan migration.
- Tidak drop database.
- Tidak menghapus data.
- Tidak rotate secret.
- Tidak menampilkan secret/hash/token/password.
- Tidak menulis secret ke docs/report.
- Tidak mengubah TU/KP/SAFA.
- Tidak menjalankan TU smoke test.
- Tidak membuat SSO/bypass login.
- Tidak membuat auto-login.
- Tidak membuat token URL.
- Tidak write-back.
- Tidak mengganti Core auth.
- Tidak mengubah password user.
- Tidak membuat user baru.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.

## Recommended Next Step
Readiness Core untuk TU portal password verification sudah `ready_for_staging_config`.

Recommended next step:
- `TU-CONNECT-8 Implement Portal Login via Core HTTP Client`
