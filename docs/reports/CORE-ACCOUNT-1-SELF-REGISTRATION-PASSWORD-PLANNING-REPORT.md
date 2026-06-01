# CORE-ACCOUNT-1 Self Registration, Profile Completion & Central Password Planning Report

Tanggal: 2026-06-01

## Scope

Planning dan audit gap untuk self registration/account request, profile completion, centralized password, app access approval, dan consumer app login policy di `apps/core-farmasi`.

Tahap ini tidak membuat registrasi aktif penuh dan tidak mengubah logic aplikasi.

## Files Audited

- `routes/web.php`
- `app/Http/Controllers/ProfilePortalController.php`
- `app/Services/CoreProfilePortalService.php`
- `app/Filament/Pages/ChangePassword.php`
- `app/Models/User.php`
- `app/Models/Student.php`
- `app/Models/Lecturer.php`
- `app/Models/Employee.php`
- `app/Filament/Resources/UserResource.php`
- `app/Filament/Resources/UserAppAccessResource.php`
- `app/Http/Controllers/Api/TuPortalAuthVerificationController.php`
- `docs/CORE-CENTRAL-DATA-CONTRACT.md`
- `docs/CORE-CONSUMER-INTEGRATION-PLAN.md`
- `docs/CORE-ARCHITECTURE-SUMMARY.md`

## Current State Findings

Core already supports:

- Canonical `users` with hashed password.
- `must_change_password`, password timestamps, and password reset metadata.
- Admin-only `/admin` via `User::canAccessPanel()` for active `super-admin` or `admin-core`.
- Profile Portal routes:
  - `GET /profile`
  - `GET /profile/edit`
  - `PUT /profile`
  - `/profil-saya` redirect.
- Safe profile contact update for `phone`, `address`, and `alternate_email` if available.
- Profile completion summary.
- Admin password change page under `/admin/change-password`.
- User app access managed by Core admin through `UserAppAccessResource`.
- TU password verification endpoint that checks Core password and app access without token/session.

Current gaps:

- No active self-registration/account-request route.
- No pending account request lifecycle table/resource.
- No Profile Portal password change route for non-admin users yet.
- No generic consumer credential verification endpoint for all apps yet.

## Planning Output

Created:

- `docs/CORE-ACCOUNT-LIFECYCLE-PLAN.md`

The plan covers:

- account sources.
- self-registration policy.
- admin approval.
- profile portal.
- password changes.
- app access assignment.
- consumer app login policy.
- non-goals.
- roadmap.

## Account Lifecycle Decision

Recommended lifecycle:

1. `imported/admin-created`
2. `self-registered pending`
3. `verified/active`
4. `rejected`
5. `inactive`

Recommended storage for future self-registration:

- Prefer separate `account_requests` table.
- Do not auto-activate `users`.
- Do not auto-create app access.
- Do not auto-grant sensitive role.

## Registration Types

Planned registration types:

- `mahasiswa`
- `dosen`
- `tendik/staf/laboran`

Each type needs identity matching and duplicate checks before approval.

## Profile Completion Policy

Current editable fields are safe and should remain:

- phone.
- address.
- alternate_email if available.

Forbidden self-edit fields:

- NIM.
- NIDN/NIP.
- employee number.
- identity number.
- prodi.
- department.
- status.
- role.
- app access.
- leadership/jabatan.

## Central Password Policy

Decision:

- Password Core is canonical.
- Password change in Core should apply to consumer apps that verify credential against Core.
- Password must remain hashed only.
- No plaintext password in import template, report, log, or API response.

Current gap:

- Non-admin self password change should be added to Profile Portal in a later stage.

## App Access Policy

Decision:

- App access remains admin-controlled.
- Self-registration must never grant app access automatically.
- `user_app_accesses` remains the authority for per-app access.
- `core_application_roles` remains app role catalog.

## Consumer Login Policy

Planned policy:

- No SSO.
- No auto-login.
- No token URL.
- Consumer app verifies credential through Core app-client protected endpoint.
- Core checks active user, valid password, and active app access.
- Consumer app creates its own local session only after safe Core verification.

Current implementation:

- TU has specific portal password verification endpoint.

Recommended future:

- Design generic endpoint:
  - `POST /api/v1/internal/apps/{app_code}/auth/verify`
- Keep response safe and generic on failure.

## Security Confirmation

- `/admin` remains admin-only.
- Profile Portal remains self-only.
- No SSO created.
- No auto-login created.
- No token URL created.
- No automatic app access created.
- No password changed.
- No migration run.
- No consumer app touched.

## Commands Run

```bash
php artisan test
```

## Test Result

- `php artisan test`: `225 passed, 1268 assertions`.

## Guardrails

- Tidak membuat registrasi aktif penuh.
- Tidak membuka `/admin` untuk non-admin.
- Tidak membuat SSO.
- Tidak membuat auto-login.
- Tidak membuat token URL.
- Tidak memberi app access otomatis.
- Tidak mengubah password user existing.
- Tidak menjalankan migration.
- Tidak menyentuh KP/TU/TA/Lab/SAFA.

## Recommended Next Step

1. Review and approve `docs/CORE-ACCOUNT-LIFECYCLE-PLAN.md`.
2. If approved, implement `CORE-ACCOUNT-2` as account request skeleton with additive migration.
3. Implement Profile Portal password change for non-admin users before consumer-wide Core password cutover.
4. Design generic consumer auth verification after account lifecycle policy is locked.
