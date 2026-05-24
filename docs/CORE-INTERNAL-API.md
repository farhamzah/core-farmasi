# Core Farmasi Internal API

Dokumen ini menjelaskan baseline API internal Core Farmasi UBP. API ini disiapkan untuk integrasi aplikasi internal workspace, bukan untuk akses publik.

## Prinsip
- API internal harus protected, kecuali health check.
- Token tidak boleh dikirim melalui URL.
- Tidak ada SSO, auto-login, atau bypass guard aplikasi tujuan.
- Password plaintext, password hash, remember token, dan api token hash tidak boleh muncul di response.
- `birth_date` tidak diexpose secara default.
- Aplikasi consumer tetap wajib melakukan autentikasi/otorisasi lokalnya sendiri.
- Role global dan role aplikasi tetap dipisah.

## Auth
Endpoint protected menggunakan bearer token dari login API existing:

```http
Authorization: Bearer <token>
```

Token dibuat lewat:

```http
POST /api/v1/auth/login
```

Token yang disimpan di database tetap berbentuk hash. Jangan menulis token ke log, URL, report, atau file export.

## App Client Credentials
Endpoint server-to-server internal memakai app client credentials. Ini bukan SSO dan bukan user session lintas aplikasi.

Header:

```http
X-Core-Client-Id: <client-id>
X-Core-Client-Secret: <client-secret>
X-Core-App-Code: <app-code>
```

Aturan:
- `client_id` bukan secret.
- `client_secret` hanya ditampilkan sekali saat dibuat atau dirotasi.
- Secret disimpan sebagai hash di `core_api_clients.secret_hash`.
- Secret tidak boleh dikirim lewat query string.
- Client harus aktif, belum revoked, dan terkait aplikasi aktif.
- Header `X-Core-App-Code` harus sama dengan `app_code` client.
- Ability harus sesuai endpoint, misalnya `read:app-access` atau `read:leadership`.

Jangan menulis secret plaintext ke log, report, file export, atau screenshot.

## Rate Limit
Route API memakai throttle standar dari `config/core_api.php`.

Default:

```php
CORE_API_RATE_LIMIT=60,1
```

Nilai ini berarti 60 request per 1 menit untuk baseline saat ini.

Endpoint app-client internal juga memakai rate limit per client.

Default:

```php
CORE_API_CLIENT_RATE_LIMIT=120
CORE_API_CLIENT_RATE_WINDOW=60
```

Key limit internal memakai `client_id + app_code`. Jika credential header belum lengkap, fallback memakai IP address. Saat limit terlampaui, response aman:

```json
{
  "message": "Too Many Requests"
}
```

Status code:

```http
429 Too Many Requests
```

Limit per app atau per ability dapat ditambahkan di `config/core_api.php` tanpa menaruh secret di config.

## Audit Logging
Request app-client internal dicatat ke `core_api_request_logs`.

Dicatat:
- app_code
- client_id
- method
- path
- route name jika ada
- status code
- ability
- IP address
- user agent singkat
- request id/correlation id jika dikirim
- duration_ms
- success/error status aman

Tidak dicatat:
- request body
- full headers
- `Authorization`
- `X-Core-Client-Secret`
- password
- token
- secret
- stack trace

Log tersedia sebagai read-only admin resource di Core.

## Audit Log Retention
API request logs punya kebijakan retensi dan pruning terkontrol.

Default:

```php
CORE_API_LOG_RETENTION_DAYS=90
CORE_API_LOG_FAILED_RETENTION_DAYS=180
CORE_API_LOG_PRUNE_CHUNK_SIZE=1000
CORE_API_LOG_KEEP_RECENT_MINIMUM=1000
CORE_API_LOG_PRUNE_ENABLED=true
CORE_API_LOG_PRUNE_DRY_RUN=true
```

Dry-run wajib menjadi langkah pertama:

```bash
php artisan core:prune-api-request-logs --dry-run
```

Prune aktual harus eksplisit memakai `--force`:

```bash
php artisan core:prune-api-request-logs --force
```

Override retensi dapat dilakukan tanpa mengubah kode:

```bash
php artisan core:prune-api-request-logs --force --days=120
```

