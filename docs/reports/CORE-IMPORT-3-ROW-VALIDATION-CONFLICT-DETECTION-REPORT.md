# CORE-IMPORT-3 Row Validation & Conflict Detection Report

Tanggal pengerjaan: 2026-05-23

## Scope

Tahap CORE-IMPORT-3 menambahkan row validation dan conflict detection untuk import:

- `students`
- `lecturers`
- `employees`

Tahap ini belum membuat:

- Import execute penuh.
- Create/update/delete data master dari upload.
- Bulk create user.
- Bulk reset password.
- App shortcut.
- SSO.
- API baru.
- App role registry.
- Leadership assignment implementation.

Semua hasil pada tahap ini hanya berupa validasi, conflict detection, suggested action, UI preview, dan summary aman.

## Previous Reports Reviewed

Report yang dibaca:

- `docs/reports/CORE-IMPORT-1-IMPORT-CENTER-SKELETON-REPORT.md`
- `docs/reports/CORE-IMPORT-2-UPLOAD-HEADING-PREVIEW-REPORT.md`
- `docs/reports/CORE-ACCESS-ORG-0-IDENTITY-ROLES-LEADERSHIP-PLANNING-REPORT.md`
- `docs/reports/CORE-AUTH-3-IDENTITY-BIRTH-DATE-ALIGNMENT-REPORT.md`
- `docs/reports/CORE-MASTER-1-EMPLOYEE-STAFF-SKELETON-REPORT.md`

## Files Changed

File dibuat:

- `app/Services/CoreImportValidationService.php`
- `docs/reports/CORE-IMPORT-3-ROW-VALIDATION-CONFLICT-DETECTION-REPORT.md`

File diubah:

- `app/Filament/Pages/CoreImportCenter.php`
- `resources/views/filament/pages/core-import-center.blade.php`
- `tests/Feature/CoreImportCenterTest.php`

Tidak ada migration baru pada tahap ini.

## Validation Service

Service baru:

- `App\Services\CoreImportValidationService`

Import types yang didukung:

- `students`
- `lecturers`
- `employees`

Import types lain:

- `users`
- `departments`
- `study_programs`
- `roles`
- `user_role_assignments`
- `user_app_accesses`

Untuk import types di luar students/lecturers/employees, validation service mengembalikan pesan bahwa row validation belum tersedia.

Row result structure:

- `row_number`
- `identifier`
- `normalized_data`
- `errors`
- `warnings`
- `conflicts`
- `suggested_action`
- `is_valid`
- `can_import_later`

Suggested actions:

- `create_new`
- `needs_admin_decision`
- `invalid`

Catatan:

- `update_existing` belum dijadikan final action karena tahap ini belum memiliki admin decision UI.
- `skip` belum dipilih otomatis karena keputusan skip harus eksplisit di tahap conflict decision.

No master data mutation:

- Service tidak membuat/update/delete User, Student, Lecturer, Employee, Role, Department, StudyProgram, atau UserAppAccess.
- Service tidak membuat password.
- Service tidak menghitung initial password.
- Service tidak membuat app access.

## Student Validation

Required:

- `nim`
- `name`
- `study_program_code`

Optional:

- `email`
- `phone`
- `birth_date`
- `gender`
- `status`
- `username`
- `identity_number`

Rules:

- `nim` wajib.
- `name` wajib.
- `study_program_code` wajib.
- `study_program_code` harus cocok dengan StudyProgram existing.
- `email` harus valid jika diisi.
- `birth_date` harus valid jika diisi.
- `status` jika diisi harus `active` atau `inactive`.
- `gender` jika diisi harus `male` atau `female`.

Conflict detection:

- NIM sudah ada di `students`.
- Email sudah ada di `users`.
- Username sudah ada di `users`.
- Identity number sudah ada di `users`.
- Nama + birth date sama dengan data existing menjadi warning possible duplicate.
- Duplicate NIM di file upload menjadi warning.

Suggested action:

- Valid dan tidak conflict: `create_new`.
- Existing NIM/identity/email: `needs_admin_decision`.
- Required missing atau reference unknown: `invalid`.

## Lecturer Validation

Required:

- `name`

Optional:

- `nidn`
- `nip`
- `email`
- `phone`
- `birth_date`
- `department_code`
- `study_program_code`
- `status`
- `username`
- `identity_number`

Rules:

- `name` wajib.
- `email` harus valid jika diisi.
- `birth_date` harus valid jika diisi.
- `department_code` harus cocok dengan Department existing jika diisi.
- `study_program_code` harus cocok dengan StudyProgram existing jika diisi.
- `status` jika diisi harus `active` atau `inactive`.
- Jika `nidn`, `nip`, `email`, dan `identity_number` semua kosong, service memberi warning.

Conflict detection:

- NIDN/NIP sudah ada di `lecturers.lecturer_number`.
- Email sudah ada di `users`.
- Email sudah ada di `lecturers`.
- Username sudah ada di `users`.
- Identity number sudah ada di `users`.
- Nama + birth date sama dengan data existing menjadi warning possible duplicate.
- Duplicate NIDN/NIP di file upload menjadi warning.

