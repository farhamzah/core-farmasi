# CORE-TU-CONNECT-8 Portal Password Verify Endpoint Design

## Scope
Tahap ini mendesain dan mengimplementasikan endpoint Core untuk verifikasi password portal TU berbasis app-client internal API. Endpoint ini untuk local/staging readiness, bukan production cutover, bukan SSO, bukan auto-login, dan bukan token/session bridge.

## Why Endpoint Needed
TU HTTP shadow sudah bisa membaca directory data, leadership, profile link, dan app access. Gap terakhir untuk portal login adalah verifikasi password Core secara aman. Endpoint ini memungkinkan TU mengirim login identifier dan password ke Core untuk diverifikasi, lalu menerima payload identity minimal jika user aktif dan punya app access aktif `tu-farmasi`.

## Endpoint Path
- Method: `POST`
- Path: `/api/v1/internal/apps/tu-farmasi/portal-auth/verify`
- Controller: `App\Http\Controllers\Api\TuPortalAuthVerificationController`
- Service: `App\Services\TuFarmasi\TuPortalPasswordVerificationService`

## Request / Response Contract
Request body:

```json
{
  "login": "string",
  "password": "string",
  "context": {}
}
```

Success response:

```json
{
  "authenticated": true,
  "has_access": true,
  "user": {
    "id": 3,
    "name": "Nama User",
    "email": "user@example.test",
    "username": "user.name",
    "identity_type": "lecturer",
    "identity_number": "ID-123",
    "profile_type": "lecturer",
    "student": null,
    "lecturer": {
      "id": 1,
      "nidn": "NIDN123",
      "name": "Nama Dosen"
    },
    "employee": null
  },
  "app_access": {
    "app_code": "tu-farmasi",
    "roles": ["dosen"]
  }
}
```

Generic failure response:

```json
{
  "authenticated": false,
  "has_access": false,
  "reason": "invalid_credentials_or_access"
}
```

Failure response intentionally does not distinguish user not found, wrong password, inactive user, missing app access, inactive app access, or inactive app role.

## Security Guardrails
- Endpoint requires app-client authentication.
- Endpoint requires ability `verify:tu-portal-auth`.
- Password is verified using Laravel `Hash::check`.
- User must be active.
- `tu-farmasi` Core application must be active.
- User must have active `user_app_accesses` for `tu-farmasi`.
- App role must be active in `core_application_roles`.
- No SSO is created.
- No auto-login is created.
- No token, personal access token, signed URL, or cross-app session is created.
- No password change, user creation, or write-back is performed.
- No password, hash, token, secret, remember token, API token, app client secret, or secret hash is returned.
- Core API audit logging continues to omit request body and app-client secret.

## App-Client Auth
Required headers:
- `X-Core-App-Code: tu-farmasi`
- `X-Core-Client-Id`
- `X-Core-Client-Secret`
- `Accept: application/json`

Client secret must never be sent by query string or written to report/docs/logs.

## Ability
New Core API ability:
- `verify:tu-portal-auth`

The current local TU API client is present but only has the previous 8 read abilities. Readiness currently reports the new ability as missing until the client is updated or rotated with this ability.

## App Access Rule
The endpoint only returns authenticated success if:
- password is valid,
- Core user is active,
- Core application `tu-farmasi` is active,
- user has active `user_app_accesses` for `tu-farmasi`,
- assigned TU role slug is active in Core app role catalog.

## Login Identifiers
Supported identifiers:
- email
- username
- identity_number
- student NIM through `students.student_number`
- lecturer NIDN/NIP through `lecturers.lecturer_number`
- employee number through `employees.employee_number`

If a relation does not exist for a user, it is skipped safely.

## Rate Limit / Throttle
The route is inside the existing Core internal API route group and uses the same throttle configuration:
- global API throttle from `CORE_API_RATE_LIMIT`
- app-client rate limiting from `AuthenticateCoreApiClient`
- per app/ability limits remain configurable through `config/core_api.php`

## Implementation Status
Implementation status: active local/staging endpoint.

Implemented files:
- `app/Services/TuFarmasi/TuPortalPasswordVerificationService.php`
- `app/Http/Controllers/Api/TuPortalAuthVerificationController.php`
- `routes/api.php`
- `config/core_api.php`
- readiness service/command updates

Not implemented:
- TU runtime login wiring.
- SSO/token/session bridge.
- production cutover.
- automatic credential rotation.

## Tests
Added `tests/Feature/TuPortalPasswordVerificationTest.php`.

Covered:
- app-client auth required.
- invalid client rejected.
- missing ability rejected.
- valid password + active TU access returns safe identity.
- wrong password returns generic failure.
- valid password without access returns generic failure.
- inactive app access returns generic failure.
- inactive user returns generic failure.
- login via email, username, identity_number, student NIM, lecturer number, employee number.
- response does not contain password/hash/token/secret.
- endpoint does not create token, change password, or create user.
- readiness command shows portal verify endpoint availability.

## Commands Run
- `php artisan optimize:clear`
- `php artisan route:list`
- `php artisan test`
- `composer validate`
- `php artisan core:tu-connection-readiness`
- `php artisan test --filter=TuConnectionReadinessTest` after command output text adjustment

## Test Result
- `php artisan route:list`: OK, 89 routes; portal verify route registered.
- `php artisan test`: OK, 202 passed / 975 assertions.
- `composer validate`: OK, `./composer.json is valid`.
- `php artisan test --filter=TuConnectionReadinessTest`: OK, 7 passed / 24 assertions.

## Readiness Result
`php artisan core:tu-connection-readiness` result:
- app registered: yes
- app active: yes
- app public visible: no
- required roles missing: none
- active API client count: 1
- active user app access count: 1
- endpoints available: yes
- portal verify endpoint available: yes
- profile route available: yes
- verdict: `missing_api_client`
- missing ability on current client: `verify:tu-portal-auth`

The endpoint is available, but the existing TU API client must be updated/rotated to include `verify:tu-portal-auth` before TU can use full portal password verification.

## Documentation Updates
- `docs/CORE-TU-CONNECTION-PACKAGE.md`
- `docs/CORE-INTERNAL-API.md`
- `docs/CORE-CONSUMER-INTEGRATION-PLAN.md`

## Guardrails Confirmation
- Tidak mengubah TU/KP/SAFA.
- Tidak membuat token.
- Tidak membuat SSO.
- Tidak membuat auto-login.
- Tidak membuat token URL.
- Tidak mengganti Core auth.
- Tidak mengganti auth TU.
- Tidak expose password/hash/token/secret.
- Tidak menulis secret ke docs/report.
- Tidak menjalankan migration.
- Tidak menjalankan migrate:fresh/reset/rollback.
- Tidak membuat user baru.
- Tidak mengubah password user existing.
- Tidak write-back ke TU.

## Recommended Next Step
Update atau rotate TU API client agar memiliki ability `verify:tu-portal-auth`, lalu lanjut:
- `TU-CONNECT-8 Implement Portal Login via Core HTTP Client`

Jika owner ingin memisahkan credential update sebagai tahap tersendiri:
- `CORE-TU-CONNECT-9 Grant TU Portal Auth Ability`