Aturan keamanan:
- pruning hanya menghapus baris `core_api_request_logs` yang lebih lama dari cutoff.
- log baru dipertahankan.
- failed/non-2xx request disimpan lebih lama secara default.
- API clients, users, role, app access, dan data master tidak dihapus.
- command tidak menampilkan secret, body request, atau header authorization.

Operasional production sebaiknya menjadwalkan dry-run/force sesuai SOP backup dan monitoring database. Scheduler otomatis belum diwajibkan pada tahap baseline ini.

## Safe Fields
User safe fields:
- id
- name
- email
- username
- identity_type
- identity_number
- active
- roles

Student safe fields:
- id
- student_number
- name
- email
- status
- active
- enrolled_at
- study_program

Lecturer safe fields:
- id
- lecturer_number
- name
- email
- phone
- active
- department
- study_program

Employee safe fields:
- id
- employee_number
- name
- staff_type
- position_title
- email
- phone
- status
- department
- study_program

Fields intentionally hidden by default:
- password
- password hash
- remember_token
- api_token hash
- birth_date
- sensitive internal metadata

## Endpoints
### Health
```http
GET /api/v1/health
```

Public health check.

Response:

```json
{
  "status": "ok"
}
```

### Login
```http
POST /api/v1/auth/login
```

Returns a bearer token and safe user profile for active users with valid credentials.

### Validate Token
```http
GET /api/v1/auth/validate-token
POST /api/v1/auth/validate-token
```

Protected by bearer token. Returns safe user profile.

### Safe User/Profile Read
Protected by bearer token:

```http
GET /api/v1/users/{id}
GET /api/v1/students/{id}
GET /api/v1/lecturers/{id}
GET /api/v1/employees/{id}
GET /api/v1/study-programs
GET /api/v1/study-programs/{id}
```

These endpoints return safe fields only. They do not return password, token hash, remember token, or birth date by default.

### App Access Check
```http
GET /api/v1/internal/apps/{app_code}/users/{user}/access
```

Protected by app client credentials.

Required ability:

```text
read:app-access
```

Purpose:
- Check whether a user has active access to an active application.
- Return app-specific roles for that application.

Rules:
- Requesting user may check their own access.
- Core admin users may check another user's access.
- Inactive app access is ignored.
- Inactive application returns `has_access: false`.

Response example:

```json
{
  "has_access": true,
  "app_code": "kp-farmasi",
  "user_id": 12,
  "roles": [
    {
      "slug": "pembimbing-dalam",
      "name": "Pembimbing Dalam"
    }
  ]
}
```

### TU Portal Password Verification
```http
POST /api/v1/internal/apps/tu-farmasi/portal-auth/verify
```

Protected by app client credentials.

Required ability:

```text
verify:tu-portal-auth
```

Purpose:
- Verify a TU portal login attempt against Core user password data.
- Confirm the Core user is active.
- Confirm the user has active `user_app_accesses` for `tu-farmasi`.
- Return a minimal safe identity payload for TU to build its local portal identity.

Rules:
- This endpoint is only for the `tu-farmasi` internal app-client flow.
- Failure response is intentionally generic for user not found, wrong password, inactive user, missing app access, or inactive app access.
- No token, session, SSO, auto-login, password change, user creation, or write-back is performed.
- Request body is not stored in Core API audit logs.
- Response never includes password, password hash, remember token, API token, client secret, or secret hash.

Request example:

```json
{
  "login": "user@example.test",
  "password": "plain-password-from-tu-login-form",
  "context": {}
}
```

Successful response example:

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

Supported login identifiers:
- email
- username
- identity_number
- student NIM (`student_number`)
- lecturer NIDN/NIP (`lecturer_number`)
- employee number (`employee_number`)

### Current Leadership
```http
GET /api/v1/internal/leadership/current?position_type=dekan&unit_type=faculty
```

Protected by app client credentials.

Required ability:

```text
read:leadership
```

Purpose:
- Resolve current official position such as Dekan or Kaprodi.
- Use active leadership assignment by date.

Response example:

```json
{
  "found": true,
  "leadership": {
    "position_type": "dekan",
    "position_title": "Dekan Fakultas Farmasi",
    "unit_type": "faculty",
    "unit_id": null,
    "person_type": "lecturer",
    "person_id": 5,
    "person_name": "Nama Dekan",
    "title_prefix": null,
    "title_suffix": null,
    "start_date": "2026-01-01",
    "end_date": null
  }
}
```

