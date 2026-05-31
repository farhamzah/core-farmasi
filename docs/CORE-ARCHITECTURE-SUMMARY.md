# Core Farmasi Architecture Summary

## Purpose
Core Farmasi UBP adalah aplikasi internal yang menjadi pusat data dan integrasi untuk aplikasi Farmasi UBP. Core dirancang sebagai Master Data Center, Identity Center, Role & App Access Center, Excel Import Center, Internal Integration Center, Audit Center, dan Data Quality Center.

Kontrak data pusat dan matriks manual CRUD terbaru:
- `docs/CORE-CENTRAL-DATA-CONTRACT.md`
- `docs/CORE-MASTER-CRUD-MATRIX.md`

## Module Overview
### Identity
- `users` menyimpan akun canonical.
- Username, identity type, identity number, must-change-password, password timestamps, dan reset metadata tersedia.
- Password selalu hashed.
- Change password flow tersedia untuk Core admin.
- Initial password berbasis `birth_date` hanya temporary dan tidak pernah disimpan plaintext.
- Profile Portal tersedia di `/profile` untuk authenticated user melihat profil dirinya sendiri, mengubah safe contact fields, dan melihat completion summary.
- Profile Portal tidak membuka akses `/admin` untuk user non-admin.

### Master Data
- `students` untuk mahasiswa.
- `lecturers` untuk dosen.
- `employees` untuk tendik, admin, staf TU, laboran, dan staff non-dosen lain.
- `departments` dan `study_programs` sebagai unit akademik.
- Profil utama mahasiswa/dosen/tendik/staf/laboran dikelola terpusat di Core; aplikasi consumer menampilkan data ini secara read-only.

### Access Control
- Role global tetap di `roles`.
- Role per aplikasi disimpan di `core_application_roles`.
- Aplikasi internal disimpan di `core_applications`.
- Assignment akses user per aplikasi disimpan di `user_app_accesses`.
- App role bersifat dynamic/configurable sehingga aplikasi baru dan role baru tidak perlu hardcode di banyak tempat.

### Leadership
- Jabatan resmi tidak disamakan dengan role login.
- `leadership_assignments` menyimpan Dekan, Wakil Dekan, Kaprodi, Sekretaris Prodi, Kepala Lab, Koordinator KP, dan jabatan lain.
- Resolver mengambil assignment aktif berdasarkan position, unit, dan tanggal.
- `study_programs.head_lecturer_id` tetap quick reference dan tidak dihapus.

### Import Center
Import lifecycle:
1. Template download.
2. Private upload.
3. Heading validation.
4. Row validation and conflict detection.
5. Admin decision UI.
6. Execute import for students/lecturers/employees.
7. Execute import for users, global role assignments, and user app accesses.
8. Rollback/undo safety.

Import safety:
- Upload private/local.
- No public file URL.
- Password column rejected/ignored.
- App role columns in profile import are not executed as app access.
- App/global roles and applications are not auto-created by import.
- Invalid/skip rows are not executed.
- Row execution uses per-row transaction.
- Rollback avoids unsafe hard delete and uses manual review when metadata is incomplete.

### Data Quality
`CoreDataQualityDashboard` is read-only.

It monitors:
- users without role/app access
- missing username/identity
- duplicate identifiers
- profiles without user/birth date
- inactive users with active app access
- unknown app/role references
- leadership gaps
- import failed/manual review status

No auto-fix is performed.

### Internal App Launcher
`CoreAppLauncher` shows internal apps available to the current Core admin based on active app access.

It is only a protected shortcut:
- no SSO
- no auto-login
- no cross-app token
- no public listing

### Internal API
Internal API baseline includes:
- safe response sanitizer
- protected user-token endpoints
- protected app-client endpoints
- app access check
- current leadership resolver endpoint
- app-client directory/profile read endpoints
- employee safe endpoint
- app client credentials with rotate/revoke
- per-client rate limit
- safe request audit logs
- log retention and pruning command

