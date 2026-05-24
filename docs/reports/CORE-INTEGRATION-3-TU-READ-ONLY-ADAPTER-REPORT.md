# CORE-INTEGRATION-3 TU Read-Only Adapter Report

## Scope
Tahap ini membuat skeleton adapter HTTP read-only di TU Farmasi untuk membaca Core API app-client endpoints. Adapter default disabled, tidak cutover, tidak mengganti auth TU, tidak menulis data ke Core, dan tidak menulis data ke database TU dari response Core.

## Previous Reports Reviewed
- CORE-INTEGRATION-1 Read-Only Consumer Integration Planning.
- CORE-INTEGRATION-2A App-Client Directory Endpoints.
- CORE-INTEGRATION-2B KP Read-Only Adapter.
- CORE-INTERNAL-API.
- CORE-CONSUMER-INTEGRATION-PLAN.
- CORE-ARCHITECTURE-SUMMARY.

## Files Changed
- `apps/tu-farmasi/config/core_farmasi.php`
- `apps/tu-farmasi/.env.example`
- `apps/tu-farmasi/app/Services/CoreFarmasiClient.php`
- `apps/tu-farmasi/tests/Feature/CoreFarmasiClientTest.php`
- `apps/tu-farmasi/docs/CORE-HTTP-ADAPTER-READONLY.md`
- `apps/core-farmasi/docs/CORE-CONSUMER-INTEGRATION-PLAN.md`
- `apps/core-farmasi/docs/reports/CORE-INTEGRATION-3-TU-READ-ONLY-ADAPTER-REPORT.md`

## TU Config
Config `core_farmasi.php` menambahkan:
- `enabled` default `false`.
- `base_url`.
- `app_code` default `tu-farmasi`.
- `client_id`.
- `client_secret`.
- `timeout` default 5 detik.
- `connect_timeout` default 3 detik.
- `verify_ssl` default `true`.
- `read_mode` default `disabled`.
- `fail_silently` default `true`.

`.env.example` hanya berisi placeholder. Tidak ada real secret, token, atau credential yang ditulis.

## TU Core Client Service
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
- Core unavailable tidak merusak TU saat `fail_silently=true`.
- Secret tidak dikirim lewat URL dan tidak ditulis ke log.
- `fail_silently=false` tetap melempar generic exception tanpa credential.

## Endpoint Mapping
- `getUser($id)` -> `GET /api/v1/internal/directory/users/{id}`
- `searchUsers($params)` -> `GET /api/v1/internal/directory/users`
- `getStudent($id)` -> `GET /api/v1/internal/directory/students/{id}`
- `searchStudents($params)` -> `GET /api/v1/internal/directory/students`
- `getLecturer($id)` -> `GET /api/v1/internal/directory/lecturers/{id}`
- `searchLecturers($params)` -> `GET /api/v1/internal/directory/lecturers`
- `getEmployee($id)` -> `GET /api/v1/internal/directory/employees/{id}`
- `searchEmployees($params)` -> `GET /api/v1/internal/directory/employees`
- `getStudyProgram($id)` -> `GET /api/v1/internal/directory/study-programs/{id}`
- `listStudyPrograms($params)` -> `GET /api/v1/internal/directory/study-programs`
- `getDepartment($id)` -> `GET /api/v1/internal/directory/departments/{id}`
- `listDepartments($params)` -> `GET /api/v1/internal/directory/departments`
- `getCurrentLeadership($params)` -> `GET /api/v1/internal/leadership/current`
- `checkUserAppAccess($userId)` -> `GET /api/v1/internal/apps/tu-farmasi/users/{user}/access`
- `searchPeople($query, $types, $limit)` -> safe wrapper over student/lecturer/employee directory search.

## Person Picker / Leadership Notes
- Student, lecturer, dan employee directory reads tersedia untuk person picker future usage.
- `searchPeople()` menormalisasi hasil menjadi `type`, `id`, `name`, `identifier`, `email`, dan `source=core`.
- Study program dan department reads tersedia untuk pilihan/fallback referensi TU.
- Current leadership endpoint tersedia untuk Dekan, Wakil Dekan, Kaprodi, dan pejabat penandatangan.
- Adapter belum dihubungkan ke production forms atau runtime flow TU.
- Existing Core DB read-only bridge dan person picker skeleton tidak dihapus dan tidak diganti.

## Security Confirmation
- Tidak membuat SSO.
- Tidak membuat auto-login.
- Tidak membuat token URL.
- Tidak menulis real secret.
- Tidak write-back ke Core.
- Tidak menulis data TU dari response Core.
- Tidak mengganti auth TU.
- Default disabled.
- Default `TU_CORE_READ_MODE=disabled` tetap.
- Tidak mengubah database TU/Core.
- Tidak menyentuh KP/SAFA.

## Tests
Tests memakai `Http::fake` untuk memastikan:
- Disabled client return null/empty dan tidak memanggil HTTP.
- Enabled client mengirim header app-client wajib.
- Secret dan client id tidak masuk URL.
- Endpoint user, student, lecturer, employee, study program, department, leadership, dan app access mapping benar.
- 404, 401, 403, 429, dan 500 ditangani aman.
- Core unavailable ditangani aman saat fail silently aktif.
- `searchPeople()` menormalisasi students/lecturers/employees.
- Tidak ada real secret di config/test output.

## Commands Run
- `php -l config\core_farmasi.php` di TU: OK.
- `php -l app\Services\CoreFarmasiClient.php` di TU: OK.
- `php -l tests\Feature\CoreFarmasiClientTest.php` di TU: OK.
- `php artisan optimize:clear` di TU: OK.
- `php artisan test --filter=CoreFarmasiClientTest` di TU: OK, 7 passed / 35 assertions.
- `php artisan test` di TU: OK, 229 passed / 1183 assertions.
- `php artisan test` di Core karena dokumentasi Core berubah: OK, 159 passed / 797 assertions.
- Migration tidak dijalankan karena tidak ada schema/database change.
- `npm run build` tidak dijalankan karena tidak ada perubahan frontend asset.

## Guardrails Confirmation
- Tidak menjalankan migrate:fresh.
- Tidak menjalankan migrate:reset.
- Tidak menjalankan migrate:rollback.
- Tidak menjalankan migration TU.
- Tidak drop database.
- Tidak menghapus data.
- Tidak mengubah database TU.
- Tidak mengubah database Core.
- Tidak execute import.
- Tidak production cutover.
- Tidak mengganti auth TU.
- Tidak membuat SSO/bypass login.
- Tidak membuat auto-login.
- Tidak membuat cross-app session.
- Tidak membuat token URL.
- Tidak menulis real client secret.
- Tidak expose password/hash/token/secret.
- Tidak write-back ke Core.
- Tidak write data to TU from Core response.
- Tidak menyentuh KP/SAFA.
- Tidak menghapus existing TU bridge/person picker skeleton.
- Tidak mengubah default read mode dari disabled ke core/http.
- Tidak hardcode secret/credential.

## Risks / Notes
- Adapter belum aktif production.
- Real app client credential untuk `tu-farmasi` belum dibuat/diuji di staging.
- Smoke test staging TU belum dijalankan.
- Keputusan mode baca TU future perlu tahap tersendiri.
- Person picker integration belum diaktifkan ke production forms.

## Recommended Next Step
Rekomendasi tahap berikutnya: CORE-INTEGRATION-3B TU Staging Smoke Test Plan. Alternatif: CORE-INTEGRATION-2D KP Real Staging Smoke Test Execution jika KP ingin divalidasi staging lebih dulu.
