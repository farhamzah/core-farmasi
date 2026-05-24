# CORE-INTEGRATION-2A App-Client Directory Endpoints Report

## Scope
Tahap ini membuat endpoint read-only app-client profile/directory di Core untuk consumer app seperti KP/TU. Tahap ini belum membuat adapter KP/TU, tidak melakukan cutover, tidak membuat write API, tidak membuat SSO, dan tidak mengubah data master.

## Previous Reports Reviewed
- CORE-INTEGRATION-1 Read-Only Consumer Integration Planning Report
- CORE-API-1 Internal API Safety Baseline Report
- CORE-API-2 App Client Credentials & Token Rotation Report
- CORE-API-3 API Audit & Rate Limit per Client Report
- CORE-API-4 Log Retention & Pruning Report

## Files Changed
- `config/core_api.php`
- `routes/api.php`
- `app/Http/Controllers/Api/InternalDirectoryController.php`
- `app/Http/Middleware/AuthenticateCoreApiClient.php`
- `app/Services/CoreApiClientCredentialService.php`
- `app/Services/CoreApiResponseSanitizer.php`
- `tests/Feature/CoreInternalApiTest.php`
- `docs/CORE-INTERNAL-API.md`
- `docs/CORE-CONSUMER-INTEGRATION-PLAN.md`
- `docs/CORE-ARCHITECTURE-SUMMARY.md`
- `README.md`
- `docs/reports/CORE-INTEGRATION-2A-APP-CLIENT-DIRECTORY-ENDPOINTS-REPORT.md`

## Config Updates
`config/core_api.php` diperbarui:

Abilities app-client:
- `read:users`
- `read:students`
- `read:lecturers`
- `read:employees`
- `read:study-programs`
- `read:departments`
- `read:app-access`
- `read:leadership`

Directory pagination:
- `directory_default_limit`: 25
- `directory_max_limit`: 100

`expose_birth_date` tetap default `false`.

## Sanitizer Updates
`CoreApiResponseSanitizer` diperluas untuk safe response:
- user
- student
- lecturer
- employee
- study program
- department
- user summary pada profile jika relation tersedia

Field yang tidak diexpose:
- password
- password hash
- remember token
- API token/hash
- client secret
- secret hash
- birth_date default
- sensitive internal reset/secret metadata

## Endpoints Added
Semua endpoint berada di `/api/v1/internal/directory/*`, memakai app-client middleware, ability check, API audit, dan rate limit.

### Users
- Method/path:
  - `GET /api/v1/internal/directory/users`
  - `GET /api/v1/internal/directory/users/{id}`
- Required ability: `read:users`
- Filters:
  - `q`
  - `username`
  - `identity_number`
  - `active`
  - `limit`
  - `page`
- Safe response:
  - id, name, email, username, identity_type, identity_number, active, roles, app access summary, timestamps.

### Students
- Method/path:
  - `GET /api/v1/internal/directory/students`
  - `GET /api/v1/internal/directory/students/{id}`
- Required ability: `read:students`
- Filters:
  - `q`
  - `nim`
  - `student_number`
  - `study_program_id`
  - `active`
  - `status`
  - `limit`
  - `page`
- Safe response:
  - id, user_id, nim/student_number, name, email, status, active, enrolled_at, study program summary, user summary.

### Lecturers
- Method/path:
  - `GET /api/v1/internal/directory/lecturers`
  - `GET /api/v1/internal/directory/lecturers/{id}`
- Required ability: `read:lecturers`
- Filters:
  - `q`
  - `nidn`
  - `nip`
  - `lecturer_number`
  - `department_id`
  - `study_program_id`
  - `active`
  - `limit`
  - `page`
- Safe response:
  - id, user_id, lecturer_number, nidn/nip alias, name, email, phone, active, department/study program summary, user summary.

### Employees
- Method/path:
  - `GET /api/v1/internal/directory/employees`
  - `GET /api/v1/internal/directory/employees/{id}`
- Required ability: `read:employees`
- Filters:
  - `q`
  - `employee_number`
  - `national_id_number`
  - `department_id`
  - `study_program_id`
  - `staff_type`
  - `status`
  - `limit`
  - `page`
- Safe response:
  - id, user_id, employee_number, national_id_number, name, staff_type, position_title, email, phone, status, department/study program summary, user summary.