App client credentials:
- stored in `core_api_clients`
- `secret_hash` stores hash only
- secret shown once on create/rotate
- revoked/inactive clients are rejected
- token/secret in URL is rejected

API request logs:
- stored in `core_api_request_logs`
- do not store request body, full headers, authorization header, password, token, or secret
- pruned through `core:prune-api-request-logs` with dry-run/force safety

## Data Model Overview
Core domain tables include:
- `users`
- `roles`
- `user_roles`
- `students`
- `lecturers`
- `employees`
- `departments`
- `study_programs`
- `leadership_assignments`
- `core_applications`
- `core_application_roles`
- `user_app_accesses`
- `core_import_batches`
- `core_import_records`
- `core_api_clients`
- `core_api_request_logs`
- `user_activity_logs`

## Auth & Password Policy
- `/admin/login` is the official Core admin login.
- `/admin` remains protected.
- `User::canAccessPanel()` requires active user and Core admin role.
- `/profile` is a protected self-service profile route for authenticated users and does not grant admin panel access.
- `must_change_password` redirects Core admin users to change password.
- Initial password generation uses birth date format from config, hashes immediately, and marks `must_change_password=true`.
- Password plaintext is not stored, logged, reported, exported, or shown in API response.

## Centralized Profile Portal
Planning docs:
- `docs/CORE-CENTRALIZED-PROFILE-PORTAL-PLAN.md`

Profile Portal principles:
- Core owns the canonical profile.
- Other apps should display profile data read-only and link users to Core for profile updates.
- Users can only see/edit their own profile.
- Safe contact updates are supported for `phone` and `address` on student, lecturer, and employee profiles.
- Profile completion shows safe indicators such as linked profile, email, phone, address, and whether birth date is recorded without exposing full birth date.
- Official identity fields, NIM/NIDN/NIP/employee number, program/department, status, roles, app access, and leadership are admin-only.
- Profile changes are audited by action and changed field names without storing password/token/secret data.

## App-Specific Roles
Global role examples:
- super-admin
- admin-core
- mahasiswa
- dosen
- employee

App-specific role examples:
- kp-farmasi: pembimbing-dalam, penguji, admin-kp
- tu-farmasi: admin-tu, validator, penandatangan
- dossier-dosen: reviewer, validator

App-specific roles are not identity categories and are not official positions.

## Leadership Assignments
Official positions are resolved from active leadership assignments, not login roles.

Examples:
- Current Dean: `position_type=dekan`, `unit_type=faculty`.
- Current Kaprodi: `position_type=kaprodi`, `unit_type=study_program`, `unit_id=<study_program_id>`.

Multiple active assignments are resolved by latest start date, and data quality dashboard flags suspicious conditions.

## API Internal
API docs:
- `docs/CORE-INTERNAL-API.md`

Internal app-to-app headers:
- `X-Core-Client-Id`
- `X-Core-Client-Secret`
- `X-Core-App-Code`

API non-goals:
- no SSO
- no auto-login
- no token URL
- no public sensitive directory
- no default `birth_date` exposure

API operations:
- app client secrets are hashed
- rotate/revoke is available
- internal app-client requests are audited safely
- rate limit is applied per client/app code
- audit log pruning uses cutoff retention and does not touch master data

## Guardrails & Non-Goals
- Core is not public.
- Core does not appear in SAFA public portal.
- No SSO yet.
- No cross-app session.
- No auto-login.
- No token/secret in URL.
- No password/hash/token/secret exposure.
- No self-service role/app-access/status/official-identity edits.
- No automatic data quality fix.
- No unsafe hard delete during rollback.
- No KP/TU/SAFA code changes from Core stages.

## Current Recommended Roadmap
1. CORE-INTEGRATION-4B Real Staging Smoke Test Execution after KP/TU staging app client credentials are available.
2. CORE-QA-3 Cross-App Final Regression and Handoff.
3. Profile field cutover planning for KP/TU only after staging smoke and owner approval.
4. Operational monitoring for API usage, API log retention, import rollback, and data quality review.
