# TA-CORE-CONNECT-0 Core Read-Only Adapter Report

## Scope
Tahap ini memverifikasi dan melengkapi skeleton adapter read-only TA ke Core internal API. Adapter default disabled, tidak melakukan cutover, tidak mengganti auth TA, tidak membuat SSO/token/session, dan tidak melakukan write-back ke Core.

## Previous Reports Reviewed
- `docs/reports/CORE-TA-LAB-CONNECT-0-READINESS-PACKAGE-REPORT.md`
- `docs/CORE-TA-CONNECTION-PACKAGE.md`
- `docs/CORE-INTERNAL-API.md`
- `docs/CORE-CONSUMER-INTEGRATION-PLAN.md`
- `docs/CORE-ARCHITECTURE-SUMMARY.md`
- `docs/CORE-APP-CLIENT-CREDENTIAL-SOP.md`

## Files Changed
- `apps/ta-farmasi/tests/Feature/CoreFarmasiHttpAdapterTest.php`
- `apps/ta-farmasi/docs/CORE-HTTP-ADAPTER-READONLY.md`
- `apps/core-farmasi/docs/CORE-CONSUMER-INTEGRATION-PLAN.md`
- `apps/core-farmasi/docs/reports/TA-CORE-CONNECT-0-READONLY-ADAPTER-REPORT.md`

Existing TA adapter files verified:
- `apps/ta-farmasi/config/core_farmasi.php`
- `apps/ta-farmasi/.env.example`
- `apps/ta-farmasi/app/Services/CoreFarmasiClient.php`
- `apps/ta-farmasi/app/Console/Commands/TaCoreSmokeTestCommand.php`

## TA Config
- `TA_CORE_HTTP_ENABLED=false` by default.
- `TA_CORE_READ_MODE=disabled` by default.
- `TA_CORE_APP_CODE=ta-farmasi`.
- `TA_CORE_CLIENT_ID` and `TA_CORE_CLIENT_SECRET` are placeholders only.
- no real secret in `.env.example`.
- profile URL is configurable by `TA_CORE_PROFILE_URL` and can fall back to `TA_CORE_BASE_URL/profile`.

## TA Core Client
Service:
- `App\Services\CoreFarmasiClient`

Methods verified:
- `enabled()`
- `profileUrl()`
- `getUser($id)`
- `searchUsers(array $params = [])`
- `getStudent($id)`
- `searchStudents(array $params = [])`
- `getLecturer($id)`
- `searchLecturers(array $params = [])`
- `getEmployee($id)`
- `searchEmployees(array $params = [])`
- `getStudyProgram($id)`
- `listStudyPrograms(array $params = [])`
- `getDepartment($id)`
- `listDepartments(array $params = [])`
- `getCurrentLeadership(array $params = [])`
- `checkUserAppAccess($userId)`

Headers:
- `X-Core-App-Code`
- `X-Core-Client-Id`
- `X-Core-Client-Secret`
- `Accept: application/json`

Timeout/fail-safe:
- uses configured timeout/connect timeout.
- disabled mode sends no HTTP.
- 401/403/429/500 are handled safely.
- connection errors are handled safely.
- fail silently remains default true.

## Endpoint Mapping
| TA method | Core endpoint |
| --- | --- |
| `searchUsers()` | `GET /api/v1/internal/directory/users` |
| `getUser($id)` | `GET /api/v1/internal/directory/users/{id}` |
| `searchStudents()` | `GET /api/v1/internal/directory/students` |
| `getStudent($id)` | `GET /api/v1/internal/directory/students/{id}` |
| `searchLecturers()` | `GET /api/v1/internal/directory/lecturers` |
| `getLecturer($id)` | `GET /api/v1/internal/directory/lecturers/{id}` |
| `searchEmployees()` | `GET /api/v1/internal/directory/employees` |
| `getEmployee($id)` | `GET /api/v1/internal/directory/employees/{id}` |
| `listStudyPrograms()` | `GET /api/v1/internal/directory/study-programs` |
| `getStudyProgram($id)` | `GET /api/v1/internal/directory/study-programs/{id}` |
| `listDepartments()` | `GET /api/v1/internal/directory/departments` |
| `getDepartment($id)` | `GET /api/v1/internal/directory/departments/{id}` |
| `getCurrentLeadership()` | `GET /api/v1/internal/leadership/current` |
| `checkUserAppAccess($userId)` | `GET /api/v1/internal/apps/ta-farmasi/users/{user}/access` |

## Smoke Command
Command:

```bash
php artisan ta:core-smoke-test
```

Behavior:
- read-only only.
- disabled mode exits safely without Core HTTP requests.
- no secret output.
- no DB write.
- checks study programs, students, lecturers, leadership, and app access if `--user-id` is provided.
- fake HTTP success path is covered by tests.

## Security Confirmation
- no SSO: OK
- no auto-login: OK
- no token URL: OK
- no cross-app session: OK
- no write-back: OK
- no auth replacement: OK
- default disabled: OK
- no real secret: OK
- no password/hash/token/secret exposure: OK
- no database changes: OK
- no Lab/KP/TU/SAFA changes: OK

## Tests
Tests updated:
- `tests/Feature/CoreFarmasiHttpAdapterTest.php`

Coverage:
- disabled client returns empty/null safely and sends no HTTP.
- enabled client sends required app-client headers.
- secret is not placed in URL.
- profile URL configured/fallback behavior.
- endpoint mapping for users/students/lecturers/employees/study programs/departments.
- leadership endpoint mapping.
- app access endpoint uses app_code `ta-farmasi`.
- query params mapped safely.
- 401/403/429/500 handled safely.
- connection exception handled safely.
- smoke command disabled mode safe.
- smoke command fake HTTP success safe.

## Commands Run
TA:
- `php artisan test --filter=CoreFarmasiHttpAdapterTest`
- `php artisan optimize:clear`
- `php artisan test`
- `php artisan test --filter=TaStudentPortalTest`
- `php artisan optimize:clear`
- `php artisan test`

Core:
- `php artisan test`

Migrations:
- not run

Real smoke:
- not run

## Test Result
TA:
- `php artisan test --filter=CoreFarmasiHttpAdapterTest`: 15 passed / 33 assertions.
- first full `php artisan test`: 1 transient Windows view cache rename error in existing `TaStudentPortalTest`, unrelated to adapter.
- `php artisan test --filter=TaStudentPortalTest`: 5 passed / 31 assertions after cache clear.
- final `php artisan test`: 179 passed / 642 assertions.

Core:
- `php artisan test`: 217 passed / 1075 assertions.

## Guardrails Confirmation
- Tidak menjalankan migration.
- Tidak menjalankan migrate:fresh/reset/rollback.
- Tidak drop database.
- Tidak mengubah database TA/Core.
- Tidak execute import.
- Tidak cutover.
- Tidak mengganti auth TA.
- Tidak membuat SSO/bypass login.
- Tidak membuat auto-login.
- Tidak membuat cross-app session.
- Tidak membuat token URL.
- Tidak menulis real secret.
- Tidak expose password/hash/token/secret.
- Tidak write-back ke Core.
- Tidak mengaktifkan integration default.
- Tidak mengubah default read mode dari disabled.
- Tidak menyentuh Lab/KP/TU/SAFA.
- Tidak membuat Core public.

## Recommended Next Step
- Issue TA API client in Core when staging credential is needed.
- Prepare TA staging smoke test plan/execution after credential exists.
- Or continue `LAB-CORE-CONNECT-0 Core Read-Only Adapter Skeleton in Lab`.