### Study Programs
- Method/path:
  - `GET /api/v1/internal/directory/study-programs`
  - `GET /api/v1/internal/directory/study-programs/{id}`
- Required ability: `read:study-programs`
- Filters:
  - `q`
  - `code`
  - `department_id`
  - `active`
  - `limit`
  - `page`
- Safe response:
  - id, code, name, description, department_id, department_name, active, safe head lecturer summary.

### Departments
- Method/path:
  - `GET /api/v1/internal/directory/departments`
  - `GET /api/v1/internal/directory/departments/{id}`
- Required ability: `read:departments`
- Filters:
  - `q`
  - `code`
  - `active`
  - `limit`
  - `page`
- Safe response:
  - id, code, name, description, active.

## Security Confirmation
- Protected by app client: OK
- Ability check per endpoint: OK
- Missing ability returns safe 403: OK
- Invalid client remains safe 401: OK
- Rate limit active: OK
- Audit log active: OK
- No password/hash/token/secret: OK
- No birth_date default: OK
- No write operation: OK
- No SSO: OK
- No auto-login: OK
- No token URL: OK
- No KP/TU/SAFA changes: OK

## Documentation
Updated:
- `docs/CORE-INTERNAL-API.md`
  - added directory endpoint list, abilities, filters, pagination, safe field notes.
- `docs/CORE-CONSUMER-INTEGRATION-PLAN.md`
  - marked app-client directory endpoints ready.
  - updated KP/TU next-step sequence.
- `README.md`
  - added app-client directory/profile endpoints status.
- `docs/CORE-ARCHITECTURE-SUMMARY.md`
  - added app-client directory/profile read endpoints and updated roadmap.

No credentials, secrets, or passwords were added to documentation.

## Commands Run
- `php artisan optimize:clear` - OK
- `php artisan route:list` - OK, 84 routes
- `php artisan test` - OK, 159 passed / 797 assertions
- `php artisan migrate` - tidak dijalankan karena tidak ada migration baru
- `npm run build` - tidak dijalankan karena tidak ada perubahan frontend asset/CSS/JS

## Test Result
`php artisan test` berhasil:

- 159 tests passed
- 797 assertions

Coverage baru/relevan:
- app client with `read:users` can list users safely.
- app client without `read:users` gets 403.
- user directory does not contain password/token/secret/birth_date.
- students directory works with `read:students`.
- lecturers directory works with `read:lecturers`.
- employees directory works with `read:employees`.
- study programs directory works with `read:study-programs`.
- departments directory works with `read:departments`.
- limit capped at max 100.
- q search works.
- invalid client rejected.
- directory request audit log created.
- app access endpoint still works.
- leadership endpoint still works.
- existing user-token endpoint tests still pass.

## Manual/API Check
- users directory OK
- students directory OK
- lecturers directory OK
- employees directory OK
- study programs directory OK
- departments directory OK
- ability missing rejected OK
- invalid/revoked client rejected OK via existing credential tests
- safe fields only OK
- no 500 error OK

## Guardrails Confirmation
- Tidak menjalankan migrate:fresh.
- Tidak menjalankan migrate:reset.
- Tidak menjalankan migrate:rollback.
- Tidak drop database.
- Tidak mengubah data master otomatis.
- Tidak execute import.
- Tidak mengubah database KP/TU/SAFA.
- Tidak menyentuh KP/TU/SAFA.
- Tidak membuat Core public.
- Tidak membuat SSO/bypass login.
- Tidak membuat auto-login.
- Tidak membuat cross-app session.
- Tidak membuat token URL.
- Tidak expose password/hash/token/secret.
- Tidak expose birth_date default.
- Tidak membuat write API.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.

## Risks / Notes
- KP adapter belum dibuat.
- TU adapter belum dibuat.
- Production credentials belum issued.
- Consumer smoke test belum dilakukan.
- Directory endpoints memakai offset pagination sederhana; jika volume sangat besar, cursor pagination bisa dipertimbangkan nanti.
- Consumer app tetap harus menerapkan object-level authorization lokalnya sendiri.

## Recommended Next Step
Rekomendasi tahap berikutnya: **CORE-INTEGRATION-2B KP Read-Only Adapter Implementation**.

KP adapter sebaiknya default-off, memakai HTTP fake tests, dan hanya mengonsumsi endpoint app-client directory/read yang sudah dibuat pada tahap ini.
