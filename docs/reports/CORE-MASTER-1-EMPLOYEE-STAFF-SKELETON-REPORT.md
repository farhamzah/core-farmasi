# CORE-MASTER-1 Employee/Staff Master Data Skeleton Report

Tanggal pengerjaan: 2026-05-23

## Scope

Tahap CORE-MASTER-1 membuat skeleton master data `employees` untuk tendik, admin, staf TU, laboran, dan pegawai non-dosen lain.

Perubahan bersifat additive dan non-destruktif:

- Membuat tabel baru `employees`.
- Membuat model `Employee`.
- Menambahkan relasi Employee ke User, Department, dan StudyProgram.
- Menambahkan Filament Resource `Tendik / Staff`.
- Menambahkan test akses dan relasi.

Yang tidak dikerjakan pada tahap ini:

- Tidak mengubah flow password.
- Tidak membuat username/identity fields di `users`.
- Tidak membuat import Excel.
- Tidak membuat app shortcut.
- Tidak membuat SSO.
- Tidak membuat API baru.
- Tidak membuat user otomatis dari employee.

## Previous Reports Reviewed

Report yang dibaca:

- `docs/reports/CORE-MASTER-0-ARCHITECTURE-PLANNING-REPORT.md`
- `docs/reports/CORE-UI-1-ASSET-LOGIN-FIX-REPORT.md`
- `docs/reports/CORE-UI-0-PLANNING-INSPECTION-REPORT.md`

## Files Changed

File dibuat:

- `database/migrations/2026_05_23_000112_create_employees_table.php`
- `app/Models/Employee.php`
- `database/factories/EmployeeFactory.php`
- `app/Filament/Resources/EmployeeResource.php`
- `app/Filament/Resources/EmployeeResource/Pages/ListEmployees.php`
- `app/Filament/Resources/EmployeeResource/Pages/CreateEmployee.php`
- `app/Filament/Resources/EmployeeResource/Pages/EditEmployee.php`
- `tests/Feature/EmployeeResourceTest.php`
- `docs/reports/CORE-MASTER-1-EMPLOYEE-STAFF-SKELETON-REPORT.md`

File diubah:

- `app/Models/User.php`
- `app/Models/Department.php`
- `app/Models/StudyProgram.php`
- `tests/Feature/CoreAdminAccessTest.php`

README tidak diubah karena report ini sudah mencatat hasil tahap dengan cukup jelas.

## Database Changes

Migration baru:

- `2026_05_23_000112_create_employees_table.php`

Tabel baru:

- `employees`

Field:

- `id`
- `user_id` nullable, unique, foreign key ke `users`, `nullOnDelete`
- `employee_number` nullable, unique
- `national_id_number` nullable, indexed
- `name`
- `staff_type`
- `department_id` nullable, foreign key ke `departments`, `nullOnDelete`
- `study_program_id` nullable, foreign key ke `study_programs`, `nullOnDelete`
- `position_title` nullable
- `phone` nullable
- `email` nullable
- `birth_date` nullable date
- `gender` nullable
- `address` nullable text
- `status` default `active`
- `notes` nullable text
- `created_at`, `updated_at`
- `deleted_at` via soft deletes

Index/unique:

- Unique `user_id`.
- Unique `employee_number`.
- Index `national_id_number`.
- Index `staff_type`.
- Index `status`.

Alasan additive dan non-destruktif:

- Migration hanya membuat tabel baru.
- Tidak mengubah tabel existing.
- Tidak menghapus kolom/data.
- Foreign key nullable memakai `nullOnDelete`, sehingga deletion user/department/study program tidak cascade-delete employee record.

## Model Changes

Model baru:

- `App\Models\Employee`

Trait:

- `HasFactory`
- `SoftDeletes`

Fillable:

- `user_id`
- `employee_number`
- `national_id_number`
- `name`
- `staff_type`
- `department_id`
- `study_program_id`
- `position_title`
- `phone`
- `email`
- `birth_date`
- `gender`
- `address`
- `status`
- `notes`

Casts:

- `birth_date` as `date`
- `deleted_at` as `datetime`

Relasi Employee:

- `user()` belongsTo `User`
- `department()` belongsTo `Department`
- `studyProgram()` belongsTo `StudyProgram`

Relasi balik yang ditambahkan:

- `User::employee()` hasOne `Employee`
- `Department::employees()` hasMany `Employee`
- `StudyProgram::employees()` hasMany `Employee`

Factory:

- `EmployeeFactory` dibuat untuk kebutuhan test/data generation tahap berikutnya.
- Factory tidak membuat user otomatis dan tidak membuat password.

## Filament Resource

Resource baru:

- `App\Filament\Resources\EmployeeResource`

Navigation:

- Group: `Master Data`
- Label: `Tendik / Staff`
- Plural label: `Tendik / Staff`
- Icon: `heroicon-o-identification`
- Sort: `40`

Routes:

- `GET /admin/employees`
- `GET /admin/employees/create`
- `GET /admin/employees/{record}/edit`

Table columns:

- `employee_number`
- `name`
- `staff_type` badge
- `department.name`
- `studyProgram.name`
- `position_title`
- `email`
- `phone`
- `status` badge
- `user.email`
- `created_at`

Filters:

- `staff_type`
- `status`
- `department_id`
- `study_program_id`

Form sections:

- `Identitas Staff`
  - `name` required
  - `employee_number` nullable unique
  - `national_id_number` nullable
  - `staff_type` required
  - `status` required
  - `birth_date` nullable
  - `gender` nullable
- `Penempatan`
  - `department_id` relationship select
  - `study_program_id` relationship select
  - `position_title` nullable
- `Kontak`
  - `email` nullable, email validation
  - `phone` nullable
  - `address` nullable
- `Akun Terhubung`
  - `user_id` nullable, unique relationship select
  - `notes` nullable

Validation:

- `name` required.
- `staff_type` required.
- `status` required.
- `email` must be valid email if filled.
- `employee_number` unique nullable in `employees`.
- `user_id` unique nullable in `employees`.

Security/access:

- Resource lives under Filament admin panel.
- No public route added.
- Access remains controlled by existing `User::canAccessPanel()`.

## Security Confirmation

Konfirmasi:

- EmployeeResource tidak public.
- `/admin` tetap protected.
- `canAccessPanel()` tidak dilonggarkan.
- Role access existing tidak dilemahkan.
- Tidak ada SSO.
- Tidak ada app shortcut.
- Tidak ada auto-create user/password.
- Tidak ada password field di `employees`.
- Tidak ada perubahan login/password flow.
- Tidak ada secret/credential hardcoded.

## Commands Run

Command yang dijalankan:

- `php artisan optimize:clear`
  - Result: success.
  - Cache/config/routes/views/blade-icons/filament cleared.

- `php artisan migrate`
  - Result: success.
  - Migration `2026_05_23_000112_create_employees_table` ran.

- `php artisan test`
  - Result: success.
  - Final result: 30 tests passed, 97 assertions.

- `php artisan route:list --path=employees`
  - Result: success.
  - Routes for employee index/create/edit registered under `/admin/employees`.

- `php artisan migrate:status --path=database\migrations\2026_05_23_000112_create_employees_table.php`
  - Result: success.
  - Migration status: `Ran`, batch `2`.

- Local manual guest check with `php artisan serve --host=127.0.0.1 --port=8013`
  - `/admin/employees`: HTTP 302 to `/admin/login`.
  - `/admin/employees/create`: HTTP 302 to `/admin/login`.
  - Temporary server stopped after check.

Command not run:

- `npm run build`
  - Alasan: tidak ada perubahan frontend asset, CSS, JS, atau Vite input pada tahap ini.

## Test Result

`php artisan test` result terakhir:

- Tests: 30 passed.
- Assertions: 97.
- Duration: 13.41s.

Test baru mencakup:

- `employees` table exists with expected columns.
- Super-admin can open EmployeeResource index.
- Super-admin can open EmployeeResource create and edit pages.
- Admin-core can open EmployeeResource index.
- User without Core admin role cannot open EmployeeResource index.
- Employee relationships to User, Department, and StudyProgram work.

## Manual Check

Checklist:

- Menu Tendik/Staff muncul di Core admin untuk authorized user: OK via Filament resource discovery/routes and authorized resource tests.
- Employee index bisa dibuka: OK via test for `super-admin` and `admin-core`.
- Form create/edit tampil: OK via test for create and edit pages.
- Unauthorized user tetap ditolak: OK via test for non-core role.
- Guest tetap diarahkan ke login: OK via local HTTP check, 302 to `/admin/login`.
- Tidak ada 500 error: OK via test and route checks.

## Guardrails Confirmation

Konfirmasi:

- Tidak menjalankan `migrate:fresh`.
- Tidak menjalankan `migrate:reset`.
- Tidak menjalankan `migrate:rollback`.
- Tidak drop database.
- Tidak menghapus data.
- Tidak execute import KP.
- Tidak mengubah database KP.
- Tidak menyentuh `apps/safa-ubp`.
- Tidak menyentuh `apps/kp-farmasi`.
- Tidak menyentuh `apps/tu-farmasi`.
- Tidak membuat Core public.
- Tidak membuat SSO/bypass login.
- Tidak membuat app shortcut.
- Tidak membuat import Excel.
- Tidak mengubah flow password.
- Tidak membuat user otomatis dari employee.

## Risks / Notes

Sisa pekerjaan:

- Identity fields di `users` belum dibuat.
- Username/login identifier belum dibuat.
- Password awal tanggal lahir belum dibuat.
- Forced password change belum dibuat.
- Import Excel belum dibuat.
- App shortcut belum dibuat.
- API internal belum dibuat.
- Data quality dashboard belum dibuat.

Risiko/catatan:

- `birth_date` sudah tersedia di `employees` untuk kebutuhan desain auth berikutnya, tetapi belum dipakai untuk password.
- `national_id_number` hanya indexed, bukan unique, sesuai scope tahap ini.
- `email` di `employees` nullable dan tidak unique agar tidak bentrok dengan `users.email` dan variasi data real.
- `employee_number` unique nullable; jika nanti ada banyak identifier seperti NIP/NIK/staff_code, CORE-AUTH-1 perlu memutuskan identity model lebih formal.
- Bulk delete masih mengikuti pola resource existing, tetapi soft deletes membuat record employee tidak langsung hilang permanen.

## Recommended Next Step

Rekomendasi tahap berikutnya:

- `CORE-AUTH-1`: username, identity, initial password, and must-change-password skeleton.

Scope yang disarankan:

- Additive fields di `users`.
- Login identifier strategy.
- `must_change_password` skeleton.
- No plaintext password export.
- Tests untuk admin access dan user inactive tetap aman.
