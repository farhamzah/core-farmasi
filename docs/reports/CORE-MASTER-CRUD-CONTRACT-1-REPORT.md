# CORE-MASTER-CRUD-CONTRACT-1 Report

Tanggal audit: 2026-06-01

## Scope

Audit dilakukan hanya pada `apps/core-farmasi`.

Tidak dilakukan:

- perubahan database.
- `migrate:fresh`, reset, rollback, drop table, atau delete data.
- import execute atau rollback import.
- perubahan pada KP, TU, TA, Lab, SAFA.
- commit atau push.
- penulisan secret.

## Apa Yang Dicek

- Runtime Core.
- Migration status.
- Admin route Filament.
- API route.
- Core command namespace.
- Filament resources untuk manual CRUD.
- Excel Import Center.
- Profile Portal.
- Model ownership dan relasi master data.
- Secret/file safety dasar.
- Test suite Core.
- Dokumen arsitektur yang perlu ditautkan.

## Command Yang Dijalankan

```bash
php artisan about
php artisan optimize:clear
php artisan migrate:status
php artisan route:list --path=admin --method=GET
php artisan route:list --path=api
php artisan list core
php artisan core:app-connection-readiness lab-farmasi
php artisan core:app-connection-readiness ta-farmasi
php artisan core:app-connection-readiness helpdesk-farmasi
php artisan core:tu-connection-readiness
php artisan core:ta-app-readiness
php artisan core:lab-app-readiness
php artisan test
git ls-files .env storage database/*.sql database/*.sqlite vendor node_modules
```

Catatan: ada satu race condition saat beberapa command Artisan dijalankan paralel dan Laravel mencoba menulis cache `packages.php` bersamaan. Command readiness kemudian dijalankan ulang secara sekuensial dan berhasil.

## Hasil Validasi

| Area | Hasil |
| --- | --- |
| Runtime | OK. App `Core Farmasi UBP`, Laravel `12.60.2`, Filament `v5.6.5`, MySQL, timezone `Asia/Jakarta`. |
| Storage link | `public/storage` linked. |
| Migration status | OK. Migration Core utama dan import/audit/app registry sudah ran. |
| Admin routes | OK. 44 GET admin routes ditemukan. |
| API routes | OK. 29 API routes ditemukan. |
| Core commands | OK. Namespace `core` berisi readiness, import, rollback, TU/TA/Lab tooling. |
| App readiness | KP/TU/TA/Lab/Helpdesk readiness command dapat membaca registry; beberapa app belum punya API client dan itu expected sesuai tahap. |
| Test | OK, `220 passed, 1130 assertions`. |
| Sensitive tracked files | `.env`, dump database, sqlite, vendor, node_modules tidak terdeteksi tracked; hanya placeholder `.gitignore` storage yang tracked. |

## Manual CRUD Status

Manual CRUD utama tersedia melalui Filament:

- Users.
- Roles.
- Departments.
- Study Programs.
- Students.
- Lecturers.
- Employees.
- Core Applications.
- Core Application Roles.
- User App Accesses.
- Leadership Assignments.
- Core API Clients.

Read-only by design:

- User Activity Logs.
- Core API Request Logs.
- Data Quality Dashboard.

Workflow-managed, bukan standalone CRUD:

- Core Import Batches.
- Core Import Records.

## Excel Import Status

Excel Import Center tersedia dan mengaktifkan template:

- users.
- students.
- lecturers.
- employees.
- departments.
- study_programs.
- roles.
- user_role_assignments.
- user_app_accesses.

Test mencakup preview, validation, decision, execute, dan rollback safety.

## Profile Portal Status

Profile Portal tersedia:

- `/profile`
- `/profile/edit`
- `PUT /profile`
- `/profil-saya`

Fungsi yang sudah ada:

- authenticated self-service.
- non-admin tetap tidak bisa masuk `/admin`.
- student/lecturer/employee summary.
- safe contact update.
- proteksi agar user tidak mengubah profil user lain.

## File Dibuat/Diubah

Dibuat:

- `docs/CORE-CENTRAL-DATA-CONTRACT.md`
- `docs/CORE-MASTER-CRUD-MATRIX.md`
- `docs/reports/CORE-MASTER-CRUD-CONTRACT-1-REPORT.md`

Diubah:

- `README.md`
- `docs/CORE-ARCHITECTURE-SUMMARY.md`
- `docs/CORE-CONSUMER-INTEGRATION-PLAN.md`
- `docs/CORE-CROSS-APP-HANDOFF.md`

## Keputusan

Core sudah memenuhi baseline sebagai pusat master data:

- manual CRUD admin tersedia.
- Excel Import Center tetap tersedia.
- Profile Portal tersedia.
- Central data contract terdokumentasi.
- batas data Core-owned dan app-owned terdokumentasi.

Status: **AMAN LANJUT KE TAHAP BERIKUTNYA**.

## Risiko / Todo

- Production cutover consumer app tetap perlu approval dan smoke test terpisah.
- Consumer baru harus mulai read-only.
- API client secret harus dibuat/stored di luar repo.
- Bulk delete pada resource master harus dipakai sangat hati-hati karena Core adalah source of truth.
- Import batch/record tidak punya standalone resource; saat ini intentionally dikelola via Import Center.
