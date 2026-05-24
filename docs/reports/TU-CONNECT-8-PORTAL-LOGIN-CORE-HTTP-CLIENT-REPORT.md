# TU-CONNECT-8 Portal Login via Core HTTP Client Report

Tanggal: 2026-05-24

## Scope

Implementasi portal login TU menggunakan Core HTTP password verification endpoint. Tahap ini dilakukan di `apps/tu-farmasi`; Core hanya menerima report dokumentasi ini. Tidak ada SSO, auto-login, token URL, write-back, migration, atau production cutover.

## Previous Reports Reviewed

- `CORE-TU-CONNECT-8-PORTAL-PASSWORD-VERIFY-ENDPOINT-DESIGN.md`
- `CORE-TU-CONNECT-9-GRANT-TU-PORTAL-AUTH-ABILITY-REPORT.md`
- `TU-CONNECT-7-PORTAL-AUTH-HTTP-SHADOW-PLAN.md`

## Files Changed

TU:

- `app/Services/CoreFarmasiClient.php`
- `app/Services/Portal/CoreHttpPortalAuthService.php`
- `app/Services/Portal/PortalAuthMode.php`
- `app/Http/Controllers/Portal/PortalController.php`
- `resources/views/portal/login.blade.php`
- `config/tu_portal.php`
- `tests/Feature/CoreFarmasiClientTest.php`
- `tests/Unit/CoreHttpPortalAuthServiceTest.php`
- `tests/Feature/PortalLoginSkeletonTest.php`
- `tests/Feature/PortalLoginPreflightCommandTest.php`
- `tests/Unit/PortalAuthModeTest.php`
- `docs/CORE-HTTP-ADAPTER-READONLY.md`
- `README.md`

Core:

- `docs/reports/TU-CONNECT-8-PORTAL-LOGIN-CORE-HTTP-CLIENT-REPORT.md`

## Core Client Changes

`CoreFarmasiClient::verifyPortalPassword(string $login, string $password): array` ditambahkan.

Endpoint:

```text
POST /api/v1/internal/apps/tu-farmasi/portal-auth/verify
```

Behavior:

- memakai app-client headers existing;
- mengirim password hanya di request body;
- tidak memakai query string;
- tidak log password;
- tidak menyimpan password;
- menormalisasi response menjadi `authenticated`, `has_access`, `user`, `app_access`, dan `reason`;
- menangani disabled client, 401, 403, 422, 429, dan 500 secara aman.

## Portal Auth Service

`CoreHttpPortalAuthService` sekarang memakai `verifyPortalPassword()` untuk `attempt()`.

Success condition:

- `TU_PORTAL_AUTH_MODE=core_http`;
- HTTP shadow ready;
- Core response `authenticated=true`;
- Core response `has_access=true`;
- payload `user` tersedia.

Jika sukses, service memetakan payload aman ke `PortalIdentity` dengan `source=core_http`.

Jika gagal, service return `null` dengan reason internal aman. UI tetap memakai pesan generik.

## Controller / Session Integration

`PortalController::attemptLogin()` sekarang memilih:

- mode `core_http`: `CoreHttpPortalAuthService`;
- mode `core`: legacy DB read-only `CorePortalAuthService`;
- mode `disabled/local`: tetap gagal aman sesuai guard existing.

Saat login sukses:

- session diregenerasi;
- `PortalIdentityResolver::storeInSession()` menyimpan payload identity aman;
- redirect ke `/portal/pengajuan`.

Session tidak berisi password, token, secret, hash, atau Core client credential.

## Config

Mode baru:

```env
TU_PORTAL_AUTH_MODE=core_http
```

Default tetap:

```env
TU_PORTAL_AUTH_MODE=disabled
```

HTTP shadow tetap memakai:

```env
TU_CORE_HTTP_ENABLED=true
TU_CORE_READ_MODE=http-shadow
TU_CORE_APP_CODE=tu-farmasi
```

Repository tidak mengaktifkan `core_http` secara default.

## Security Confirmation

- no SSO: OK
- no auto-login: OK
- no token URL: OK
- no password storage: OK
- no token storage: OK
- no password/hash/token/secret output: OK
- no secret in report: OK
- no write-back to Core: OK
- admin auth unaffected: OK
- no production cutover: OK
- no KP/SAFA changes: OK

## Commands Run

TU:

- `php artisan test --filter=CoreFarmasiClientTest`
- `php artisan test --filter=CoreHttpPortalAuthServiceTest`
- `php artisan test --filter=PortalLoginSkeletonTest`
- `php artisan test --filter=PortalLoginPreflightCommandTest`
- `php artisan test --filter=PortalAuthModeTest`
- `php artisan optimize:clear`
- `php artisan test`
- `composer validate`

Core:

- Core tests not run because Core runtime/code was not changed; only this report was added.

## Test Result

TU targeted tests:

- `CoreFarmasiClientTest`: passed
- `CoreHttpPortalAuthServiceTest`: passed
- `PortalLoginSkeletonTest`: passed
- `PortalLoginPreflightCommandTest`: passed
- `PortalAuthModeTest`: passed

Full TU test result is recorded in the final task response.

## Guardrails Confirmation

- Tidak menjalankan migration.
- Tidak menjalankan migrate:fresh/reset/rollback.
- Tidak mengubah database TU/Core.
- Tidak mengubah Core runtime/code.
- Tidak mengganti admin auth TU.
- Tidak membuat SSO.
- Tidak membuat auto-login.
- Tidak membuat token URL.
- Tidak menyimpan password/token.
- Tidak expose secret/password/hash/token.
- Tidak write-back.
- Tidak production cutover.
- Tidak menyentuh KP/SAFA.

## Recommended Next Step

TU-CONNECT-9 Portal Login Local/Staging Manual QA.
