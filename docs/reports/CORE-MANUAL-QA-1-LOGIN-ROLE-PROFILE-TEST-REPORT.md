# CORE-MANUAL-QA-1 Login, Role & Profile Manual QA Report

Tanggal: 2026-06-01

## Scope

Manual QA preparation for admin/user login, role assignment, app-specific access, and Core Profile Portal.

Perubahan hanya dilakukan di `apps/core-farmasi`.

## URLs

- Admin: `http://127.0.0.1:8000/admin/login`
- Profile Portal: `http://127.0.0.1:8000/profile`
- Change Password: `http://127.0.0.1:8000/profile/change-password`

Server lokal terdeteksi hidup di PID `17396`, sehingga tidak dibuat server duplikat.

## Command

Command baru:

```bash
php artisan core:manual-qa-accounts
```

Dry-run behavior:

- Menampilkan admin candidate.
- Menampilkan planned QA users.
- Menampilkan planned app access policy.
- Menampilkan URL manual test.
- Tidak menulis database.

Apply behavior:

```bash
php artisan core:manual-qa-accounts --apply --reset-admin-password --create-users --assign-app-access --show-credentials
```

- Hanya apply di `local` atau `testing`, kecuali `--force-env` dipakai secara eksplisit.
- Membuat/update admin QA.
- Membuat/update user QA mahasiswa, dosen, tendik.
- Membuat/update profile student, lecturer, employee.
- Mengisi password hashed.
- Men-set `must_change_password=true` untuk user QA non-admin.
- Mengassign app access hanya jika app dan role catalog sudah ada.
- Tidak membuat app/role baru.
- Tidak menghapus data.

Credential hanya dicetak di terminal/final response untuk manual QA lokal. Credential tidak ditulis di report.

## QA Accounts

| Type | User ID | Username | Email | Identity Type | must_change_password | Global Roles |
| --- | ---: | --- | --- | --- | --- | --- |
| Admin | 1 | `admin` | `admin@core-farmasi.local` | `admin` | false | `super-admin` |
| Mahasiswa | 17 | `20260001` | `mahasiswa.qa@core-farmasi.local` | `student` | true | none |
| Dosen | 18 | `0012345678` | `dosen.qa@core-farmasi.local` | `lecturer` | true | none |
| Tendik | 19 | `TENDIK001` | `tendik.qa@core-farmasi.local` | `employee` | true | none |

Profiles:

- Student: `student_number=20260001`.
- Lecturer: `lecturer_number=0012345678`.
- Employee: `employee_number=TENDIK001`, `staff_type=tendik`.

## Admin Role Rules

- `super-admin` / `admin-core` can access `/admin`.
- Non-admin QA users do not receive `super-admin` or `admin-core`.
- Non-admin QA users should use `/profile`.

## App Access Rules

Assigned app access:

| User ID | App | Role | Active |
| ---: | --- | --- | --- |
| 17 | `tu-farmasi` | `mahasiswa` | true |
| 17 | `ta-farmasi` | `mahasiswa` | true |
| 17 | `lab-farmasi` | `mahasiswa` | true |
| 18 | `tu-farmasi` | `dosen` | true |
| 18 | `ta-farmasi` | `dosen-pembimbing` | true |
| 18 | `lab-farmasi` | `dosen` | true |
| 19 | `tu-farmasi` | `staf-tu` | true |
| 19 | `lab-farmasi` | `laboran` | true |

Manual assignment:

1. Admin login.
2. Open `User App Accesses`.
3. Click `Create`.
4. Select user.
5. Fill `app_code`.
6. Fill `role_slug`.
7. Set `is_active=true`.
8. Save.

## Manual Test Checklist

- Admin login.
- Admin dashboard opens.
- Admin opens user CRUD.
- Admin creates user manually.
- Admin creates student/lecturer/employee manually.
- Admin opens Import Center.
- Admin opens Roles.
- Admin assigns User App Access.
- User QA logs into Profile Portal.
- User QA sees own profile.
- User QA changes password.
- User QA edits phone/address.
- Non-admin `/admin` is blocked.
- Password/hash/token/secret is not visible.

## Security

- No password hash exposed.
- No secret exposed.
- No mass assignment beyond named QA accounts.
- No app changes.
- No SSO.
- No token URL.
- No app role/app registry creation by QA command.
- No deletion.
- No commit/push.

## Validation

- `php artisan optimize:clear`: OK.
- `php artisan about`: OK, local environment, MySQL, timezone Asia/Jakarta.
- `php artisan route:list --path=admin --method=GET`: OK, 46 routes.
- `php artisan route:list --path=profile`: OK, 5 routes.
- `php artisan core:manual-qa-accounts`: OK, dry-run, no write.
- `php artisan core:manual-qa-accounts --apply --reset-admin-password --create-users --assign-app-access --show-credentials`: OK.
- `php artisan test --filter=CoreManualQaAccountsCommandTest`: OK, 7 passed / 46 assertions.
- `php artisan test`: OK, 254 passed / 1430 assertions.

## Guardrails Confirmation

- Tidak menjalankan `migrate:fresh/reset/rollback`.
- Tidak drop database.
- Tidak menghapus data.
- Tidak execute import.
- Tidak mengubah aplikasi lain.
- Tidak membuat SSO.
- Tidak membuat token URL.
- Tidak expose password hash/token/secret.
- Tidak commit.
- Tidak push.
