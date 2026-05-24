# CORE-IMPORT-5 Execute Import for Students, Lecturers & Employees Report

Tanggal pengerjaan: 2026-05-23

## Scope

Tahap CORE-IMPORT-5 membuat eksekusi import untuk `students`, `lecturers`, dan `employees` berdasarkan validation records dan `admin_decision` dari CORE-IMPORT-4.

Tahap ini boleh menulis ke tabel master berikut, tetapi hanya untuk row yang sudah valid dan disetujui admin:

- `students`
- `lecturers`
- `employees`
- `users` jika aman untuk membuat/link akun terkait

Tahap ini tidak membuat import execute untuk:

- users standalone
- departments
- study_programs
- roles
- user_role_assignments
- user_app_accesses
- leadership_assignments

Tahap ini tidak membuat SSO, app shortcut, API baru, route public, atau import KP.

## Previous Reports Reviewed

Report yang dibaca:

- `docs/reports/CORE-IMPORT-4-ADMIN-DECISION-UI-REPORT.md`
- `docs/reports/CORE-IMPORT-3-ROW-VALIDATION-CONFLICT-DETECTION-REPORT.md`
- `docs/reports/CORE-AUTH-3-IDENTITY-BIRTH-DATE-ALIGNMENT-REPORT.md`
- `docs/reports/CORE-ACCESS-1-DYNAMIC-APP-ROLE-CATALOG-REPORT.md`

File existing yang diperiksa:

- `app/Services/CoreImportValidationService.php`
- `app/Services/CoreInitialPasswordService.php`
- `app/Filament/Pages/CoreImportCenter.php`
- `resources/views/filament/pages/core-import-center.blade.php`
- `app/Models/CoreImportBatch.php`
- `app/Models/CoreImportRecord.php`
- `app/Models/User.php`
- `app/Models/Student.php`
- `app/Models/Lecturer.php`
- `app/Models/Employee.php`
- `app/Models/StudyProgram.php`
- `app/Models/Department.php`
- `app/Models/UserActivityLog.php`
- `tests/Feature/CoreImportCenterTest.php`

## Files Changed

File dibuat:

- `app/Services/CoreImportExecutionService.php`
- `docs/reports/CORE-IMPORT-5-EXECUTE-STUDENTS-LECTURERS-EMPLOYEES-REPORT.md`

File diubah:

- `app/Services/CoreInitialPasswordService.php`
- `app/Filament/Pages/CoreImportCenter.php`
- `resources/views/filament/pages/core-import-center.blade.php`
- `tests/Feature/CoreImportCenterTest.php`

Tidak ada migration baru pada tahap ini.

## Execution Service

Service baru:

- `App\Services\CoreImportExecutionService`

Supported import types:

- `students`
- `lecturers`
- `employees`

Executable rules:

- Record dengan `admin_decision=create_new` dieksekusi sebagai create.
- Record dengan `admin_decision=update_existing` dieksekusi sebagai update.
- Record dengan `admin_decision=skip` ditandai `skipped`, tanpa mengubah master data.
- Record dengan `admin_decision=invalid` atau `validation_status=invalid` ditandai `ignored_invalid`, tanpa mengubah master data.
- Record dengan `needs_admin_decision`, null, atau pending tidak dieksekusi.
- Batch dengan pending decision ditolak untuk execute.
- Unsupported import type ditolak.

Transaction strategy:

- Transaksi dilakukan per row.
- Jika satu row gagal, row tersebut diberi `execution_status=failed`.
- Row lain tetap dapat dieksekusi.
- Batch summary dapat menjadi `partially_failed`.
- Keputusan ini menghindari satu row buruk menggagalkan seluruh batch.

Record status:

- `not_executed`
- `executed`
- `skipped`
- `failed`
- `ignored_invalid`
- `pending`

Batch status:

- `executing`
- `executed`
- `partially_failed`
- `failed`

No unsupported import execution:

- `users`, `departments`, `study_programs`, `roles`, `user_role_assignments`, `user_app_accesses`, dan `leadership_assignments` belum dieksekusi pada tahap ini.

## Student Execution

`create_new`:

- Mencari `StudyProgram` dari `study_program_code`.
- Menolak create jika NIM sudah ada saat execute.
- Membuat `Student` baru.
- Mengisi:
  - `student_number`
  - `name`
  - `email`
  - `birth_date`
  - `study_program_id`
  - `status`
  - `active`
  - `user_id` jika user dibuat/di-link aman.

`update_existing`:

- Mencari Student berdasarkan NIM.
- Update field non-empty yang aman.
- Tidak mengubah NIM primary identifier.
- Tidak overwrite field dengan null/kosong.
- Link user hanya jika student belum punya `user_id` dan user creation/link aman.

