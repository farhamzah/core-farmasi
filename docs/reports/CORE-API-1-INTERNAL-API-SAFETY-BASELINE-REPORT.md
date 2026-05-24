# CORE-API-1 Internal API Safety Baseline Report

## Scope
Tahap ini membuat planning dan baseline keamanan API internal Core Farmasi UBP. Fokusnya adalah review endpoint existing, safe response, protected internal endpoint minimal, rate limit baseline, dokumentasi, dan test.

Tahap ini bukan SSO, bukan auto-login, tidak membuat cross-app token URL, dan tidak membuat API publik untuk data sensitif.

## Previous Reports Reviewed
- CORE-ACCESS-2 Internal App Launcher Report
- CORE-ACCESS-1 Dynamic App Registry & App Role Catalog Report
- CORE-DQ-1 Data Quality Dashboard Report
- CORE-ORG-1 context melalui `CoreLeadershipResolver` dan `LeadershipAssignment`

## Existing API Review
Endpoint existing sebelum hardening:
- `GET /api/v1/health` - public health endpoint, risiko rendah.
- `POST /api/v1/auth/login` - login API existing, mengembalikan token plaintext satu kali dan safe user profile.
- `GET|POST /api/v1/auth/validate-token` - protected by `auth.api`.
- `GET /api/v1/users/{id}` - protected, sudah safe tetapi belum lewat sanitizer shared.
- `GET /api/v1/students/{id}` - protected, perlu menjaga `birth_date` tidak tampil.
- `GET /api/v1/lecturers/{id}` - protected, perlu menjaga `birth_date` tidak tampil.
- `GET /api/v1/study-programs` - protected.
- `GET /api/v1/study-programs/{id}` - protected.

Hardening yang dilakukan:
- Menambahkan throttle API dari config.
- Menambahkan safe response sanitizer shared.
- Mengubah user/student/lecturer/auth response agar memakai sanitizer.
- Menambahkan safe employee endpoint.
- Menambahkan endpoint internal app access check.
- Menambahkan endpoint internal current leadership.

Tidak menghapus endpoint existing.

## Config
File `config/core_api.php` dibuat.

Isi baseline:
- `version`: `v1`
- `default_rate_limit`: dari env `CORE_API_RATE_LIMIT`, default `60,1`
- `expose_birth_date`: false
- `expose_sensitive_fields`: false
- `app_access_required`: true
- `audit_sensitive_requests`: false
- safe response fields untuk user, student, lecturer, employee

Tidak ada secret/credential hardcoded.

## Safe Response/Transformer
`CoreApiResponseSanitizer` dibuat sebagai sanitizer sederhana untuk response API.

Safe payload:
- User safe fields: id, name, email, username, identity_type, identity_number, active, roles.
- Student safe fields: id, student_number, name, email, status, active, enrolled_at, study_program.
- Lecturer safe fields: id, lecturer_number, name, email, phone, active, department, study_program.
- Employee safe fields: id, employee_number, name, staff_type, position_title, email, phone, status, department, study_program.
- Leadership safe fields: position/unit/person summary and active dates.

Field yang tidak diexpose:
- password
- password hash
- remember_token
- api_token hash
- birth_date default
- sensitive internal metadata

## API Access Service
`CoreApiAccessService` dibuat untuk app access check.

Behavior:
- Validasi app aktif berdasarkan `CoreApplication`.
- Cek `UserAppAccess` aktif untuk user dan `app_code`.
- Mengembalikan role aplikasi aktif dalam bentuk slug/name.
- Requesting user hanya boleh cek dirinya sendiri.
- Core admin (`super-admin` atau `admin-core`) boleh cek user lain.
- Inactive application menghasilkan `has_access = false`.

Tidak membuat SSO, redirect login, cookie lintas aplikasi, atau token URL.

## Endpoints Added/Updated
Updated:
- `POST /api/v1/auth/login`
  - Auth: public credential check existing.
  - Response: token one-time + safe user fields.