### App-Client Directory Endpoints
Directory endpoints memakai app client credentials, bukan bearer token user. Semua endpoint read-only, memakai safe fields, terkena rate limit per client, dan tercatat di API audit log.

Headers:

```http
X-Core-Client-Id: <client-id>
X-Core-Client-Secret: <client-secret>
X-Core-App-Code: <app-code>
```

Pagination:
- `limit` default 25.
- `limit` maksimal 100.
- `page` default 1.

Response list:

```json
{
  "data": [],
  "meta": {
    "page": 1,
    "limit": 25,
    "total": 0,
    "has_more": false
  }
}
```

Endpoints:

```http
GET /api/v1/internal/directory/users
GET /api/v1/internal/directory/users/{id}
```

Required ability: `read:users`

Filters:
- `q`
- `username`
- `identity_number`
- `active`

```http
GET /api/v1/internal/directory/students
GET /api/v1/internal/directory/students/{id}
```

Required ability: `read:students`

Filters:
- `q`
- `nim`
- `student_number`
- `study_program_id`
- `active`
- `status`

```http
GET /api/v1/internal/directory/lecturers
GET /api/v1/internal/directory/lecturers/{id}
```

Required ability: `read:lecturers`

Filters:
- `q`
- `nidn`
- `nip`
- `lecturer_number`
- `department_id`
- `study_program_id`
- `active`

```http
GET /api/v1/internal/directory/employees
GET /api/v1/internal/directory/employees/{id}
```

Required ability: `read:employees`

Filters:
- `q`
- `employee_number`
- `national_id_number`
- `department_id`
- `study_program_id`
- `staff_type`
- `status`

```http
GET /api/v1/internal/directory/study-programs
GET /api/v1/internal/directory/study-programs/{id}
```

Required ability: `read:study-programs`

Filters:
- `q`
- `code`
- `department_id`
- `active`

```http
GET /api/v1/internal/directory/departments
GET /api/v1/internal/directory/departments/{id}
```

Required ability: `read:departments`

Filters:
- `q`
- `code`
- `active`

Directory safe fields do not include password, password hash, remember token, API token hash, API client secret, secret hash, or `birth_date` by default.

## What Not To Do
- Jangan membuat SSO dari endpoint ini.
- Jangan memakai token di query string.
- Jangan memakai client secret di query string.
- Jangan membuat signed login URL lintas aplikasi.
- Jangan expose password, hash, token, atau birth date default.
- Jangan expose client secret hash.
- Jangan menjadikan app launcher sebagai public portal.
- Jangan mengambil data user tanpa object-level authorization di consumer app.

## Consumer Setup Notes
Consumer app seperti KP/TU harus memakai feature flag dan default-off saat mulai integrasi.

Rekomendasi env placeholder:

```env
CORE_FARMASI_ENABLED=false
CORE_FARMASI_BASE_URL=
CORE_FARMASI_APP_CODE=kp-farmasi
CORE_FARMASI_CLIENT_ID=
CORE_FARMASI_CLIENT_SECRET=
CORE_FARMASI_TIMEOUT=5
```

Aturan:
- Simpan real secret di environment/secret manager, bukan repository.
- Jangan kirim client secret lewat URL.
- Jangan mengganti auth consumer dengan Core API.
- Jangan menganggap app launcher sebagai SSO.
- Jika Core API unavailable, consumer harus fallback ke legacy/local mode atau empty result sesuai feature flag.
- App-client endpoint yang siap untuk smoke test awal adalah app access check, current leadership, dan directory read-only endpoints.
- Consumer tetap harus memakai feature flag dan fallback lokal/legacy saat mengaktifkan adapter.

## Future Work
- Object-level authorization policy yang lebih granular.
- Real staging smoke test for KP/TU HTTP read-only adapters.
- Monitoring/alerting untuk API audit log dan anomali rate limit.
- Dashboard usage per client/app/ability.
- Rate limit lanjutan per ability dengan monitoring.
- Operational SOP untuk secret rotation dan revocation.