`skip/invalid`:

- Tidak mengubah data master.
- Record diberi status `skipped` atau `ignored_invalid`.

User creation/linking:

- Username dari `username` atau NIM.
- `identity_type=student`.
- `identity_number` dari `identity_number` atau NIM.
- Email wajib untuk user karena schema `users.email` required.
- Password dibuat dari `birth_date` jika tersedia.
- Jika `birth_date` kosong, user tidak dibuat; profile dapat dibuat tanpa `user_id` jika schema mengizinkan.

## Lecturer Execution

`create_new`:

- Mencari `Department` dari `department_code`.
- Mencari `StudyProgram` dari `study_program_code` jika ada.
- Identifier lecturer dari `nidn`, `nip`, atau `identity_number`.
- Menolak create jika NIDN/NIP sudah ada saat execute.
- Membuat `Lecturer` baru dan link user jika aman.

`update_existing`:

- Mencari Lecturer berdasarkan `nidn`, `nip`, atau email.
- Update field non-empty yang aman.
- Tidak overwrite dengan null/kosong.
- Link user hanya jika belum punya `user_id` dan aman.

`skip/invalid`:

- Tidak mengubah data master.

## Employee Execution

`create_new`:

- Menolak create jika `employee_number` atau `national_id_number` sudah ada saat execute.
- Mencari Department/StudyProgram jika code tersedia.
- Membuat `Employee` baru.
- Link user jika username/email/birth_date aman.

`update_existing`:

- Mencari Employee berdasarkan `employee_number`, `national_id_number`, atau email.
- Update field non-empty yang aman.
- Tidak overwrite dengan null/kosong.
- Link user hanya jika belum punya `user_id` dan aman.

`skip/invalid`:

- Tidak mengubah data master.

## User Creation & Password Safety

Username strategy:

- Student: `username` dari row atau NIM.
- Lecturer: `username` dari row atau NIDN/NIP/identity.
- Employee: `username` dari row atau employee_number/national_id_number/email.

Password strategy:

- Password awal hanya dari `birth_date`.
- Format mengikuti `core_identity.initial_password_format`, yaitu `d/m/Y`.
- `CoreInitialPasswordService` diperkuat agar string `dd/mm/yyyy` diparse sebagai format Indonesia terlebih dahulu sebelum fallback parse.
- Password selalu hashed.
- `must_change_password=true`.
- `password_changed_at=null`.
- `last_password_reset_at=now()`.
- `password_reset_by` diisi actor admin jika tersedia.

Behavior jika birth_date missing:

- Tidak membuat user baru.
- Tidak membuat password fallback.
- Tidak membuat password lemah seperti NIM, `123456`, atau `password`.
- Profile dapat dibuat/update tanpa user jika schema mengizinkan.

No plaintext:

- Password plaintext tidak disimpan.
- Password plaintext tidak ditampilkan.
- Password plaintext tidak ditulis ke report/log/notification.

## UI Execute Action

Update halaman:

- `/admin/import-center`

Perubahan UI:

- Decision table sekarang menampilkan `execution_status`.
- Tombol `Execute Import` aktif hanya jika:
  - tidak ada pending decision,
  - ada executable rows.
- Tombol memakai confirmation:
  - hanya rows approved yang dieksekusi,
  - invalid/skip tidak dieksekusi,
  - user baru memakai password awal dari birth_date jika tersedia,
  - password tidak ditampilkan.
- Setelah execute, halaman menampilkan execution summary:
  - executed
  - created
  - updated
  - skipped
  - ignored invalid
  - failed
  - users created

Import tidak auto-execute setelah upload atau save decision. Execute tetap manual oleh admin.

## Audit/Logging

`UserActivityLog` digunakan.

Event yang dicatat:

- `import.batch_executed`
- `import.row_created`
- `import.row_updated`
- `import.user_created`

Metadata aman:

- `import_batch_id`
- `import_record_id`
- `import_type`
- `target_model`
- `target_id`
- `target_user_id`
- `identity_type`
- `method=birth_date_based`
- summary angka execute

Tidak dicatat:

- Password plaintext.
- Password hash.
- Full sensitive row.

## Security Confirmation

Konfirmasi:

- Execute action protected di admin Filament.
- Tidak ada route public baru.
- File upload tetap private/local.
- Tidak ada SSO.
- Tidak ada app shortcut.
- Tidak import app roles/user_app_accesses.
- Tidak import leadership assignment.
- Tidak ada plaintext password.
- Tidak ada password export.
- Tidak execute KP import.
- Tidak menyentuh KP/SAFA/TU.
- Role access tidak dilonggarkan.
- Login resmi tetap `/admin/login`.