- `GET|POST /api/v1/auth/validate-token`
  - Auth: bearer token.
  - Response: safe user fields.
- `GET /api/v1/users/{id}`
  - Auth: bearer token.
  - Response: safe user fields.
- `GET /api/v1/students/{id}`
  - Auth: bearer token.
  - Response: safe student fields, no birth date.
- `GET /api/v1/lecturers/{id}`
  - Auth: bearer token.
  - Response: safe lecturer fields, no birth date.

Added:
- `GET /api/v1/employees/{id}`
  - Auth: bearer token.
  - Purpose: safe employee profile lookup.
  - Response: safe employee fields, no birth date.
- `GET /api/v1/internal/apps/{app_code}/users/{user}/access`
  - Auth: bearer token.
  - Purpose: check active app access and app roles.
  - Response: `has_access`, `app_code`, `user_id`, `roles`.
- `GET /api/v1/internal/leadership/current`
  - Auth: bearer token.
  - Purpose: resolve current leadership assignment.
  - Query: `position_type`, `unit_type`, `unit_id`, `date`.
  - Response: safe leadership summary.

All `/api/v1` routes use throttle baseline.

## Leadership Endpoint
`InternalLeadershipController` uses `CoreLeadershipResolver::getCurrentPosition()`.

Behavior:
- Validates `position_type` and `unit_type` from `config/core_leadership.php`.
- Finds active/current assignment for the requested date.
- Returns safe leadership fields only.
- Does not expose birth date, password, token, or sensitive profile metadata.

## Security Confirmation
- Internal endpoints protected by bearer token.
- Missing/invalid token returns 401.
- Cross-user app access check is forbidden unless requester is Core admin.
- Password/hash/token are not exposed in safe response.
- Birth date is hidden by default.
- No public sensitive API was added.
- No SSO.
- No auto-login.
- No cross-app token URL.
- API routes use throttle baseline.
- Object-level/app access considerations added for app access check.

## Documentation
`docs/CORE-INTERNAL-API.md` dibuat.

Isi:
- prinsip API internal
- auth
- rate limit
- safe fields
- endpoint list
- example response
- security notes
- no SSO/no auto-login/no token URL guidance
- future work

Tidak ada token/credential/password di dokumentasi.

## Commands Run
- `php artisan optimize:clear` - OK.
- `php artisan test --filter=CoreInternalApiTest` - OK, 6 passed / 44 assertions.
- `php artisan route:list` - OK, 68 routes shown including new internal API endpoints.
- `php artisan test` - OK, 130 passed / 570 assertions.

Tidak menjalankan `php artisan migrate` karena tidak ada migration baru.
Tidak menjalankan `npm run build` karena tidak ada perubahan frontend asset/CSS/JS build pipeline.

## Test Result
`php artisan test`: 130 passed / 570 assertions.

## Manual Check
- health OK: covered by existing API test.
- protected endpoint unauthorized rejected: OK.
- invalid token rejected: OK.
- valid request OK: OK.
- app access check OK: OK.
- leadership current OK: OK.
- response tidak mengandung password/hash/token: OK.
- birth_date tidak tampil default: OK.
- tidak ada 500 error: OK.

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
- Tidak membuat cross-app token URL.
- Tidak expose password/hash/token.
- Tidak expose birth_date default.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.

## Risks / Notes
- API masih internal baseline.
- SSO belum dibuat.
- App integration consumer belum diubah.
- API audit untuk endpoint sensitif belum diaktifkan.
- Token rotation/revocation perlu tahap khusus.
- App client credentials belum dibuat.

## Recommended Next Step
Rekomendasi tahap berikutnya: `CORE-API-2 Token Rotation / App Client Credentials Planning` jika API akan dipakai serius oleh aplikasi lain, atau `CORE-IMPORT-7 Users & App Access Import` jika prioritasnya bulk access management.
