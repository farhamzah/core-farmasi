# CORE-IMPORT-2 Upload, Heading Validation & Preview Skeleton Report

Tanggal pengerjaan: 2026-05-23

## Scope

Tahap CORE-IMPORT-2 membuat upload file import, heading validation, dan preview skeleton di Import Center.

Tahap ini belum membuat:

- Import execute penuh.
- Create/update/delete data master dari upload.
- Conflict handling penuh.
- Row-level final import.
- Bulk create user.
- Bulk reset password.
- API baru.
- SSO/app shortcut.

Semua upload pada tahap ini hanya membaca struktur file, menyimpan file di private storage, membuat batch metadata, dan menampilkan preview terbatas.

## Previous Reports Reviewed

Report yang dibaca:

- `docs/reports/CORE-IMPORT-1-IMPORT-CENTER-SKELETON-REPORT.md`
- `docs/reports/CORE-AUTH-3-IDENTITY-BIRTH-DATE-ALIGNMENT-REPORT.md`
- `docs/reports/CORE-AUTH-1-USERNAME-IDENTITY-PASSWORD-SKELETON-REPORT.md`
- `docs/reports/CORE-MASTER-1-EMPLOYEE-STAFF-SKELETON-REPORT.md`

## Files Changed

File dibuat:

- `app/Services/CoreImportPreviewService.php`
- `docs/reports/CORE-IMPORT-2-UPLOAD-HEADING-PREVIEW-REPORT.md`

File diubah:

- `config/core_import.php`
- `app/Filament/Pages/CoreImportCenter.php`
- `resources/views/filament/pages/core-import-center.blade.php`
- `tests/Feature/CoreImportCenterTest.php`

Tidak ada migration baru pada tahap ini.

## Upload Implementation

Upload field/action:

- Import type select: `importType`.
- File upload: `importFile`.
- Action: `uploadAndPreview`.

Accepted types:

- `xlsx`
- `xls`
- `csv`

Max size:

- 5120 KB / 5 MB.

Storage:

- Disk: `local`.
- Directory: `core-imports/pending`.
- Disk `local` pada Laravel config mengarah ke `storage/app/private`.

Public/private status:

- File disimpan di private/local storage.
- Tidak ada public URL yang dibuat.
- Tidak ada route public file download untuk upload import.

Sanitasi filename/path:

- Stored filename memakai UUID dan extension file.
- Original filename hanya disimpan sebagai metadata di batch options.
- Original filename tidak dipakai sebagai path utama.

## Preview Service

Service baru:

- `App\Services\CoreImportPreviewService`

Tanggung jawab:

- Menerima import type.
- Menerima private local file path.
- Membaca active sheet via PhpSpreadsheet.
- Membaca heading row.
- Normalisasi heading:
  - trim
  - lowercase
  - spasi/dash/dot menjadi underscore
  - karakter non alphanumeric/underscore dibersihkan
- Validasi required columns berdasarkan `config/core_import.php`.
- Deteksi unknown columns.
- Deteksi password columns.
- Membaca preview rows terbatas.
- Menghasilkan result array:
  - `import_type`
  - `filename`
  - `original_filename`
  - `stored_path`
  - `headings`
  - `missing_required_columns`
  - `unknown_columns`
  - `password_columns`
  - `preview_rows`
  - `row_count_estimate`
  - `errors`
  - `warnings`
  - `status`
  - `is_valid_for_preview`

Preview row limit:

- 10 rows.

Status:

- `preview_ready` jika heading ada, required columns lengkap, dan tidak ada password column.
- `invalid_heading` jika required columns kurang atau ada password column.
- `failed` jika file tidak bisa dibaca.

No master data mutation:

- Service tidak membuat/update/delete users, students, lecturers, employees, roles, departments, study programs, atau app access.
- Service hanya membaca file dan mengembalikan preview metadata.

## Import Center UI

Perubahan halaman:

- Page tetap `App\Filament\Pages\CoreImportCenter`.
- Route tetap `GET /admin/import-center`.

UI baru:

- Section `Upload & Preview`.
- Import type select dari registry enabled types.
- File input untuk xlsx/xls/csv.
- Tombol `Upload & Preview`.

Preview output:

- Status badge:
  - `preview_ready`
  - `invalid_heading`
  - `failed`
- Batch id.
- Original filename.
- Row count estimate.
- Heading ditemukan.
- Required columns status.
- Missing required columns.
- Unknown columns.
- Errors.
- Warnings.
- Preview table terbatas.

Pesan keamanan:

- UI menampilkan bahwa preview belum mengeksekusi import.
- Data master tidak dibuat, diubah, atau dihapus dari tahap ini.

## Batch Integration

Model/tabel yang digunakan:

- `CoreImportBatch`
- `core_import_batches`

Batch dibuat saat upload berhasil disimpan dan preview selesai dibaca.

Field yang disimpan:

- `source`: import type.
- `mode`: `preview`.
- `status`: `preview_ready`, `invalid_heading`, atau `failed`.
- `started_at`: waktu upload/preview.
- `operator_id`: user admin yang upload.
- `options`:
  - `original_filename`
  - `stored_path`
  - `disk`
- `summary`:
  - `headings`
  - `missing_required_columns`
  - `unknown_columns`
  - `password_columns`
  - `row_count_estimate`
  - `errors`
  - `warnings`

Yang belum dibuat:

- `CoreImportRecord` row detail per row.
- Execute import.
- Row-level conflict decision.

Alasan:

- Schema existing `core_import_records` lebih cocok dipakai saat validation/execute row-level di tahap berikutnya.

## Security Confirmation

Konfirmasi:

- Import Center protected di admin panel.
- Upload protected lewat Filament admin page.
- Template download tetap protected.
- File upload disimpan private/local.
- Tidak ada public route untuk upload.
- Tidak ada public URL file upload.
- Tidak ada import execute.
- Tidak ada perubahan data master.
- Tidak ada plaintext password.
- Kolom password pada file upload membuat preview invalid.
- Password column tidak ditampilkan di preview rows.
- Tidak ada SSO.
- Tidak ada app shortcut.
- Tidak ada API baru.
- Login resmi tetap `/admin/login`.
- Role access tidak dilonggarkan.

## Commands Run

Command yang dijalankan:

- `php artisan optimize:clear`
  - Result: success.
  - Cache/config/routes/views/blade-icons/filament cleared.

- `php artisan test --filter=CoreImportCenterTest`
  - Result: success.
  - 15 tests passed, 59 assertions.

- `php artisan test`
  - Result: success.
  - Final result: 64 tests passed, 237 assertions.

- `php artisan route:list --path=import-center`
  - Result: success.
  - `GET /admin/import-center` registered to `App\Filament\Pages\CoreImportCenter`.

- `Test-Path public\build\manifest.json`
  - Result: `True`.
  - Existing public build manifest tetap tersedia.

Command not run:

- `php artisan migrate`
  - Alasan: tidak ada migration baru pada tahap ini.

- `npm run build`
  - Alasan: tidak ada perubahan frontend asset, CSS, JS, Vite input, atau public build.

## Test Result

`php artisan test` result terakhir:

- Tests: 64 passed.
- Assertions: 237.
- Duration: 13.54s.

Test baru/perluasan test mencakup:

- Authorized super-admin bisa membuka Import Center.
- Guest diarahkan ke login.
- Unauthorized user ditolak.
- Template download tetap OK.
- Upload valid users CSV menghasilkan preview valid dan batch.
- Upload valid students CSV menghasilkan preview valid.
- Upload valid employees CSV menghasilkan preview valid.
- Missing required columns menghasilkan status `invalid_heading`.
- Password column menghasilkan status `invalid_heading`.
- Password value tidak tersimpan di batch summary.
- File non spreadsheet ditolak.
- Upload preview tidak mengubah jumlah users/students/lecturers/employees.
- File upload tersimpan di disk local private path `core-imports/pending`.
- File upload tidak tersimpan di public disk.

## Manual Check

Checklist:

- Import Center bisa dibuka authorized user: OK via HTTP test.
- Upload valid file OK: OK via Livewire upload test.
- Heading valid tampil: OK via preview state test.
- Missing columns tampil: OK via preview state test.
- Unknown columns tampil: OK via service/batch summary.
- Preview rows tampil terbatas: OK, limit 10 rows.
- Guest diarahkan ke login: OK via HTTP test.
- Unauthorized user ditolak: OK via HTTP test.
- File tidak public: OK via storage assertion.
- Data master tidak berubah: OK via count assertion.
- Tidak ada 500 error: OK via full test suite.

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
- Tidak membuat API baru.
- Tidak membuat import execute penuh.
- Tidak membuat create/update/delete data master dari upload.
- Tidak bulk reset password.
- Tidak expose/export password plaintext.
- Tidak menyimpan upload di public disk.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.

## Risks / Notes

Sisa pekerjaan:

- Row validation belum penuh.
- Referential validation belum dibuat.
- Conflict handling belum dibuat.
- Execute import belum dibuat.
- `core_import_records` row-level belum dipakai.
- App shortcut belum dibuat.
- API internal belum dibuat.
- Data quality dashboard belum dibuat.

Risiko/catatan:

- Preview saat ini membaca active sheet pertama.
- Row count masih estimate berbasis highest data row spreadsheet.
- File disimpan private dan belum ada cleanup policy.
- Upload validasi masih struktur dasar; validasi isi data dilakukan tahap berikutnya.

## Recommended Next Step

Rekomendasi tahap berikutnya:

- `CORE-IMPORT-3 Row Validation & Conflict Detection for Students/Lecturers/Employees`

Scope yang disarankan:

- Validasi row per import type prioritas.
- Validasi reference `study_program_code`, `department_code`, `role_slug`, dan `app_code`.
- Deteksi duplicate dalam file dan database.
- Preview conflict tanpa execute import.
