# CORE-ACCOUNT-2 Account Request Skeleton Report

Tanggal: 2026-06-01

## Scope

Account request pending approval skeleton di `apps/core-farmasi`.

Tahap ini membuat public request form dan admin review resource, tetapi tidak membuat user aktif otomatis, tidak membuat app access otomatis, dan tidak mengubah auth admin.

## Files Changed

Dibuat:

- `database/migrations/2026_06_01_000123_create_account_requests_table.php`
- `app/Models/AccountRequest.php`
- `app/Services/CoreAccountRequestService.php`
- `app/Http/Controllers/AccountRequestController.php`
- `app/Filament/Resources/AccountRequestResource.php`
- `app/Filament/Resources/AccountRequestResource/Pages/ListAccountRequests.php`
- `app/Filament/Resources/AccountRequestResource/Pages/EditAccountRequest.php`
- `resources/views/account-request/create.blade.php`
- `resources/views/account-request/success.blade.php`
- `tests/Feature/CoreAccountRequestTest.php`
- `docs/reports/CORE-ACCOUNT-2-ACCOUNT-REQUEST-SKELETON-REPORT.md`

Diubah:

- `routes/web.php`
- `docs/CORE-ACCOUNT-LIFECYCLE-PLAN.md`

## Database Changes

Migration additive membuat tabel `account_requests`.

Fields:

- `request_type`
- `name`
- `email`
- `phone`
- `identity_number`
- `student_number`
- `lecturer_number`
- `employee_number`
- `study_program_id`
- `department_id`
- `requested_role`
- `requested_app_code`
- `status`
- `notes`
- `admin_notes`
- `reviewed_by`
- `reviewed_at`
- `approved_user_id`
- `submitted_ip`
- `submitted_user_agent`
- timestamps
- soft deletes

Tidak ada perubahan pada tabel `users`, `students`, `lecturers`, `employees`, `roles`, atau `user_app_accesses`.

## Public Routes

- `GET /account-request`
- `POST /account-request`
- `GET /account-request/success`
- `GET /register` redirect ke `/account-request`

Public route hanya menyimpan permohonan pending. Tidak membuat user, session, password, role, atau app access.

## Admin Resource

`AccountRequestResource` tersedia di Core Admin untuk user `super-admin` atau `admin-core`.

Fitur:

- list account requests.
- edit request metadata/admin notes/status.
- filter status, request type, requested app.
- action `Review` untuk status `in_review`.
- action `Reject` untuk status `rejected`.
- action `Approve Skeleton` untuk status `approved`.

Approve skeleton hanya menandai request approved dan menyimpan reviewer/admin notes. Belum membuat `User`, password, role, atau app access.

## Service

`CoreAccountRequestService` bertanggung jawab untuk:

- submit request.
- mark in review.
- reject.
- approve skeleton.
- duplicate detection summary:
  - email exists.
  - identity number exists.
  - student number exists.
  - lecturer number exists.
  - employee number exists.

Service tidak menangani password dan tidak membuat app access otomatis.

## Security

- No instant user activation: OK.
- No app access automatic: OK.
- No password field: OK.
- No SSO: OK.
- No auto-login: OK.
- No token URL: OK.
- Admin-only review: OK.
- Non-admin remains blocked from `/admin`: OK.
- No password/hash/token/secret output: OK.

## Commands Run

```bash
php artisan optimize:clear
php artisan migrate
npm.cmd run build
php artisan route:list
php artisan test
```

## Test Result

- `php artisan test`: PASS, 235 tests passed / 1326 assertions.
- `CoreAccountRequestTest`: PASS, 10 tests passed.
- `npm.cmd run build`: PASS.
- `php artisan route:list`: PASS, account request and admin review routes registered.

## Guardrails Confirmation

- Tidak menjalankan `migrate:fresh/reset/rollback`.
- Tidak drop database.
- Tidak menghapus data.
- Tidak membuat user aktif otomatis.
- Tidak memberi app access otomatis.
- Tidak mengubah password user existing.
- Tidak membuat SSO/bypass login.
- Tidak membuat auto-login.
- Tidak membuat token URL.
- Tidak membuka `/admin` untuk non-admin.
- Tidak menulis secret.
- Tidak mengubah KP/TU/TA/Lab/SAFA.

## Recommended Next Step

- CORE-ACCOUNT-3 Admin Approval Creates User Safely.
- CORE-PROFILE-4 Self-Service Change Password for Profile Portal.
