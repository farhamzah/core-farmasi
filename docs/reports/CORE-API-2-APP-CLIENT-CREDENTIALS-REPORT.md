# CORE-API-2 App Client Credentials & Token Rotation Report

## Scope
Tahap ini membuat skeleton app client credentials untuk API internal Core. Fitur ini untuk server-to-server API access antar aplikasi internal, bukan SSO, bukan auto-login, dan bukan cross-app user session.

## Previous Reports Reviewed
- CORE-API-1 Internal API Safety Baseline Report
- CORE-ACCESS-1 Dynamic App Registry & App Role Catalog context

## Files Changed
- `database/migrations/2026_05_24_000120_create_core_api_clients_table.php`
- `app/Models/CoreApiClient.php`
- `app/Services/CoreApiClientCredentialService.php`
- `app/Http/Middleware/AuthenticateCoreApiClient.php`
- `app/Filament/Resources/CoreApiClientResource.php`
- `app/Filament/Resources/CoreApiClientResource/Pages/ListCoreApiClients.php`
- `app/Filament/Resources/CoreApiClientResource/Pages/CreateCoreApiClient.php`
- `app/Filament/Resources/CoreApiClientResource/Pages/EditCoreApiClient.php`
- `bootstrap/app.php`
- `routes/api.php`
- `config/core_api.php`
- `tests/Feature/CoreApiClientCredentialTest.php`
- `tests/Feature/CoreInternalApiTest.php`
- `docs/CORE-INTERNAL-API.md`
- `docs/reports/CORE-API-2-APP-CLIENT-CREDENTIALS-REPORT.md`
- `README.md`

## Database Changes
Migration baru:
- `2026_05_24_000120_create_core_api_clients_table.php`

Tabel `core_api_clients`:
- `id`
- `core_application_id` nullable FK ke `core_applications`
- `app_code`
- `name`
- `client_id` unique
- `secret_hash`
- `abilities`
- `allowed_ips`
- `last_used_at`
- `last_rotated_at`
- `revoked_at`
- `is_active`
- `notes`
- `created_by`
- `rotated_by`
- `revoked_by`
- timestamps
- soft deletes

Sifat perubahan:
- Additive.
- Non-destruktif.
- Tidak mengubah data existing.
- Secret disimpan di `secret_hash`, bukan plaintext.

## Model & Service
Model:
- `CoreApiClient`

Model behavior:
- `secret_hash` hidden.
- casts `abilities`, `allowed_ips`, dates, `is_active`, dan `deleted_at`.
- relation ke `CoreApplication`.
- scope `active` dan `revoked`.
- helper `isRevoked`, `canUseAbility`, dan `markUsed`.

Service:
- `CoreApiClientCredentialService`

Service behavior:
- Generate high entropy secret.
- Generate client_id.
- Hash secret dengan Laravel Hash.
- Create client dengan secret yang hanya dikembalikan sekali.
- Validate `client_id + secret + app_code + ability`.
- Rotate secret.
- Revoke client.
- Update `last_used_at` saat valid.
- Tidak menyimpan plaintext secret.
- Tidak menulis secret plaintext di log.

## Middleware
Middleware:
- `AuthenticateCoreApiClient`

Required headers:
- `X-Core-Client-Id`
- `X-Core-Client-Secret`
- `X-Core-App-Code`

Rules:
- Menolak credential dari query string.
- `client_id`, `client_secret`, dan `app_code` wajib.
- Client harus aktif.
- Client tidak boleh revoked.
- Header app_code harus cocok dengan client app_code.
- `CoreApplication` harus aktif.
- Secret harus valid.
- Ability harus sesuai route jika diberikan.
- Valid client ditempel ke request attributes.

No URL token:
- Middleware secara eksplisit menolak `client_id` atau `client_secret` di query string.

## Filament Resource
Resource:
- `CoreApiClientResource`

Navigation:
- Group: Access Control
- Label: API Clients

Table:
- name
- app_code
- client_id
- abilities
- is_active
- revoked_at
- last_used_at
- last_rotated_at

Form:
- application/app_code
- name
- abilities
- allowed IPs
- is_active
- notes

Actions:
- Create client: generates `client_id` and secret hash.
- Rotate Secret: confirmation required, new secret shown once in notification, plaintext not persisted.
- Revoke: confirmation required, sets `revoked_at`, `revoked_by`, and `is_active=false`.

Security behavior:
- Secret shown once only.
- Secret hash hidden.
- No plaintext secret in persistent storage.

## API Route Changes
Internal app-to-app endpoints now use app client middleware:
- `GET /api/v1/internal/apps/{app_code}/users/{user}/access`
  - middleware: `auth.core-api-client:read:app-access`
- `GET /api/v1/internal/leadership/current`
  - middleware: `auth.core-api-client:read:leadership`

User-token auth endpoints remain unchanged:
- `POST /api/v1/auth/login`
- `GET|POST /api/v1/auth/validate-token`
- safe user/profile endpoints

Backward compatibility:
- Existing user-token auth tests still pass.
- Internal server-to-server endpoints now require app client headers.

## Documentation
`docs/CORE-INTERNAL-API.md` updated with:
- app client credential headers
- no query string secret/token rule
- ability requirements
- rotation/revocation notes
- no SSO/no auto-login guidance

No real credential/secret/token is documented.

## Security Confirmation
- No SSO.
- No auto-login.
- No cross-app user session.
- No token URL.
- No plaintext secret stored/logged/reported.
- Secret hash hidden from model serialization.
- Rotation available.
- Revocation available.
- Internal app-to-app API protected by app client middleware.

## Commands Run
- `php artisan optimize:clear` - OK.
- `php artisan migrate` - OK, migration `core_api_clients` applied.
- `php artisan test --filter=CoreApiClientCredentialTest` - OK, 7 passed / 41 assertions.
- `php artisan test --filter=CoreInternalApiTest` - OK, 6 passed / 43 assertions.
- `php artisan route:list` - OK, 71 routes shown.
- `php artisan test` - OK, 137 passed / 610 assertions.

Tidak menjalankan `npm run build` karena tidak ada perubahan frontend asset/CSS/JS build pipeline.

## Test Result
`php artisan test`: 137 passed / 610 assertions.

## Manual Check
- API client can be created: OK via service/resource tests.
- Secret not stored plaintext: OK.
- Valid client accepted: OK.
- Invalid client rejected: OK.
- Revoked client rejected: OK.
- Rotated old secret rejected: OK.
- Protected internal endpoint works: OK.
- No 500 error: OK.

## Guardrails Confirmation
- Tidak menjalankan migrate:fresh.
- Tidak menjalankan migrate:reset.
- Tidak menjalankan migrate:rollback.
- Tidak drop database.
- Tidak mengubah data master otomatis.
- Tidak execute import KP.
- Tidak mengubah database KP.
- Tidak menyentuh SAFA/KP/TU.
- Tidak membuat Core public.
- Tidak membuat SSO/bypass login.
- Tidak membuat cross-app user session.
- Tidak membuat token di URL.
- Tidak expose secret plaintext/hash.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.

## Risks / Notes
- Consumer apps belum dikonfigurasi.
- Token rotation SOP perlu ditulis.
- API audit mungkin perlu tahap lanjutan.
- Rate limit per client mungkin perlu tahap lanjutan.
- Allowed IP check masih skeleton sederhana.

## Recommended Next Step
Rekomendasi tahap berikutnya: `CORE-QA-1 Stabilization & Documentation` untuk merapikan dokumentasi operasional dan QA lintas fitur, atau `CORE-API-3 API Audit & Rate Limit per Client` jika API mulai dipakai aplikasi internal secara aktif.
