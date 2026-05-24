# CORE-DQ-1 Data Quality Dashboard Report

## Scope
Tahap ini membuat Data Quality Dashboard read-only untuk Core Farmasi UBP. Dashboard hanya membaca, menghitung, dan menampilkan ringkasan kualitas data utama tanpa auto-fix, tanpa mutasi data master, tanpa import execute baru, dan tanpa API baru.

## Previous Reports Reviewed
- CORE-IMPORT-6 Rollback / Undo Import Safety Report
- CORE-ACCESS-1 Dynamic App Registry & App Role Catalog Report
- CORE-ORG-1 Leadership Assignments Skeleton context melalui model dan struktur existing

## Files Changed
- `app/Services/CoreDataQualityService.php`
- `app/Filament/Pages/CoreDataQualityDashboard.php`
- `resources/views/filament/pages/core-data-quality-dashboard.blade.php`
- `tests/Feature/CoreDataQualityDashboardTest.php`
- `docs/reports/CORE-DQ-1-DATA-QUALITY-DASHBOARD-REPORT.md`
- `README.md`

## Data Quality Service
`CoreDataQualityService` dibuat sebagai service read-only untuk menghitung summary kualitas data. Service ini mengembalikan array sederhana berisi metric dan contoh terbatas.

Metric group:
- Identity/User Quality
- Profile Link Quality
- App Access Quality
- Leadership Quality
- Import Quality

Service tidak menjalankan create, update, delete, auto-fix, import execute, reset password, atau perubahan app access.

## Dashboard Page
`CoreDataQualityDashboard` dibuat sebagai Filament page di admin panel dengan slug `/admin/data-quality`.

Navigation:
- Group: Data Quality
- Label: Data Quality

UI dashboard:
- Ringkasan read-only
- Section Identity & Users
- Section Master Profiles
- Section App Access
- Section Leadership
- Section Imports
- Metric cards
- Example lists terbatas untuk masalah penting

Dashboard tidak menampilkan password plaintext atau password hash.

## Metrics Implemented
### Identity/User Quality
- total users
- active users
- inactive users
- users without roles
- users without app access
- users with must change password
- users missing username
- users missing identity number
- duplicate user emails
- duplicate usernames
- duplicate identity numbers
- inactive users with active app access

### Profile Link Quality
- students total
- students without user
- students without birth date
- students without study program
- duplicate student NIM
- lecturers total
- lecturers without user
- lecturers without birth date
- duplicate lecturer NIDN
- duplicate lecturer email
- employees total
- employees without user
- employees without birth date
- duplicate employee number
- duplicate employee email

### App Access Quality
- total applications
- active applications
- public visible sensitive apps
- core public visible warning
- app roles total
- user app accesses total
- active user app accesses
- app accesses for inactive users
- app accesses with unknown app code
- app accesses with unknown role slug

### Leadership Quality
- active dean count
- current dean exists
- multiple current deans
- study programs without Kaprodi quick reference
- active Kaprodi assignments count
- leadership assignments expired but active
- leadership assignments without valid person

### Import Quality
- import batches total
- import batches failed
- import batches partially failed
- import batches manual review
- rollback manual review count
- recent import batches

## Security Confirmation
- Data Quality Dashboard protected di Filament admin panel.
- Tidak ada public route baru.
- Tidak ada auto-fix.
- Tidak ada mutasi data master.
- Tidak ada import execute baru.
- Tidak ada password plaintext atau hash di output service.
- Tidak ada SSO.
- Tidak ada app shortcut/launcher.
- Tidak ada API baru.
- `canAccessPanel` tidak dilonggarkan.
- Login resmi tetap `/admin/login`.

## Commands Run
- `php artisan optimize:clear` - OK.
- `php artisan test --filter=CoreDataQualityDashboardTest` - OK, 7 passed / 37 assertions.
- `php artisan test` - OK, 114 passed / 498 assertions.

Tidak menjalankan `php artisan migrate` karena tidak ada migration baru pada tahap ini.
Tidak menjalankan `npm run build` karena tidak ada perubahan frontend asset/CSS/JS build pipeline.

## Test Result
`php artisan test`: 114 passed / 498 assertions.

## Manual Check
- Data Quality Dashboard bisa dibuka authorized user: OK, diverifikasi lewat feature test HTTP `/admin/data-quality`.
- Guest diarahkan ke login: OK.
- Unauthorized user ditolak: OK.
- Counts tampil: OK.
- No 500 error: OK.
- No data mutation: OK.

## Guardrails Confirmation
- Tidak menjalankan migrate:fresh.
- Tidak menjalankan migrate:reset.
- Tidak menjalankan migrate:rollback.
- Tidak drop database.
- Tidak mengubah data master otomatis.
- Tidak auto-fix data.
- Tidak execute import KP.
- Tidak mengubah database KP.
- Tidak menyentuh SAFA/KP/TU.
- Tidak membuat Core public.
- Tidak membuat SSO/bypass login.
- Tidak membuat app shortcut/launcher.
- Tidak membuat API baru.
- Tidak bulk reset password.
- Tidak expose/export password plaintext/hash.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.

## Risks / Notes
- Data quality saat ini read-only.
- Auto-fix belum dibuat.
- Export data quality belum dibuat.
- Internal API belum dibuat.
- App launcher belum dibuat.
- Import users/app access belum dibuat.

## Recommended Next Step
Rekomendasi tahap berikutnya: `CORE-ACCESS-2 Internal App Launcher without SSO` jika owner ingin navigasi internal antar aplikasi lebih cepat, atau `CORE-IMPORT-7 Users & App Access Import Planning` jika prioritasnya melanjutkan import identity/app access.
