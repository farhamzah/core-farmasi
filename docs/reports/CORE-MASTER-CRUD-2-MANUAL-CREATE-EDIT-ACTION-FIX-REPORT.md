# CORE-MASTER-CRUD-2 Manual Create/Edit Action Fix Report

Tanggal: 2026-06-01

## Scope

Manual CRUD create/edit action audit and fix untuk `apps/core-farmasi`.

Tidak menyentuh aplikasi lain, tidak menjalankan migration destructive, tidak menghapus data, tidak execute import, tidak reset password, tidak membuat user baru, tidak commit, dan tidak push.

## Context

Ringkasan hasil `CORE-LOCAL-RUN-ROLE-LOGIN-TEST`:

- DB OK, MySQL `core_farmasi_ubp` terbaca.
- Server lokal: `http://127.0.0.1:8000`.
- Server PID: `17396`.
- Global roles: 13.
- App-specific roles: 50.
- User app accesses: 30.
- Admin candidate: user_id `1`, email `admin@core-farmasi.local`.
- Core Admin roles: `super-admin`, `admin-core`.
- User `admin@sikp.test` hanya `admin-kp`, sehingga tidak bisa masuk Core Admin tanpa global role `admin-core`/`super-admin`.

## Problem

- Di browser, halaman Core Admin Users menampilkan tabel tetapi tombol tambah/create/new tidak terlihat.
- Route create/edit sebenarnya tersedia, tetapi sebagian besar `List*` page resource master belum mendefinisikan header `CreateAction`.
- Core harus mendukung input manual satu-satu melalui CRUD, bukan hanya Excel Import Center.

## Resources Audited

| Resource | Index route | Create route | Edit route | Create action visible | Edit action visible | Status |
| --- | --- | --- | --- | --- | --- | --- |
| UserResource | OK | OK | OK | Fixed/OK | OK | Manual CRUD ready |
| RoleResource | OK | OK | OK | Fixed/OK | OK | Manual CRUD ready |
| StudentResource | OK | OK | OK | Fixed/OK | OK | Manual CRUD ready |
| LecturerResource | OK | OK | OK | Fixed/OK | OK | Manual CRUD ready |
| EmployeeResource | OK | OK | OK | Fixed/OK | OK | Manual CRUD ready |
| DepartmentResource | OK | OK | OK | Fixed/OK | OK | Manual CRUD ready |
| StudyProgramResource | OK | OK | OK | Fixed/OK | OK | Manual CRUD ready |
| CoreApplicationResource | OK | OK | OK | Fixed/OK | OK | Manual CRUD ready |
| CoreApplicationRoleResource | OK | OK | OK | Fixed/OK | OK | Manual CRUD ready |
| UserAppAccessResource | OK | OK | OK | Fixed/OK | OK | Manual CRUD ready |
| LeadershipAssignmentResource | OK | OK | OK | Fixed/OK | OK | Manual CRUD ready |
| CoreApiClientResource | OK | OK | OK | Already OK | OK | Manual CRUD ready |
| UserActivityLogResource | OK | No create route | No edit route | Read-only | Read-only | Correct |
| CoreApiRequestLogResource | OK | No create route | No edit route | Read-only | Read-only | Correct |
| Data Quality Dashboard | OK | Not applicable | Not applicable | Read-only | Read-only | Correct |
| Import batches/records | Import Center | Workflow-managed | Workflow-managed | Workflow-managed | Workflow-managed | Correct |

## Fixes Applied

Added Filament header `CreateAction` to master resource list pages:

- `app/Filament/Resources/UserResource/Pages/ListUsers.php`
- `app/Filament/Resources/StudentResource/Pages/ListStudents.php`
- `app/Filament/Resources/LecturerResource/Pages/ListLecturers.php`
- `app/Filament/Resources/EmployeeResource/Pages/ListEmployees.php`
- `app/Filament/Resources/DepartmentResource/Pages/ListDepartments.php`
- `app/Filament/Resources/StudyProgramResource/Pages/ListStudyPrograms.php`
- `app/Filament/Resources/RoleResource/Pages/ListRoles.php`
- `app/Filament/Resources/CoreApplicationResource/Pages/ListCoreApplications.php`
- `app/Filament/Resources/CoreApplicationRoleResource/Pages/ListCoreApplicationRoles.php`
- `app/Filament/Resources/UserAppAccessResource/Pages/ListUserAppAccesses.php`
- `app/Filament/Resources/LeadershipAssignmentResource/Pages/ListLeadershipAssignments.php`

`CoreApiClientResource` already had `CreateAction`, so no change was needed there.

## Read-only Resources

These remain intentionally read-only:

- `UserActivityLogResource`.
- `CoreApiRequestLogResource`.
- Data Quality Dashboard.
- Import batches/records outside the Import Center workflow.

No create/edit route or action was added to log resources.

## Browser / Render Check

Authenticated render checks were added in `tests/Feature/CoreManualCrudResourceTest.php`.

The test verifies:

- Core admin can access all manual create pages.
- Core admin index pages render links to each create route.
- Core admin can access edit pages.
- Non-admin users are forbidden from create pages.
- Guests are redirected to `/admin/login`.
- Logs have no create route.
- Sensitive implementation fields like `secret_hash`, `api_token`, and `remember_token` are not rendered on checked create/edit pages.

Status:

- Users create button/link: OK.
- Students create button/link: OK.
- Lecturers create button/link: OK.
- Employees create button/link: OK.
- Departments create button/link: OK.
- Study Programs create button/link: OK.
- App Registry create button/link: OK.
- App Roles create button/link: OK.
- User App Access create button/link: OK.
- Leadership create button/link: OK.
- API Clients create action: OK.

Manual browser login was not performed because no password was requested, displayed, changed, or guessed. The local server remains available for the owner to login with existing local admin credentials.

## Security Confirmation

- Admin-only: OK.
- Non-admin blocked: OK.
- Guest redirected: OK.
- No role access loosening: OK.
- `User::canAccessPanel()` unchanged: OK.
- No password/hash/token/secret exposure: OK.
- No SSO: OK.
- No token URL: OK.
- Profile Portal remains self-only by existing tests: OK.

## Commands Run

```bash
php artisan route:list --path=admin --method=GET
php artisan optimize:clear
php artisan test --filter=CoreManualCrudResourceTest
php artisan test
```

`npm.cmd run build` was not run because no frontend asset/CSS/JS build output was changed.

## Test Result

- Focused test: `5 passed, 138 assertions`.
- Full suite: `225 passed, 1268 assertions`.

## Guardrails Confirmation

- Tidak menjalankan `migrate:fresh/reset/rollback`.
- Tidak drop database.
- Tidak menghapus data.
- Tidak execute import.
- Tidak rollback import.
- Tidak mengubah password user.
- Tidak membuat user baru.
- Tidak membuka `/admin` untuk non-admin.
- Tidak expose secret/password/hash/token.
- Tidak menyentuh app lain.
- Tidak commit.
- Tidak push.

## Recommended Next Step

- Owner dapat login manual ke `http://127.0.0.1:8000/admin/login` memakai credential admin lokal yang sudah ada.
- Cek halaman Users dan resource master lain; tombol create/new seharusnya muncul untuk admin Core.
- Jika UI sudah sesuai, lanjut commit/push patch Core.
