# CORE-IMPORT-1 Import Center Skeleton & Template Download Report

Tanggal pengerjaan: 2026-05-23

## Scope

Tahap CORE-IMPORT-1 membuat skeleton Import Center dan download template Excel untuk data utama Core.

Tahap ini belum membuat:

- Import execute penuh.
- Preview validation penuh.
- Conflict handling.
- Upload processing.
- Perubahan data master dari file upload.
- Bulk create user.
- Bulk reset password.

Prinsip utama yang dijaga:

- Import harus preview-first.
- Template tidak memiliki kolom password.
- Template boleh memiliki `birth_date` untuk kebutuhan initial password tahap execute import nanti.
- Semua akses tetap berada di admin Core yang protected.

## Previous Reports Reviewed

Report yang dibaca:

- `docs/reports/CORE-MASTER-0-ARCHITECTURE-PLANNING-REPORT.md`
- `docs/reports/CORE-MASTER-1-EMPLOYEE-STAFF-SKELETON-REPORT.md`
- `docs/reports/CORE-AUTH-1-USERNAME-IDENTITY-PASSWORD-SKELETON-REPORT.md`
- `docs/reports/CORE-AUTH-3-IDENTITY-BIRTH-DATE-ALIGNMENT-REPORT.md`

## Files Changed

File dibuat:

- `config/core_import.php`
- `app/Services/CoreImportTemplateService.php`
- `app/Exports/CoreImportTemplateExport.php`
- `app/Filament/Pages/CoreImportCenter.php`
- `resources/views/filament/pages/core-import-center.blade.php`
- `tests/Feature/CoreImportCenterTest.php`
- `docs/reports/CORE-IMPORT-1-IMPORT-CENTER-SKELETON-REPORT.md`

File diubah:

- Tidak ada file existing yang diubah pada tahap ini selain penambahan test/report baru.

README tidak diubah karena report ini sudah mencatat hasil tahap dan tidak ada credential/password yang boleh didokumentasikan.

## Import Registry

Registry baru:

- `config/core_import.php`

Import types:

- `users`
- `students`
- `lecturers`
- `employees`
- `departments`
- `study_programs`
- `roles`
- `user_role_assignments`
- `user_app_accesses`

Setiap type berisi:

- `label`
- `description`
- `template_filename`
- `required_columns`
- `optional_columns`
- `sample_rows`
- `notes`
- `is_enabled`

Semua import types pada tahap ini enabled untuk template download.

Catatan registry:

- Tidak ada kolom `password` di required/optional columns.
- `birth_date` tersedia pada template `users`, `students`, `lecturers`, dan `employees`.
- `app access` tetap metadata authorization, bukan SSO.

## Template Download

Service baru:

- `App\Services\CoreImportTemplateService`

Tanggung jawab:

- Membaca `config/core_import.php`.
- Mengambil daftar import types aktif.
- Menghasilkan heading template.
- Menghasilkan sample rows.
- Menghasilkan filename template.
- Memastikan template tidak memiliki kolom password.
- Tidak menyentuh database master.

Export class baru:

- `App\Exports\CoreImportTemplateExport`

Format file:

- `.xlsx`

Template yang tersedia:

- Users: OK.
- Students: OK.
- Lecturers: OK.
- Employees / Tendik / Staff: OK.
- Departments: OK.
- Study Programs: OK.
- Roles: OK.
- User Role Assignments: OK.
- User App Accesses: OK.

Password:

- Tidak ada kolom password.
- Tidak ada password plaintext dalam template.
- Password awal nanti dihitung dari `birth_date` pada tahap execute import, bukan ditulis di file.

Birth date:

- Ada pada template yang relevan:
  - users
  - students
  - lecturers
  - employees

## Import Center Page

Page baru:

- `App\Filament\Pages\CoreImportCenter`

Route:

- `GET /admin/import-center`

Navigation:

- Group: `Import & Data Tools`
- Label: `Import Center`
- Icon: `heroicon-o-arrow-up-tray`

UI sections:

- `Template Download`
  - Menampilkan daftar import type.
  - Menampilkan label, description, dan ringkasan required columns.
  - Tombol download template per import type.

- `Upload Skeleton`
  - Placeholder informasi.
  - Menjelaskan bahwa preview, validasi heading, conflict handling, dan execute import akan dibuat pada tahap berikutnya.
  - Tidak memproses file.
  - Tidak mengubah data master.

Tombol download:

- Berjalan via Livewire method `downloadTemplate`.
- Protected karena page berada di admin panel Filament.
- Filename mengikuti registry.

## Upload Skeleton

Upload processing belum dibuat pada tahap ini.

Alasan:

- Tahap ini fokus pada fondasi registry dan template download.
- Upload yang aman perlu desain validasi file, private storage, batch status, heading validation, dan preview.
- Agar tidak ada risiko data master berubah tanpa preview, upload processing dipindahkan ke CORE-IMPORT-2.

Rencana CORE-IMPORT-2:

- Upload `.xlsx/.xls/.csv` dengan size limit.
- Simpan file ke disk local/private, bukan public.
- Buat `core_import_batches` status `uploaded`.
- Validasi heading.
- Preview rows tanpa execute data master.

## Import Batch Integration

Existing model/tabel yang tersedia:

- `CoreImportBatch`
- `CoreImportRecord`
- `core_import_batches`
- `core_import_records`

Integrasi batch belum dibuat pada tahap ini karena belum ada upload processing.

Catatan:

- Struktur existing cukup sebagai fondasi awal.
- Row-level import records dan status preview akan dikerjakan di CORE-IMPORT-2/CORE-IMPORT-3.

## Security Confirmation

Konfirmasi:

- Import Center protected di `/admin/import-center`.
- Guest diarahkan ke `/admin/login`.
- Unauthorized user ditolak.
- Template download protected melalui page admin Filament.
- Tidak ada public download route.
- Tidak ada public upload route.
- Tidak ada file upload yang disimpan pada tahap ini.
- Tidak ada file public.
- Tidak ada plaintext password.
- Tidak ada password export.
- Tidak ada import execute.
- Tidak ada perubahan data master dari upload/template.
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
  - 8 tests passed, 29 assertions.

- `php artisan test`
  - Result: success.
  - Final result: 57 tests passed, 207 assertions.

- `php artisan route:list --path=import-center`
  - Result: success.
  - `GET /admin/import-center` registered to `App\Filament\Pages\CoreImportCenter`.

- `Test-Path public\build\manifest.json`
  - Result: `True`.
  - Asset build existing tetap tersedia.

Command not run:

- `php artisan migrate`
  - Alasan: tidak ada migration baru pada tahap ini.

- `npm run build`
  - Alasan: tidak ada perubahan frontend asset, CSS, JS, Vite input, atau public build.

## Test Result

`php artisan test` result terakhir:

- Tests: 57 passed.
- Assertions: 207.
- Duration: 6.43s.

Test baru mencakup:

- Super-admin bisa membuka Import Center.
- Guest diarahkan ke login.
- Unauthorized user ditolak.
- Registry import types lengkap.
- Semua template tidak memiliki kolom password.
- `birth_date` tersedia pada template relevan.
- Authorized admin bisa download semua template.
- Download template tidak mengubah data master.

## Manual Check

Checklist:

- Import Center bisa dibuka authorized user: OK via HTTP test.
- Guest diarahkan ke login: OK via HTTP test.
- Unauthorized user ditolak: OK via HTTP test.
- Template Users download OK: OK via Livewire file download test.
- Template Students download OK: OK via Livewire file download test.
- Template Lecturers download OK: OK via Livewire file download test.
- Template Employees download OK: OK via Livewire file download test.
- Template Departments download OK: OK via Livewire file download test.
- Template Study Programs download OK: OK via Livewire file download test.
- Template Roles download OK: OK via Livewire file download test.
- Template User Role Assignments download OK: OK via Livewire file download test.
- Template User App Accesses download OK: OK via Livewire file download test.
- Template tidak memiliki password column: OK via service test.
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
- Tidak membuat preview conflict penuh.
- Tidak mengubah data master dari upload.
- Tidak bulk reset password.
- Tidak expose/export password plaintext.
- Tidak menyimpan upload di public disk.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.

## Risks / Notes

Sisa pekerjaan:

- Upload file belum dibuat.
- Heading validation belum dibuat.
- Preview validation belum dibuat.
- Conflict handling belum dibuat.
- Execute import belum dibuat.
- Row-level `core_import_records` belum dibuat dari upload.
- App shortcut belum dibuat.
- API internal belum dibuat.
- Data quality dashboard belum dibuat.

Risiko/catatan:

- Template sample rows hanya contoh struktur, belum validasi data real.
- `core_import_batches` dan `core_import_records` tersedia tetapi belum dipakai dalam UI karena upload belum aktif.
- Tahap execute import harus memastikan initial password tidak pernah ditulis ke file/export/log.

## Recommended Next Step

Rekomendasi tahap berikutnya:

- `CORE-IMPORT-2 Upload, Heading Validation & Preview Skeleton`

Scope yang disarankan:

- Upload file protected.
- Private local storage.
- Create `core_import_batches` status `uploaded`.
- Validasi heading against registry.
- Preview rows read-only.
- Tidak execute data master dulu.
