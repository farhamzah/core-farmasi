# CORE-IMPORT-6 Rollback / Undo Import Safety Report

Tanggal pengerjaan: 2026-05-23

## Scope

Tahap CORE-IMPORT-6 membuat rollback/undo import safety untuk batch import Core yang menulis data `students`, `lecturers`, dan `employees`.

Rollback hanya berlaku untuk batch import Core berbasis `CoreImportBatch` dan `CoreImportRecord`. Tahap ini tidak membuat import type baru, tidak membuat import app roles, tidak membuat SSO, tidak membuat app shortcut, dan tidak membuat API baru.

Prinsip utama:

- Rollback hanya untuk data yang dibuat/diubah oleh batch import tertentu.
- Rollback berbasis metadata execution.
- Rollback butuh admin action dan confirmation.
- Create_new di-rollback dengan soft delete.
- Update_existing di-rollback dengan `previous_snapshot`.
- Jika metadata tidak cukup, row masuk `manual_review`.
- User tidak dihapus jika bukan dibuat oleh batch atau masih dipakai data/akses lain.
- Tidak ada password plaintext di log/report/UI.

## Previous Reports Reviewed

Report yang dibaca:

- `docs/reports/CORE-IMPORT-5-EXECUTE-STUDENTS-LECTURERS-EMPLOYEES-REPORT.md`
- `docs/reports/CORE-IMPORT-4-ADMIN-DECISION-UI-REPORT.md`
- `docs/reports/CORE-AUTH-3-IDENTITY-BIRTH-DATE-ALIGNMENT-REPORT.md`

File existing yang diperiksa:

- `app/Services/CoreImportExecutionService.php`
- `app/Filament/Pages/CoreImportCenter.php`
- `resources/views/filament/pages/core-import-center.blade.php`
- `app/Models/CoreImportBatch.php`
- `app/Models/CoreImportRecord.php`
- `app/Models/User.php`
- `app/Models/Student.php`
- `app/Models/Lecturer.php`
- `app/Models/Employee.php`
- `app/Models/UserActivityLog.php`
- `database/migrations/*core_import*`
- `tests/Feature/CoreImportCenterTest.php`

## Files Changed

File dibuat:

- `database/migrations/2026_05_23_000119_add_rollback_metadata_to_core_import_tables.php`
- `app/Services/CoreImportRollbackService.php`
- `docs/reports/CORE-IMPORT-6-ROLLBACK-UNDO-SAFETY-REPORT.md`

File diubah:

- `app/Models/User.php`
- `app/Models/Student.php`
- `app/Models/Lecturer.php`
- `app/Models/CoreImportBatch.php`
- `app/Models/CoreImportRecord.php`
- `app/Services/CoreImportExecutionService.php`
- `app/Filament/Pages/CoreImportCenter.php`
- `resources/views/filament/pages/core-import-center.blade.php`
- `tests/Feature/CoreImportCenterTest.php`

Tidak ada file di `apps/kp-farmasi`, `apps/safa-ubp`, atau `apps/tu-farmasi` yang diubah.

## Database Changes

Migration dibuat:

- `2026_05_23_000119_add_rollback_metadata_to_core_import_tables.php`

Field rollback metadata di `core_import_records`:

- `target_type`
- `executed_action`
- `executed_by`
- `executed_at`
- `previous_snapshot`
- `rollback_status`
- `rollback_note`
- `rolled_back_by`
- `rolled_back_at`
- `rollback_result`
- `created_user_id`
- `linked_user_id`

Field rollback metadata di `core_import_batches`:

- `rollback_status`
- `rolled_back_rows_count`
- `rollback_failed_rows_count`
- `rollback_skipped_rows_count`
- `rolled_back_by`
- `rolled_back_at`

Soft delete additive:

- `users.deleted_at`
- `students.deleted_at`
- `lecturers.deleted_at`

Alasan:

- `students` dan `lecturers` belum mendukung soft delete, sehingga rollback create_new akan berisiko jika memakai hard delete.
- `users` belum mendukung soft delete, sehingga user import-created tidak bisa di-undo secara aman tanpa field `deleted_at`.
- Perubahan ini additive dan non-destruktif.
- Tidak menghapus/rename field existing.

## Execution Metadata Update

`CoreImportExecutionService` diupdate agar record execute menyimpan metadata rollback:

- `target_type`: class model target, misalnya `App\Models\Student`
- `target_id`: id record target
- `executed_action`: `create_new` atau `update_existing`
- `executed_by`: admin yang menjalankan execute
- `executed_at`: waktu execute
- `previous_snapshot`: field target sebelum update untuk `update_existing`
- `created_user_id`: user yang dibuat oleh import row
- `linked_user_id`: user existing yang hanya di-link ke profile

Snapshot:

- Untuk `update_existing`, snapshot menyimpan field yang akan diubah saja.
- Snapshot tidak menyimpan password.
- Snapshot tidak menyimpan password hash.

## Rollback Service

Service baru:

- `App\Services\CoreImportRollbackService`

Supported import types:

- `students`
- `lecturers`
- `employees`

Rollback create_new:

- Cari target berdasarkan `target_type` dan `target_id`.
- Jika model target mendukung SoftDeletes, target di-soft-delete.
- Jika target tidak ditemukan atau model tidak soft delete, row menjadi `manual_review`.
- Jika ada `created_user_id`, user hanya di-soft-delete jika aman:
  - user dibuat oleh batch,
  - user tidak punya roles,
  - user tidak punya `user_app_accesses`,
  - user tidak dipakai profile lain.
- Jika user masih dipakai data lain/app access, target tetap di-soft-delete tetapi user tidak dihapus; row menjadi `manual_review`.

Rollback update_existing:

- Restore `previous_snapshot` ke target.
- Jika `previous_snapshot` tidak tersedia, row menjadi `manual_review`.
- Tidak memakai snapshot jika metadata tidak cukup.

Rollback skip/invalid:

- Record `skipped` atau `ignored_invalid` ditandai `rollback_status=skipped`.
- Tidak mengubah data master.

Rollback statuses:

- `rolled_back`
- `skipped`
- `failed`
- `manual_review`
- `not_supported`
- `already_rolled_back`

Batch rollback:

- Summary batch disimpan ke `summary.rollback`.
- Batch menyimpan count:
  - rolled back
  - skipped
  - failed
  - manual review
  - already rolled back

## Rollback UI

Halaman:

- `/admin/import-center`

UI yang ditambahkan:

- Kolom `Rollback` di decision/execution table.
- Tombol `Rollback Import Batch`.
- Confirmation kuat sebelum rollback:
  - rollback mencoba membatalkan perubahan batch ini,
  - update_existing hanya direstore jika snapshot tersedia,
  - user yang sudah dipakai data lain tidak dihapus otomatis,
  - rollback tidak menjamin membatalkan perubahan manual setelah import.
- Rollback summary:
  - rolled back
  - skipped
  - manual review
  - failed
  - already rolled back

Rollback tidak otomatis. Admin harus klik tombol rollback.

## Audit/Logging

`UserActivityLog` digunakan.

Event:

- `import.rollback_started`
- `import.rollback_row_result`
- `import.rollback_completed`

Metadata aman:

- `import_batch_id`
- `import_record_id`
- `target_type`
- `target_id`
- `rollback_status`
- rollback summary angka

Tidak dicatat:

- Password plaintext.
- Password hash.
- Full sensitive snapshot.

## Security Confirmation

Konfirmasi:

- Rollback action protected di admin Filament.
- Tidak ada public route baru.
- Tidak ada unsafe hard delete.
- Rollback create_new memakai soft delete.
- User import-created hanya soft delete jika aman.
- Tidak menghapus user yang bukan dibuat batch.
- Tidak menghapus user yang sudah dipakai data lain/app access.
- Tidak ada password plaintext.
- Tidak ada SSO.
- Tidak ada app shortcut.
- Tidak ada API baru.
- Tidak menyentuh KP/SAFA/TU.
- Role access tidak dilonggarkan.
- Login resmi tetap `/admin/login`.