Suggested action:

- Valid dan tidak conflict: `create_new`.
- Existing identifier/email/identity: `needs_admin_decision`.
- Name missing atau reference unknown: `invalid`.

## Employee Validation

Required:

- `name`
- `staff_type`

Optional:

- `employee_number`
- `national_id_number`
- `email`
- `phone`
- `birth_date`
- `department_code`
- `study_program_code`
- `position_title`
- `status`
- `username`
- `identity_number`

Rules:

- `name` wajib.
- `staff_type` wajib.
- `staff_type` harus salah satu:
  - `tendik`
  - `admin`
  - `staf_tu`
  - `laboran`
  - `other`
- `email` harus valid jika diisi.
- `birth_date` harus valid jika diisi.
- `department_code` harus cocok dengan Department existing jika diisi.
- `study_program_code` harus cocok dengan StudyProgram existing jika diisi.
- `status` jika diisi harus `active` atau `inactive`.

Conflict detection:

- `employee_number` sudah ada di `employees`.
- `national_id_number` sudah ada di `employees`.
- Email sudah ada di `users`.
- Email sudah ada di `employees`.
- Username sudah ada di `users`.
- Identity number sudah ada di `users`.
- Nama + birth date sama dengan data existing menjadi warning possible duplicate.
- Duplicate employee_number/national_id_number di file upload menjadi warning.

Suggested action:

- Valid dan tidak conflict: `create_new`.
- Existing employee_number/national_id_number/email/identity: `needs_admin_decision`.
- Required missing atau invalid staff_type/reference unknown: `invalid`.

## Password Handling

Behavior:

- Kolom yang mengandung `password` tidak diproses.
- Password tidak masuk `normalized_data`.
- Password tidak disimpan di batch summary validation.
- Password tidak ditampilkan di preview validation.
- Password plaintext tidak ditulis ke log/report.

Jika file upload mengandung password column:

- Heading preview menjadi invalid pada tahap heading validation.
- Validation service juga mengabaikan kolom password jika dipanggil langsung.

Birth date:

- `birth_date` hanya dianggap data profil.
- Tahap ini tidak menghitung initial password dari birth date.
- Initial password hanya akan dibahas pada tahap execute import nanti, tetap tanpa plaintext export.

## App Role Handling

Kolom berikut pada profile import tidak diproses sebagai app role:

- `app_code`
- `role_slug`
- `app_role`
- `app_access`
- `app_accesses`

Behavior:

- Kolom app role/app access dihapus dari `normalized_data`.
- Service memberi warning bahwa app role/app access tidak diproses pada profile import.
- Tidak membuat app access otomatis.
- Tidak membuat role baru otomatis.
- Tidak mengubah `user_app_accesses`.

Catatan desain:

- App-specific roles harus dynamic/configurable.
- Aplikasi baru seperti `dossier-dosen` dengan role `admin-dossier`, `reviewer`, `validator` tidak boleh mengharuskan hardcode banyak tempat.
- App role registry dan app access UI dikerjakan pada CORE-ACCESS-1.

## Import Center UI

Perubahan UI:

- Import Center tetap di `/admin/import-center`.
- Setelah upload dan heading preview, halaman menampilkan validation result jika import type didukung.

Summary cards:

- Checked.
- Valid.
- Invalid.
- Conflict.
- Warnings.
- Errors.

Validation table:

- Row number.
- Identifier.
- Status badge:
  - `valid`
  - `conflict`
  - `invalid`
- Suggested action.
- Errors summary.
- Warnings summary.
- Conflicts summary.

Pesan keamanan:

- UI menyatakan bahwa belum ada data yang diimport.
- Tahap ini hanya validasi dan deteksi konflik.

Unsupported import types:

- UI menampilkan pesan bahwa row validation belum tersedia untuk import type tersebut.

## Batch/Record Integration

`core_import_batches` digunakan.

Perubahan batch behavior:

- Jika validation berjalan, batch mode menjadi `validation`.
- Status:
  - `validated` untuk supported validation.
  - `validation_not_available` untuk import type yang belum didukung row validation.
  - `invalid_heading` tetap dipakai jika heading tidak valid.

Batch summary menyimpan:

- Heading preview.
- Missing required columns.
- Unknown columns.
- Password columns.
- Row count estimate.
- Preview errors/warnings.
- Validation summary aman.

`core_import_records` belum digunakan.

Alasan:

- Tahap ini belum admin decision UI.
- Row-level final records lebih tepat dibuat saat conflict decision/execute agar ada status final, chosen action, dan outcome.
- Existing schema `core_import_records` belum ideal untuk validation-only detail besar.

## Security Confirmation

Konfirmasi:

