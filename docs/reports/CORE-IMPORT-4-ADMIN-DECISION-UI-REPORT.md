# CORE-IMPORT-4 Admin Decision UI Report

Tanggal pengerjaan: 2026-05-23

## Scope

Tahap CORE-IMPORT-4 membuat Admin Decision UI untuk hasil validation/conflict import `students`, `lecturers`, dan `employees`.

Tahap ini belum membuat import execute. Tidak ada create/update/delete data master dari file upload. Semua perubahan hanya menyimpan hasil validasi, keputusan admin, dan ringkasan decision ke tabel import staging.

Import types yang decision-ready pada tahap ini:

- `students`
- `lecturers`
- `employees`

Import types lain tetap belum decision-ready:

- `users`
- `departments`
- `study_programs`
- `roles`
- `user_role_assignments`
- `user_app_accesses`

## Previous Reports Reviewed

Report yang dibaca:

- `docs/reports/CORE-IMPORT-3-ROW-VALIDATION-CONFLICT-DETECTION-REPORT.md`
- `docs/reports/CORE-ACCESS-1-DYNAMIC-APP-ROLE-CATALOG-REPORT.md`
- `docs/reports/CORE-ORG-1-LEADERSHIP-ASSIGNMENTS-SKELETON-REPORT.md`

File existing yang diperiksa:

- `config/core_import.php`
- `app/Services/CoreImportPreviewService.php`
- `app/Services/CoreImportValidationService.php`
- `app/Filament/Pages/CoreImportCenter.php`
- `resources/views/filament/pages/core-import-center.blade.php`
- `app/Models/CoreImportBatch.php`
- `app/Models/CoreImportRecord.php`
- `database/migrations/*core_import*`
- `tests/Feature/CoreImportCenterTest.php`

## Files Changed

File dibuat:

- `database/migrations/2026_05_23_000118_add_decision_columns_to_core_import_tables.php`
- `docs/reports/CORE-IMPORT-4-ADMIN-DECISION-UI-REPORT.md`

File diubah:

- `app/Models/CoreImportBatch.php`
- `app/Models/CoreImportRecord.php`
- `app/Services/CoreImportValidationService.php`
- `app/Filament/Pages/CoreImportCenter.php`
- `resources/views/filament/pages/core-import-center.blade.php`
- `tests/Feature/CoreImportCenterTest.php`

Tidak ada file di `apps/kp-farmasi`, `apps/safa-ubp`, atau `apps/tu-farmasi` yang diubah.

## Database Changes

Migration dibuat:

- `2026_05_23_000118_add_decision_columns_to_core_import_tables.php`

Field tambahan di `core_import_records`:

- `validation_status` nullable string index
- `suggested_action` nullable string index
- `admin_decision` nullable string index
- `decision_note` nullable text
- `decided_by` nullable foreign key ke `users`, `nullOnDelete`
- `decided_at` nullable timestamp
- `normalized_data` nullable json
- `errors` nullable json
- `warnings` nullable json
- `conflicts` nullable json
- `execution_status` nullable string index

Field tambahan di `core_import_batches`:

- `decision_status` nullable string index
- `decided_rows_count` unsigned integer default 0
- `pending_decision_rows_count` unsigned integer default 0
- `executable_rows_count` unsigned integer default 0

Alasan:

- `core_import_records` existing belum punya field cukup untuk menyimpan hasil validation per row dan keputusan admin.
- `core_import_batches` existing belum punya summary field eksplisit untuk status decision.
- Perubahan additive dan non-destruktif.
- Tidak ada field yang dihapus atau di-rename.

## Decision Model/Storage

Hasil validation disimpan sebagai `core_import_records`.

Mapping storage:

- `source_table`: import type, misalnya `students`
- `source_id`: row number
- `source_identifier`: identifier row, misalnya NIM/NIDN/employee_number/name
- `target_table`: import type
- `action`: `decision_preview`
- `validation_status`: `valid`, `conflict`, atau `invalid`
- `suggested_action`: hasil dari validation service
- `admin_decision`: keputusan admin/default decision
- `decision_note`: catatan opsional admin
- `decided_by`: user admin yang menyimpan keputusan
- `decided_at`: waktu keputusan disimpan
- `normalized_data`: data aman yang sudah dinormalisasi
- `errors`, `warnings`, `conflicts`: detail validation aman
- `execution_status`: `not_executed`

Default decision:

- Valid row: `create_new`
- Conflict row: `needs_admin_decision`
- Invalid row: `invalid`

Batch summary:

- `total_rows`
- `valid_rows`
- `conflict_rows`
- `invalid_rows`
- `pending_decisions`
- `executable_rows`
- `skipped_rows`
- `invalid_decisions`
- `decided_rows`

Batch status:

- `waiting_decision` jika masih ada pending decision.
- `decision_ready` jika tidak ada pending decision.

Batch decision status:

- `pending`
- `partial`
- `ready`
- `none` untuk unsupported/no decision rows.

## Decision UI

Decision UI ditambahkan ke halaman:

- `/admin/import-center`

UI yang dibuat:

- Summary cards:
  - Pending
  - Executable
  - Skipped
  - Invalid
  - Decided
  - Total
- Decision table:
  - row number
  - identifier
  - validation status badge
  - suggested action
  - admin decision select
  - decision note input
  - errors/warnings/conflicts detail
- Bulk actions ringan:
  - Valid: Create New
  - Conflict: Skip
  - Invalid: Skip
  - Reset
- Save Decisions button.
- Execute import button placeholder disabled.

Pesan UI:

- Menjelaskan bahwa belum ada data yang diimport.
- Menjelaskan execute import tersedia pada tahap berikutnya.

## Rules

Allowed decisions:

Valid rows:

- `create_new`
- `skip`

Conflict rows:

- `needs_admin_decision`
- `update_existing`
- `skip`
- `create_new`

Invalid rows:

- `invalid`
- `skip`

Unsupported import types:

- Tidak memiliki decision UI.
- Tidak membuat `core_import_records` decision rows.

Security/data rules:

- Invalid row tidak dapat dipaksa menjadi `create_new` atau `update_existing`.
- Password column tetap tidak disimpan.
- `normalized_data` tidak menyimpan key yang mengandung `password`.
- Save decision tidak mengeksekusi import dan tidak mengubah master data.

## Security Confirmation

Konfirmasi:

- Import Center protected oleh admin Filament.
- Decision action hanya berjalan untuk authenticated authorized Core admin.
- Tidak ada public route baru.
- Tidak ada import execute.
- Tidak ada create/update/delete data master dari upload.
- Tidak ada plaintext password di DB/log/report/notification.
- File upload tetap private/local sesuai tahap sebelumnya.
- Tidak ada SSO.
- Tidak ada app shortcut.
- Tidak ada app role assignment otomatis.
- Role access tidak dilonggarkan.
- Login resmi tetap `/admin/login`.

## Commands Run

Command yang dijalankan:

- `php artisan optimize:clear`
  - Result: OK.
- `php artisan migrate`
  - Result: OK.
  - Migration `2026_05_23_000118_add_decision_columns_to_core_import_tables` berhasil dijalankan.
- `php artisan test --filter=CoreImportCenterTest`
  - Run pertama: ada beberapa test lama perlu penyesuaian karena status batch kini menjadi `decision_ready`, dan data fixture student perlu email sesuai schema existing.
  - Setelah penyesuaian: OK, 24 tests passed / 124 assertions.
- `php artisan test`
  - Result: OK.
  - Final result: 92 tests passed / 397 assertions.

Command tidak dijalankan:

- `npm run build`
  - Alasan: tidak ada perubahan frontend asset, CSS, JS, Vite input, atau public build.

## Test Result

Hasil akhir:

- `php artisan test`: 92 passed / 397 assertions.

Test baru/perluasan test mencakup:

- Validation results dipersist ke `core_import_records`.
- Valid row default decision `create_new`.
- Conflict row default decision `needs_admin_decision`.
- Invalid row default decision `invalid`.
- Admin decision tersimpan dengan `decided_by` dan `decided_at`.
- Batch summary pending/executable/skipped benar.
- Password column tidak tersimpan.
- Save decision tidak mengubah jumlah users/students/lecturers/employees.
- Invalid row tidak dapat diubah menjadi `create_new`.
- Guest/unauthorized behavior tetap aman dari test existing.

## Manual Check

Checklist:

- Decision UI muncul setelah validation: OK via Livewire test.
- Valid row decision OK: OK.
- Conflict row decision OK: OK.
- Invalid row locked/skip OK: OK.
- Save decision OK: OK.
- Execute import belum tersedia/disabled: OK di UI.
- Data master tidak berubah: OK via count assertions.
- Guest diarahkan login: OK.
- Unauthorized user ditolak: OK.
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

- Execute import belum dibuat.
- Rollback import belum dibuat.
- Users/roles/app access import belum execute.
- Internal API belum dibuat.
- Data quality dashboard belum dibuat.
- App launcher belum dibuat.

Catatan teknis:

- `core_import_records` sekarang dipakai untuk validation/decision staging.
- `execution_status` masih `not_executed`.
- `update_existing` baru decision intent, belum melakukan update.
- `create_new` baru decision intent, belum membuat data.

## Recommended Next Step

Rekomendasi tahap berikutnya:

- `CORE-IMPORT-5 Execute Import for Students/Lecturers/Employees`.

Alasan:

- Upload, heading preview, row validation, conflict detection, dan admin decision sudah tersedia.
- Tahap berikutnya dapat memakai `core_import_records.admin_decision` sebagai input aman untuk execute import.
- Execute harus tetap bertahap, audited, dan tidak menyentuh password plaintext.