## Commands Run

Command yang dijalankan:

- `php artisan optimize:clear`
  - Result: OK.
- `php artisan migrate`
  - Result: OK.
  - Migration `2026_05_23_000119_add_rollback_metadata_to_core_import_tables` berhasil dijalankan.
- `php artisan test --filter=CoreImportCenterTest`
  - Result: OK.
  - 39 tests passed / 188 assertions.
- `php artisan test`
  - Result: OK.
  - 107 tests passed / 461 assertions.

Command tidak dijalankan:

- `npm run build`
  - Alasan: tidak ada perubahan frontend asset, CSS, JS, Vite input, atau public build.

## Test Result

Hasil akhir:

- `php artisan test`: 107 passed / 461 assertions.

Test baru/perluasan test mencakup:

- Rollback create_new student soft deletes Student dan import-created User.
- Rollback create_new lecturer soft deletes Lecturer.
- Rollback create_new employee soft deletes Employee.
- Rollback update_existing restores `previous_snapshot`.
- Update_existing tanpa `previous_snapshot` menjadi `manual_review`.
- Skip/invalid records tidak berubah destruktif.
- Rollback tidak berjalan destruktif dua kali.
- Rollback tidak menghapus user import-created jika user memiliki app access.
- Rollback tidak menyentuh `user_app_accesses`.
- Rollback tidak menyentuh `leadership_assignments`.
- Guest dan unauthorized behavior tetap aman dari test existing.

## Manual Check

Checklist:

- Rollback create_new students OK: OK.
- Rollback create_new lecturers OK: OK.
- Rollback create_new employees OK: OK.
- Rollback update_existing with snapshot OK: OK.
- Manual_review for unsafe rollback OK: OK.
- Rollback cannot run destructively twice: OK.
- Guest diarahkan login: OK.
- Unauthorized user ditolak: OK.
- Tidak ada 500 error: OK via full test suite.

## Guardrails Confirmation

Konfirmasi:

- Tidak menjalankan `migrate:fresh`.
- Tidak menjalankan `migrate:reset`.
- Tidak menjalankan `migrate:rollback`.
- Tidak drop database.
- Tidak hard delete unsafe data.
- Tidak menghapus user yang bukan dibuat batch.
- Tidak menghapus user yang sudah dipakai data lain.
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
- Tidak bulk reset password.
- Tidak expose/export password plaintext.
- Tidak menyimpan upload di public disk.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.
- Tidak hardcode secret/credential.

## Risks / Notes

Risiko/catatan:

- Rollback hanya seaman metadata yang tersedia.
- Batch lama tanpa `previous_snapshot` untuk `update_existing` akan menjadi `manual_review`.
- Jika user import-created sudah dipakai app access atau data lain, user tidak dihapus dan row menjadi `manual_review`.
- Soft-deleted records tetap mempertahankan unique value di database; re-import dengan identifier sama bisa tetap butuh keputusan admin/manual cleanup.
- Deteksi perubahan manual setelah import masih sederhana; tahap data quality/audit dapat memperkuat ini.

Sisa pekerjaan:

- Import users standalone belum dibuat.
- Import departments/study_programs belum dibuat.
- Import roles/app access belum dibuat.
- Import leadership assignment belum dibuat.
- Internal API belum dibuat.
- App launcher belum dibuat.
- Data quality dashboard belum dibuat.

## Recommended Next Step

Rekomendasi tahap berikutnya:

- `CORE-DQ-1 Data Quality Dashboard`.

Alasan:

- Import execute dan rollback safety sudah tersedia untuk students/lecturers/employees.
- Tahap aman berikutnya adalah dashboard kualitas data untuk mendeteksi duplicate, missing identity, records tanpa user, missing app access, dan leadership assignment yang expired/missing.
- Setelah data quality terlihat, baru lanjut ke import users/app access atau app launcher dengan risiko lebih terukur.