- Import Center protected.
- Validation protected.
- File tetap private/local.
- Tidak ada public route baru.
- Tidak ada import execute.
- Tidak ada data master berubah.
- Tidak ada plaintext password.
- Tidak ada SSO.
- Tidak ada app shortcut.
- Tidak ada app role assignment otomatis.
- Tidak ada API baru.
- Role access tidak dilonggarkan.
- Login resmi tetap `/admin/login`.

## Commands Run

Command yang dijalankan:

- `php artisan test --filter=CoreImportCenterTest`
  - Result: success.
  - 20 tests passed, 95 assertions.

- `php artisan optimize:clear`
  - Result: success.
  - Cache/config/routes/views/blade-icons/filament cleared.

- `php artisan test`
  - Result: success.
  - Final result: 69 tests passed, 273 assertions.

Command not run:

- `php artisan migrate`
  - Alasan: tidak ada migration baru.

- `npm run build`
  - Alasan: tidak ada perubahan frontend asset, CSS, JS, Vite input, atau public build.

## Test Result

`php artisan test` result terakhir:

- Tests: 69 passed.
- Assertions: 273.
- Duration: 42.58s.

Test baru/perluasan test mencakup:

- Students valid row menghasilkan `create_new`.
- Students missing NIM menghasilkan `invalid`.
- Students existing NIM menghasilkan conflict/`needs_admin_decision`.
- Students unknown study_program_code menghasilkan `invalid`.
- Lecturers valid row menghasilkan `create_new`.
- Lecturers missing name menghasilkan `invalid`.
- Lecturers existing NIDN menghasilkan conflict/`needs_admin_decision`.
- Lecturers unknown department_code menghasilkan `invalid`.
- Employees valid row menghasilkan `create_new`.
- Employees invalid staff_type menghasilkan `invalid`.
- Employees existing employee_number menghasilkan conflict/`needs_admin_decision`.
- Employees existing national_id_number menghasilkan conflict/`needs_admin_decision`.
- Password column tidak diproses dan tidak muncul di normalized data.
- App role columns tidak diproses sebagai app access.
- `user_app_accesses` tidak berubah.
- Validation tidak mengubah jumlah users/students/lecturers/employees.
- Authorized admin bisa upload valid students file dan melihat validation summary.
- Guest/unauthorized behavior tetap aman dari test existing.

## Manual Check

Checklist:

- Students validation OK: OK.
- Lecturers validation OK: OK.
- Employees validation OK: OK.
- Conflict detection OK: OK.
- Missing required row fields OK: OK.
- Unknown reference OK: OK.
- Password column ignored/rejected OK: OK.
- App role columns warning OK: OK.
- Data master tidak berubah: OK via count assertions.
- `user_app_accesses` tidak berubah: OK via test.
- Guest diarahkan login: OK via existing Import Center test.
- Unauthorized user ditolak: OK via existing Import Center test.
- Tidak ada 500 error: OK via full test suite.

## Guardrails Confirmation

Konfirmasi:

- Tidak menjalankan `migrate:fresh`.
- Tidak menjalankan `migrate:reset`.
- Tidak menjalankan `migrate:rollback`.
- Tidak drop database.
- Tidak menghapus data existing.
- Tidak execute import KP.
- Tidak mengubah database KP.
- Tidak menyentuh `apps/safa-ubp`.
- Tidak menyentuh `apps/kp-farmasi`.
- Tidak menyentuh `apps/tu-farmasi`.
- Tidak membuat Core public.
- Tidak membuat SSO/bypass login.
- Tidak membuat app shortcut.
- Tidak membuat API baru.
- Tidak membuat app role registry.
- Tidak membuat import execute penuh.
- Tidak create/update/delete data master dari upload.
- Tidak bulk reset password.
- Tidak expose/export password plaintext.
- Tidak menyimpan upload di public disk.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.
- Tidak hardcode secret/credential.

## Risks / Notes

Sisa pekerjaan:

- Admin conflict decision UI belum final.
- Execute import belum dibuat.
- Users/roles/app access row validation belum dibuat.
- Dynamic app role registry belum dibuat.
- Leadership assignment belum dibuat.
- App shortcut belum dibuat.
- API internal belum dibuat.
- Data quality dashboard belum dibuat.

Risiko/catatan:

- Suggested action saat conflict masih `needs_admin_decision`, belum final update/skip.
- `core_import_records` belum dipakai untuk row validation detail.
- Tipe selain students/lecturers/employees belum memiliki row validation.
- App roles sengaja tidak diproses pada profile import agar tidak mencampur profile data dengan app-specific access.

## Recommended Next Step

Rekomendasi tahap berikutnya:

- `CORE-ORG-1 Leadership Assignments Skeleton`

Alasan:

- Row validation untuk Students/Lecturers/Employees sudah tersedia.
- Berdasarkan roadmap owner, leadership structure perlu disiapkan agar Dekan/Kaprodi tidak dimodelkan sebagai role auth biasa.
- Setelah leadership skeleton siap, lanjut ke `CORE-IMPORT-4 Admin Decision UI for Conflicts`.
