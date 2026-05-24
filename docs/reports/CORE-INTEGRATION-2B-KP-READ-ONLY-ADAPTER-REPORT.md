# CORE-INTEGRATION-2B KP Read-Only Adapter Report

## Scope
Tahap ini membuat skeleton adapter HTTP read-only di KP Farmasi untuk membaca Core API app-client endpoints. Adapter default disabled, tidak cutover, tidak mengganti auth KP, dan tidak menulis data ke Core.

## Previous Reports Reviewed
- CORE-INTEGRATION-1 Read-Only Consumer Integration Planning.
- CORE-INTEGRATION-2A App-Client Directory Endpoints.
- CORE-INTERNAL-API.

## Files Changed
- `apps/kp-farmasi/config/core_farmasi.php`
- `apps/kp-farmasi/.env.example`
- `apps/kp-farmasi/app/Services/CoreFarmasiClient.php`
- `apps/kp-farmasi/tests/Feature/CoreFarmasiClientTest.php`
- `apps/kp-farmasi/docs/CORE-HTTP-ADAPTER-READONLY.md`
- `apps/core-farmasi/docs/CORE-CONSUMER-INTEGRATION-PLAN.md`
- `apps/core-farmasi/docs/reports/CORE-INTEGRATION-2B-KP-READ-ONLY-ADAPTER-REPORT.md`

## KP Config
Config `core_farmasi.php` menambahkan:
- `enabled` default `false`.
- `base_url`.
- `app_code` default `kp-farmasi`.
- `client_id`.
- `client_secret`.
- `timeout` default 5 detik.
- `connect_timeout` default 3 detik.
- `verify_ssl` default `true`.
- `read_mode` default `legacy`.
- `fail_silently` default `true`.

`.env.example` hanya berisi placeholder dan tidak berisi real secret.

## KP Core Client Service
`CoreFarmasiClient` memakai Laravel HTTP client untuk read-only calls ke Core API.

Header yang dikirim:
- `X-Core-App-Code`
- `X-Core-Client-Id`
- `X-Core-Client-Secret`
- `Accept: application/json`

Behavior:
- Jika disabled atau credential belum lengkap, service mengembalikan `null` atau collection kosong dan tidak memanggil HTTP.
- Timeout, connect timeout, dan SSL verification diambil dari config.
- 404 dikembalikan sebagai `null`.
- 401/403/429/500 aman saat `fail_silently=true`.
- Core unavailable tidak merusak KP saat `fail_silently=true`.
- Tidak ada secret di URL atau log.

## Endpoint Mapping
- `getUser($id)` -> `GET /api/v1/internal/directory/users/{id}`
- `searchUsers($params)` -> `GET /api/v1/internal/directory/users`
- `getStudent($id)` -> `GET /api/v1/internal/directory/students/{id}`
- `searchStudents($params)` -> `GET /api/v1/internal/directory/students`
- `getLecturer($id)` -> `GET /api/v1/internal/directory/lecturers/{id}`
- `searchLecturers($params)` -> `GET /api/v1/internal/directory/lecturers`
- `getStudyProgram($id)` -> `GET /api/v1/internal/directory/study-programs/{id}`
- `listStudyPrograms($params)` -> `GET /api/v1/internal/directory/study-programs`
- `getCurrentLeadership($params)` -> `GET /api/v1/internal/leadership/current`
- `checkUserAppAccess($userId)` -> `GET /api/v1/internal/apps/kp-farmasi/users/{user}/access`

## Security Confirmation
- Tidak membuat SSO.
- Tidak membuat auto-login.
- Tidak membuat token URL.
- Tidak menulis real secret.
- Tidak write-back ke Core.
- Tidak mengganti auth KP.
- Default disabled.
- Default legacy tetap.
- Tidak mengubah database KP/Core.
- Tidak menghapus Core DB read-only bridge existing.

## Tests
Tests memakai `Http::fake` untuk memastikan:
- Disabled client return null/empty dan tidak memanggil HTTP.
- Enabled client mengirim header app-client wajib.
- Secret dan client id tidak masuk URL.
- Endpoint user, student search, study program, leadership, dan app access mapping benar.
- 404, 401, 403, 429, dan 500 ditangani aman.
- Core unavailable ditangani aman saat fail silently aktif.

## Commands Run
- `php artisan optimize:clear` di KP: OK.
- `php artisan test --filter=CoreFarmasiClientTest` di KP: OK, 6 passed / 25 assertions.
- `php artisan test` di KP: OK, 125 passed / 594 assertions.
- `php artisan test` di Core karena dokumentasi/report Core berubah: OK, 159 passed / 797 assertions.
- Migration tidak dijalankan karena tidak ada perubahan schema.
- `npm run build` tidak dijalankan karena tidak ada perubahan frontend asset.

## Test Result
- KP: 125 passed / 594 assertions.
- Core: 159 passed / 797 assertions.

## Guardrails Confirmation
- Tidak menjalankan migrate:fresh.
- Tidak menjalankan migration KP.
- Tidak mengubah database KP/Core.
- Tidak cutover.
- Tidak mengganti auth KP.
- Tidak membuat SSO/bypass login.
- Tidak membuat token URL.
- Tidak menulis real secret.
- Tidak expose password/hash/token/secret.
- Tidak write-back.
- Tidak menyentuh SAFA/TU.
- Default legacy tetap.

## Risks / Notes
- Adapter belum aktif production.
- Perlu real app client credential di staging melalui SOP Core API client.
- Perlu smoke test staging dengan Core API nyata.
- Perlu keputusan bertahap untuk mode baca KP berikutnya setelah smoke test lulus.

## Recommended Next Step
Rekomendasi tahap berikutnya: CORE-INTEGRATION-2C KP Staging Smoke Test Plan.
