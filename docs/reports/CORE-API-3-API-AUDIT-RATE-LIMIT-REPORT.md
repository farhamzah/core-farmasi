# CORE-API-3 API Audit & Rate Limit per Client Report

## Scope
Tahap ini menambahkan audit logging dan rate limit per API client untuk internal API Core Farmasi UBP. Pekerjaan ini bukan SSO, bukan auto-login, bukan cross-app session, dan tidak menambah endpoint bisnis baru.

## Previous Reports Reviewed
- CORE-API-2 App Client Credentials & Token Rotation Report
- CORE-API-1 Internal API Safety Baseline Report
- CORE-IMPORT-8 Users & App Access Execute/Rollback Report

## Files Changed
- `config/core_api.php`
- `routes/api.php`
- `app/Http/Middleware/AuthenticateCoreApiClient.php`
- `app/Models/CoreApiRequestLog.php`
- `app/Services/CoreApiAuditService.php`
- `app/Filament/Resources/CoreApiRequestLogResource.php`
- `app/Filament/Resources/CoreApiRequestLogResource/Pages/ListCoreApiRequestLogs.php`
- `database/migrations/2026_05_24_000121_create_core_api_request_logs_table.php`
- `tests/Feature/CoreApiClientCredentialTest.php`
- `docs/CORE-INTERNAL-API.md`
- `README.md`
- `docs/reports/CORE-API-3-API-AUDIT-RATE-LIMIT-REPORT.md`

## Database Changes
Migration additive membuat tabel `core_api_request_logs`.

Field utama:
- `core_api_client_id`
- `app_code`
- `client_id`
- `method`
- `path`
- `route_name`
- `status_code`
- `ability`
- `ip_address`
- `user_agent`
- `request_id`
- `duration_ms`
- `is_success`
- `error_code`
- `error_message`
- `created_at`

Index ditambahkan untuk client, app code, status, ability, dan waktu log agar pencarian audit lebih ringan.

Tabel ini tidak menyimpan request body penuh, headers penuh, authorization header, password, token, secret, atau secret hash.

## Audit Service
`CoreApiAuditService` dibuat untuk mencatat request internal API secara aman.

Yang dicatat:
- API client dan app code
- method dan path
- route name jika tersedia
- HTTP status
- ability jika tersedia
- IP dan user agent terbatas
- request id jika tersedia
- durasi request
- success flag
- error code dan error message aman

Yang tidak dicatat:
- request body
- headers penuh
- authorization header
- client secret
- token
- password
- password hash
- secret hash

Jika audit logging gagal, service menangkap exception dan tidak membuat API request menjadi 500 hanya karena audit log gagal.

## Middleware Updates
`AuthenticateCoreApiClient` diperbarui untuk:
- menolak client credential dari query string
- memakai header `X-Core-Client-Id`, `X-Core-Client-Secret`, dan `X-Core-App-Code`
- mencatat valid request
- mencatat invalid credential secara aman
- mencatat revoked/inactive client secara aman
- mencatat durasi request
- menyimpan ability context dari middleware

Invalid/revoked/inactive client tetap ditolak tanpa membocorkan detail secret.

## Rate Limit
`config/core_api.php` diperbarui dengan:
- `default_client_rate_limit`
- `client_rate_window_seconds`
- `per_app_rate_limits`
- `per_ability_rate_limits`

Rate limit memakai key per `client_id + app_code`, dengan fallback ke IP jika header client belum lengkap. Response 429 memakai pesan aman:

```json
{
  "message": "Too Many Requests"
}
```

Default limit saat ini 120 request per 60 detik, dapat dikonfigurasi lewat environment.

## Admin UI
`CoreApiRequestLogResource` dibuat sebagai resource read-only di Filament.

Tabel menampilkan:
- waktu request
- app code
- client id
- method
- path
- status code
- ability
- durasi
- success flag
- IP address

Filter tersedia untuk:
- client
- app code
- ability
- status code
- success/failure

Resource tidak menyediakan create, edit, delete, atau bulk delete.

## Documentation
`docs/CORE-INTERNAL-API.md` diperbarui dengan:
- audit logging
- rate limit per client
- 429 response
- header credential
- larangan token URL
- catatan keamanan secret rotation/revocation

README juga diperbarui untuk mencatat bahwa internal API kini memiliki audit log dan rate limit per client/app code.

## Security Confirmation
- Internal routes tetap protected.
- API client credential tetap lewat header, bukan URL.
- Tidak ada SSO.
- Tidak ada auto-login.
- Tidak ada cross-app user session.
- Tidak ada public sensitive API baru.
- Secret tidak disimpan di logs.
- Request body penuh tidak disimpan.
- Authorization headers tidak disimpan.
- Password/hash/token/secret tidak diexpose.
- API logs protected di admin.
- Role access tidak dilonggarkan.

## Commands Run
- `php artisan optimize:clear` - OK
- `php artisan migrate` - OK, nothing to migrate karena migration sudah berada dalam status applied
- `php artisan route:list` - OK, 72 routes
- `php artisan test` - OK, 150 passed / 728 assertions
- `npm run build` - tidak dijalankan karena tidak ada perubahan frontend asset/CSS/JS

## Test Result
`php artisan test` berhasil:

- 150 tests passed
- 728 assertions

Coverage relevan yang ditambahkan/diperbarui:
- tabel `core_api_request_logs` ada
- valid app client request tercatat
- invalid/revoked client ditolak dan dilog aman
- rate limit menghasilkan 429
- rate limit key per client
- log tidak menyimpan secret atau request body
- resource API request logs protected

## Manual Check
- valid request logged: OK
- invalid client rejected: OK
- revoked client rejected: OK
- rate limit 429 OK: OK
- log resource protected: OK
- no secret in logs: OK
- no request body in logs: OK
- no 500 error: OK

## Guardrails Confirmation
- Tidak menjalankan migrate:fresh.
- Tidak menjalankan migrate:reset.
- Tidak menjalankan migrate:rollback.
- Tidak drop database.
- Tidak menghapus data.
- Tidak mengubah data master otomatis.
- Tidak execute import KP.
- Tidak mengubah database KP.
- Tidak menyentuh SAFA/KP/TU.
- Tidak membuat Core public.
- Tidak membuat SSO/bypass login.
- Tidak membuat cross-app user session.
- Tidak membuat token di URL.
- Tidak expose password/hash/token/secret.
- Tidak menyimpan secret di logs.
- Tidak menyimpan request body penuh.
- Tidak menyimpan authorization headers.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.

## Risks / Notes
- Log pruning/retention belum dibuat.
- Per-ability limit masih basic dan belum dibuat sebagai kebijakan granular penuh.
- Consumer apps belum dikonfigurasi untuk menggunakan app client credential.
- Monitoring/alerting untuk anomali API belum dibuat.
- Audit log saat ini fokus request metadata aman, bukan payload inspection.

## Recommended Next Step
Rekomendasi tahap berikutnya: **CORE-API-4 Log Retention & Pruning**.

Alasannya, API audit logging sudah aktif sehingga Core perlu kebijakan retensi, pruning, dan ukuran log sebelum trafik consumer app nyata mulai berjalan.
