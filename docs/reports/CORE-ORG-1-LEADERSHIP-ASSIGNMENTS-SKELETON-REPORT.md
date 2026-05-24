# CORE-ORG-1 Leadership Assignments Skeleton Report

Tanggal pengerjaan: 2026-05-23

## Scope

Tahap CORE-ORG-1 membuat skeleton `leadership_assignments` untuk jabatan resmi/struktural seperti Dekan, Wakil Dekan, Kaprodi, Sekretaris Prodi, Kepala Laboratorium, dan Koordinator KP.

Jabatan struktural pada tahap ini dipisahkan dari role login. Role login tetap menentukan hak akses, sedangkan leadership assignment menentukan siapa pejabat aktif untuk unit tertentu pada tanggal tertentu.

Tahap ini belum membuat app shortcut, SSO, API baru, import leadership assignment, import execute, otomatisasi dokumen, tanda tangan digital, atau sinkronisasi ke KP/TU/SAFA.

## Previous Reports Reviewed

Report yang dibaca:

- `docs/reports/CORE-ACCESS-ORG-0-IDENTITY-ROLES-LEADERSHIP-PLANNING-REPORT.md`
- `docs/reports/CORE-IMPORT-3-ROW-VALIDATION-CONFLICT-DETECTION-REPORT.md`

Report/struktur lain yang ikut diperiksa melalui kode existing:

- `app/Models/Lecturer.php`
- `app/Models/Employee.php`
- `app/Models/Department.php`
- `app/Models/StudyProgram.php`
- `app/Filament/Resources/EmployeeResource.php`
- `database/migrations/*`
- `tests/Feature/*`

## Files Changed

File dibuat:

- `database/migrations/2026_05_23_000115_create_leadership_assignments_table.php`
- `config/core_leadership.php`
- `app/Models/LeadershipAssignment.php`
- `app/Services/CoreLeadershipResolver.php`
- `app/Filament/Resources/LeadershipAssignmentResource.php`
- `app/Filament/Resources/LeadershipAssignmentResource/Pages/ListLeadershipAssignments.php`
- `app/Filament/Resources/LeadershipAssignmentResource/Pages/CreateLeadershipAssignment.php`
- `app/Filament/Resources/LeadershipAssignmentResource/Pages/EditLeadershipAssignment.php`
- `tests/Feature/LeadershipAssignmentTest.php`
- `docs/reports/CORE-ORG-1-LEADERSHIP-ASSIGNMENTS-SKELETON-REPORT.md`

File/folder aplikasi lain tidak diubah.

## Database Changes

Migration baru:

- `2026_05_23_000115_create_leadership_assignments_table.php`

Tabel baru:

- `leadership_assignments`

Field yang dibuat:

- `id`
- `position_type`
- `position_title`
- `unit_type`
- `unit_id`
- `person_type`
- `person_id`
- `title_prefix`
- `title_suffix`
- `official_name_snapshot`
- `decree_number`
- `start_date`
- `end_date`
- `is_active`
- `notes`
- `created_at`
- `updated_at`
- `deleted_at`

Index yang dibuat:

- `position_type`
- `unit_type`
- `unit_id`
- `person_type`
- `person_id`
- `is_active`
- composite `unit_type, unit_id`
- composite `person_type, person_id`
- composite `start_date, end_date`

Soft deletes:

- `deleted_at` tersedia.

Alasan desain:

- Additive dan non-destruktif karena hanya membuat tabel baru.
- Tidak mengubah tabel existing.
- Tidak menghapus atau mengubah `study_programs.head_lecturer_id`.
- Tidak membuat foreign key polymorphic paksa karena `unit_type` dan `person_type` sengaja fleksibel.

## Config

File config:

- `config/core_leadership.php`

Isi config:

- `position_types`
  - `dekan`
  - `wakil_dekan`
  - `kaprodi`
  - `sekretaris_prodi`
  - `kepala_lab`
  - `koordinator_kp`
  - `other`
- `unit_types`
  - `faculty`
  - `department`
  - `study_program`
  - `laboratory`
  - `committee`
  - `other`
- `person_types`
  - `lecturer`
  - `employee`

Catatan:

- Jabatan baru dapat ditambahkan dari config tanpa mengubah database.
- Resource tidak di-hardcode hanya untuk Dekan/Kaprodi.

## Model

Model baru:

- `app/Models/LeadershipAssignment.php`

Fitur model:

- `HasFactory`
- `SoftDeletes`
- `fillable` untuk field leadership assignment.
- casts:
  - `start_date` sebagai `date`
  - `end_date` sebagai `date`
  - `is_active` sebagai `boolean`
  - `deleted_at` sebagai `datetime`

Relasi/resolution:

- `lecturer()` memakai `person_id`.
- `employee()` memakai `person_id`.
- `person` accessor mengembalikan Lecturer atau Employee sesuai `person_type`.
- `person_display_name` memakai `official_name_snapshot` jika ada, lalu fallback ke nama person.
- `unit_label` menampilkan nama `StudyProgram` untuk `unit_type=study_program`, nama `Department` untuk `unit_type=department/faculty`, atau fallback aman.

Scopes:

- `active()`
- `current($date = null)`
- `forPosition($positionType)`
- `forUnit($unitType, $unitId = null)`

## Leadership Resolver

Service baru:

- `app/Services/CoreLeadershipResolver.php`

Method:

- `getCurrentDean($date = null)`
- `getCurrentViceDean($date = null)`
- `getCurrentHeadOfStudyProgram($studyProgramId, $date = null)`
- `getCurrentPosition($positionType, $unitType = null, $unitId = null, $date = null)`

Logic:

- Hanya mengambil `is_active=true`.
- `start_date <= date`.
- `end_date` kosong atau `end_date >= date`.
- Jika ada beberapa assignment aktif, resolver memilih `start_date` terbaru, lalu `id` terbaru.
- Return `LeadershipAssignment|null`.
- Data kosong tidak menyebabkan error 500.

## Filament Resource

Resource baru:

- `app/Filament/Resources/LeadershipAssignmentResource.php`

Navigation:

- Group: `Organization`
- Label: `Jabatan Struktural`
- Icon: `heroicon-o-briefcase`

Pages:

- Index: `/admin/leadership-assignments`
- Create: `/admin/leadership-assignments/create`
- Edit: `/admin/leadership-assignments/{record}/edit`

Table:

- `position_type` badge/label
- `position_title`
- `unit_type` badge/label
- `unit_label`
- `person_type` badge/label
- `person_display_name`
- `decree_number`
- `start_date`
- `end_date`
- `is_active`
- `created_at` toggleable

Filters:

- `position_type`
- `unit_type`
- `person_type`
- `is_active`

Form sections:

- Jabatan
  - `position_type`
  - `position_title`
  - `decree_number`
  - `is_active`
- Unit
  - `unit_type`
  - `unit_id`
- Pejabat
  - `person_type`
  - `person_id`
  - `title_prefix`
  - `title_suffix`
  - `official_name_snapshot`
- Periode
  - `start_date`
  - `end_date`
  - `notes`

Validasi:

- `position_type` required via select options dari config.
- `unit_type` required via select options dari config.
- `person_type` required via select options dari config.
- `person_id` required numeric.
- `start_date` required.
- `end_date` harus `after_or_equal:start_date` jika diisi.

Catatan UI:

- Untuk tahap skeleton, `unit_id` dan `person_id` dibuat sebagai numeric input dengan helper text agar tetap fleksibel dan tidak memaksa desain polymorphic UI yang besar.
- Conditional searchable select dapat dipoles pada tahap berikutnya.

## Dekan/Kaprodi Handling

Implementasi tahap ini:

- Dekan dapat diambil dari active leadership assignment dengan `position_type=dekan`.
- Kaprodi dapat diambil dari active leadership assignment dengan `position_type=kaprodi`, `unit_type=study_program`, dan `unit_id` program studi.
- `study_programs.head_lecturer_id` tetap tidak dihapus dan tidak diubah.
- `head_lecturer_id` masih dapat dipakai sebagai quick reference manual.
- Leadership assignment menjadi sumber jangka panjang untuk jabatan resmi yang date-aware dan historis.
- Jabatan resmi tidak disamakan dengan role login.

## Security Confirmation

Konfirmasi keamanan:

- Resource berada di admin Filament, bukan route public.
- `/admin` tetap protected oleh auth dan `canAccessPanel`.
- `canAccessPanel` tidak diubah dan tidak dilonggarkan.
- Tidak ada route public baru.
- Tidak ada API baru.
- Tidak ada SSO.
- Tidak ada app shortcut.
- Tidak ada import execute.
- Tidak ada perubahan data master dari import.
- Tidak ada password plaintext.
- Tidak ada bulk reset password.

## Commands Run

Command yang dijalankan:

- `php artisan optimize:clear`
  - Result: OK.
  - Cache/config/routes/views/Filament cache dibersihkan.
- `php artisan migrate`
  - Result: OK.
  - Migration `2026_05_23_000115_create_leadership_assignments_table` berhasil dijalankan.
- `php artisan test`
  - Result: OK.
  - Final result: 79 passed / 309 assertions.
- `php artisan route:list --path=leadership-assignments`
  - Result: OK.
  - Routes tersedia:
    - `GET|HEAD admin/leadership-assignments`
    - `GET|HEAD admin/leadership-assignments/create`
    - `GET|HEAD admin/leadership-assignments/{record}/edit`

`npm run build` tidak dijalankan karena tidak ada perubahan frontend CSS/JS/Vite asset.

## Test Result

`php artisan test` berhasil.

Hasil:

- Tests: 79 passed.
- Assertions: 309.
- Duration: 44.56s.

Test baru mencakup:

- Tabel `leadership_assignments` dan kolom utama tersedia.
- Cast model bekerja.
- Resolver mengambil Dekan aktif.
- Resolver mengambil Kaprodi aktif berdasarkan study program.
- Resolver mengabaikan assignment inactive.
- Resolver mengabaikan assignment expired.
- Resolver memilih assignment dengan `start_date` terbaru jika multiple active.
- Super-admin dapat membuka index/create/edit LeadershipAssignmentResource.
- Guest diarahkan ke `/admin/login`.
- User tanpa role Core admin ditolak.
- Akses resource tidak mengubah count master data user/lecturer/employee.

## Manual Check

Checklist:

- Menu `Jabatan Struktural` tersedia sebagai resource admin: OK.
- Index route `/admin/leadership-assignments` tersedia: OK.
- Create route `/admin/leadership-assignments/create` tersedia: OK.
- Edit route `/admin/leadership-assignments/{record}/edit` tersedia: OK.
- Resolver Dekan aktif: OK via test.
- Resolver Kaprodi aktif: OK via test.
- Guest diarahkan login: OK via test.
- Unauthorized user ditolak: OK via test.
- Tidak ada 500 error pada route resource yang dites: OK.

## Guardrails Confirmation

Konfirmasi guardrails:

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
- Tidak membuat import execute.
- Tidak bulk reset password.
- Tidak expose/export password plaintext.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.
- Tidak menghapus `study_programs.head_lecturer_id`.
- Tidak hardcode secret/credential.

## Risks / Notes

Catatan risiko dan sisa pekerjaan:

- UI `unit_id` dan `person_id` masih numeric input dengan helper text; tahap berikutnya bisa dipoles menjadi conditional searchable select.
- Belum ada validasi overlap periode jabatan untuk posisi/unit yang sama.
- Belum ada action sync Kaprodi aktif ke `study_programs.head_lecturer_id`.
- Belum ada import leadership assignment.
- Belum ada API official position untuk aplikasi internal.
- Belum ada app registry/dynamic app roles.
- Belum ada app shortcut.
- Belum ada data quality dashboard untuk missing/expired leadership assignment.

## Recommended Next Step

Rekomendasi tahap berikutnya:

- `CORE-ACCESS-1 Dynamic App Registry & App Role Catalog Planning/Skeleton`.

Alasan:

- Owner membutuhkan aplikasi baru dan role aplikasi baru mudah ditambahkan.
- CORE-ORG-1 sudah memisahkan jabatan resmi dari role login.
- Tahap berikutnya sebaiknya menata app registry dan app role catalog agar `user_app_accesses` tidak terkunci pada daftar role hardcoded.
- Setelah app access makin matang, lanjut ke `CORE-IMPORT-4 Admin Decision UI for Conflicts` atau API internal official position.
