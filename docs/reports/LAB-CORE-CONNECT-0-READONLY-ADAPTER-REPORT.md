# LAB-CORE-CONNECT-0 Core Read-Only Adapter Report

## Scope
Tahap ini membuat skeleton adapter HTTP read-only Lab Farmasi ke Core Farmasi. Scope meliputi config Lab, service client, command smoke test, dokumentasi Lab, update kecil rencana consumer Core, dan tests berbasis `Http::fake`. Tahap ini default disabled, bukan real connection, bukan real credential issuance, bukan smoke test nyata, bukan cutover, dan bukan perubahan database.

## Previous Reports Reviewed
- `CORE-TA-LAB-CONNECT-0-READINESS-PACKAGE-REPORT.md`
- `CORE-LAB-CONNECTION-PACKAGE.md`
- `CORE-INTERNAL-API.md`

## Files Changed
- `apps/lab-farmasi/config/core_farmasi.php`
- `apps/lab-farmasi/app/Services/CoreFarmasiClient.php`
- `apps/lab-farmasi/app/Console/Commands/LabCoreSmokeTestCommand.php`
- `apps/lab-farmasi/bootstrap/app.php`
- `apps/lab-farmasi/.env.example`
- `apps/lab-farmasi/tests/Feature/CoreFarmasiHttpAdapterTest.php`
- `apps/lab-farmasi/docs/CORE-HTTP-ADAPTER-READONLY.md`
- `apps/core-farmasi/docs/CORE-CONSUMER-INTEGRATION-PLAN.md`
- `apps/core-farmasi/docs/reports/LAB-CORE-CONNECT-0-READONLY-ADAPTER-REPORT.md`

## Lab Config
- `LAB_CORE_HTTP_ENABLED=false` by default.
- `LAB_CORE_READ_MODE=disabled` for HTTP adapter default posture.
- `LAB_CORE_APP_CODE=lab-farmasi`.
- `LAB_CORE_PROFILE_URL` supports Core Profile Portal link.
- No real client ID or client secret is written.

## Lab Core Client
Created `App\Services\CoreFarmasiClient` with:
- safe disabled behavior returning null/empty values.
- app-client headers `X-Core-App-Code`, `X-Core-Client-Id`, `X-Core-Client-Secret`.
- timeout/connect timeout/SSL verify config.
- safe handling for 401, 403, 404, 429, 500, and connection errors.
- no token URL and no secret in URL.

Methods:
- `enabled`
- `profileUrl`
- `getUser`
- `searchUsers`
- `getStudent`
- `searchStudents`
- `getLecturer`
- `searchLecturers`
- `getEmployee`
- `searchEmployees`
- `getStudyProgram`
- `listStudyPrograms`
- `getDepartment`
- `listDepartments`
- `getCurrentLeadership`
- `checkUserAppAccess`

## Endpoint Mapping
- `getUser` -> `GET /api/v1/internal/directory/users/{id}`
- `searchUsers` -> `GET /api/v1/internal/directory/users`
- `getStudent` -> `GET /api/v1/internal/directory/students/{id}`
- `searchStudents` -> `GET /api/v1/internal/directory/students`
- `getLecturer` -> `GET /api/v1/internal/directory/lecturers/{id}`
- `searchLecturers` -> `GET /api/v1/internal/directory/lecturers`
- `getEmployee` -> `GET /api/v1/internal/directory/employees/{id}`
- `searchEmployees` -> `GET /api/v1/internal/directory/employees`
- `getStudyProgram` -> `GET /api/v1/internal/directory/study-programs/{id}`
- `listStudyPrograms` -> `GET /api/v1/internal/directory/study-programs`
- `getDepartment` -> `GET /api/v1/internal/directory/departments/{id}`
- `listDepartments` -> `GET /api/v1/internal/directory/departments`
- `getCurrentLeadership` -> `GET /api/v1/internal/leadership/current`
- `checkUserAppAccess` -> `GET /api/v1/internal/apps/lab-farmasi/users/{user}/access`

## Smoke Command
Created:

```bash
php artisan lab:core-smoke-test
```

Behavior:
- read-only only.
- exits safely when disabled.
- prints config presence without secrets.
- calls departments, study programs, students, lecturers, employees, leadership, and optional app access check.
- `--user-id` enables user app access check.
- no DB write, no migration, no auth change, no secret output.

## Security Confirmation
- no SSO: OK
- no auto-login: OK
- no token URL: OK
- no write-back: OK
- no auth replacement: OK
- default disabled: OK
- no database changes: OK
- no real secret: OK
- no password/hash/token/secret exposure: OK
- no Lab production cutover: OK

## Tests
Added `tests/Feature/CoreFarmasiHttpAdapterTest.php` covering:
- disabled client no HTTP call.
- required app-client headers.
- no secret in URL.
- endpoint mapping for users/students/lecturers/employees/study programs/departments/leadership/app access.
- 401/403/429/500 safe handling.
- connection exception safe handling.
- smoke command disabled safe.
- smoke command fake HTTP success safe.

## Commands Run
- `php -l config/core_farmasi.php`
- `php -l app/Services/CoreFarmasiClient.php`
- `php -l app/Console/Commands/LabCoreSmokeTestCommand.php`
- `php -l tests/Feature/CoreFarmasiHttpAdapterTest.php`
- `php artisan optimize:clear` in Lab
- `php artisan test` in Lab
- `php artisan lab:core-smoke-test` in disabled-safe mode
- `php artisan test` in Core
- migrations not run

## Test Result
- Lab `php artisan test`: 122 passed / 462 assertions.
- Core `php artisan test`: 217 passed / 1075 assertions.
- Lab `php artisan lab:core-smoke-test`: disabled-safe, no Core HTTP request sent, no secret output.

## Guardrails Confirmation
- Tidak menjalankan migration.
- Tidak menjalankan migrate:fresh/reset/rollback.
- Tidak drop database.
- Tidak mengubah database Lab/Core.
- Tidak execute import.
- Tidak cutover.
- Tidak mengganti auth Lab.
- Tidak membuat SSO/bypass login.
- Tidak membuat auto-login.
- Tidak membuat cross-app session.
- Tidak membuat token URL.
- Tidak menulis real secret.
- Tidak expose password/hash/token/secret.
- Tidak write-back ke Core.
- Tidak mengaktifkan integration default.
- Tidak mengubah runtime production flow Lab.
- Tidak menyentuh TA/KP/TU/SAFA.
- Tidak membuat Core public.

## Recommended Next Step
- Issue Lab API client in Core only when staging credential handling is ready.
- Run Lab staging smoke test with real staging URL/secret manager.
- Continue next app/helpdesk planning if Lab credential is not ready yet.