## Commands Run

Command yang dijalankan:

- `php artisan optimize:clear`
  - Result: OK.
- `php artisan test --filter=CoreImportCenterTest`
  - Run pertama menemukan beberapa penyesuaian test:
    - parse `dd/mm/yyyy` perlu dipertegas di `CoreInitialPasswordService`,
    - test skip/invalid butuh satu executable row agar execute action berjalan,
    - fixture user count perlu dihitung setelah admin dibuat.
  - Setelah perbaikan: OK, 31 tests passed / 165 assertions.
- `php artisan test`
  - Result: OK.
  - Final result: 99 tests passed / 438 assertions.

Command tidak dijalankan:

- `php artisan migrate`
  - Alasan: tidak ada migration baru pada CORE-IMPORT-5. Schema CORE-IMPORT-4 sudah cukup.
- `npm run build`
  - Alasan: tidak ada perubahan frontend asset, CSS, JS, Vite input, atau public build.

## Test Result

Hasil akhir:

- `php artisan test`: 99 passed / 438 assertions.

Test baru/perluasan test mencakup:

- Execute students `create_new` membuat Student.
- Execute students `create_new` dengan birth_date dan username membuat User hashed + `must_change_password`.
- Execute skip tidak mengubah data target row.
- Execute invalid/skip tidak mengubah data target row.
- Execute `update_existing` mengupdate Student existing.
- Duplicate NIM saat `create_new` gagal aman.
- Execute lecturer `create_new` membuat Lecturer dan user hashed.
- Execute employee `create_new` membuat Employee dan user hashed.
- Execute employee `update_existing` mengupdate Employee existing.
- Birth date kosong tidak membuat password/user lemah.
- Password tidak plaintext.
- `user_app_accesses` tidak berubah.
- `leadership_assignments` tidak berubah.
- Record `execution_status` terisi.
- Batch summary terupdate.
- Guest dan unauthorized tetap aman dari test existing.

## Manual Check

Checklist:

- Execute valid students OK: OK.
- Execute valid lecturers OK: OK.
- Execute valid employees OK: OK.
- Skip/invalid tidak mengubah data target row: OK.
- Update_existing OK: OK.
- Password hashed: OK.
- Must_change_password true: OK.
- Batch summary OK: OK.
- Record execution status OK: OK.
- Guest diarahkan login: OK.
- Unauthorized user ditolak: OK.
- Tidak ada 500 error: OK via full test suite.

## Guardrails Confirmation

Konfirmasi:

- Tidak menjalankan `migrate:fresh`.
- Tidak menjalankan `migrate:reset`.
- Tidak menjalankan `migrate:rollback`.
- Tidak drop database.
- Tidak execute import KP.
- Tidak mengubah database KP.
- Tidak menyentuh `apps/safa-ubp`.
- Tidak menyentuh `apps/kp-farmasi`.
- Tidak menyentuh `apps/tu-farmasi`.
- Tidak membuat Core public.
- Tidak membuat SSO/bypass login.
- Tidak membuat app shortcut.
- Tidak membuat API baru.
- Tidak import roles/app accesses.
- Tidak import leadership assignment.
- Tidak bulk reset password terpisah.
- Tidak expose/export password plaintext.
- Tidak menyimpan upload di public disk.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.
- Tidak hardcode secret/credential.

## Risks / Notes

Sisa pekerjaan:

- Import users standalone belum dibuat.
- Import departments/study_programs belum dibuat.
- Import roles/app access belum dibuat.
- Import leadership assignment belum dibuat.
- Rollback/undo import belum dibuat.
- Internal API belum dibuat.
- App launcher belum dibuat.
- Data quality dashboard belum dibuat.

Risiko/catatan:

- Student/Lecturer schema lama mewajibkan email, sehingga row tanpa email akan gagal aman saat execute.
- Lecturer schema lama mewajibkan department, sehingga create lecturer tanpa department_code akan gagal aman saat execute.
- `update_existing` menggunakan update non-empty untuk menghindari overwrite data existing dengan null/kosong.
- Partial failure dapat terjadi dan dicatat per row.

## Recommended Next Step

Rekomendasi tahap berikutnya:

- `CORE-IMPORT-6 Rollback/Undo Import Safety`.

Alasan:

- CORE-IMPORT-5 sudah mulai menulis ke master data.
- Sebelum memperluas import ke users/app access/leadership, lebih aman menambah kemampuan audit detail dan rollback/undo import untuk batch tertentu.
- Setelah rollback safety, lanjutkan ke import users/app access atau data quality dashboard.
