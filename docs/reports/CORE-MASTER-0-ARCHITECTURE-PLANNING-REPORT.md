# CORE-MASTER-0 Architecture Planning Report

Tanggal planning: 2026-05-23

## Scope

Tahap CORE-MASTER-0 hanya planning arsitektur untuk Core Farmasi UBP sebagai pusat master data, identity, role/app access, import Excel, internal integration, audit, dan data quality.

Tidak ada implementasi fitur pada tahap ini:

- Tidak membuat migration.
- Tidak menjalankan migration.
- Tidak mengubah database.
- Tidak mengubah flow login/password.
- Tidak membuat import Excel.
- Tidak membuat staff/tendik master data.
- Tidak membuat app shortcut.
- Tidak membuat SSO.
- Tidak menyentuh aplikasi lain.

Dokumen ini menjadi rancangan teknis aman untuk tahap implementasi berikutnya.

## Previous Reports Reviewed

Report yang dibaca:

- `docs/reports/CORE-UI-0-PLANNING-INSPECTION-REPORT.md`
- `docs/reports/CORE-UI-1-ASSET-LOGIN-FIX-REPORT.md`

## Current Baseline

Kondisi Core saat ini:

- Aplikasi target: `apps/core-farmasi`.
- Laravel: `12.60.2`.
- PHP: `8.2.12`.
- Filament: `v5.6.5`.
- Livewire: `v4.3.0`.
- Database default: MySQL.
- Login resmi admin: `/admin/login`.
- Route `/login` tidak dibuat.
- Asset pipeline sudah aktif dari CORE-UI-1:
  - `node_modules` ada.
  - `package-lock.json` ada.
  - `public/build/manifest.json` ada.
  - Filament assets ada di `public/css/filament`, `public/js/filament`, dan `public/fonts/filament`.
- Login UI sudah styled dengan brand `Core Farmasi UBP`.
- `/admin` tetap protected.
- Test terakhir pada tahap ini: 24 passed, 68 assertions.

Model/resource existing:

- `User`
- `Role`
- `Student`
- `Lecturer`
- `Department`
- `StudyProgram`
- `UserAppAccess`
- `UserActivityLog`
- `CoreImportBatch`
- `CoreImportRecord`

Resource Filament existing:

- Users
- Roles
- Departments
- Study Programs
- Students
- Lecturers
- User App Accesses
- User Activity Logs

Auth panel existing:

- `User::canAccessPanel()` hanya mengizinkan user aktif dengan role aktif `super-admin` atau `admin-core`.
- User non-core-admin seperti mahasiswa/dosen biasa tidak dapat masuk panel.
- API token custom saat ini memakai bearer token yang hash-nya disimpan di `users.api_token`.

## Existing Data Model Analysis

### users

Sudah ada:

- Identitas login dasar: `name`, `email`, `password`.
- `api_token` hashed untuk API bearer token.
- `active` untuk status user.
- Relasi ke roles, student, lecturer, app accesses, activity logs.
- Password cast `hashed`.
- Panel access gate untuk `super-admin` dan `admin-core`.

Cukup untuk:

- Login admin Core berbasis email.
- Hub identity dasar ke mahasiswa/dosen.
- API token sederhana untuk fase awal.

Kurang untuk target Core:

- Belum ada `username`.
- Belum ada `identity_type`.
- Belum ada `identity_number`.
- Belum ada `must_change_password`.
- Belum ada `password_changed_at`.
- Belum ada `last_login_at`.
- Belum ada `birth_date` di identity layer atau profile umum.
- Belum ada mekanisme multi-identifier login.
- `api_token` satu field di `users` kurang ideal untuk integrasi antar aplikasi jangka panjang.

Rekomendasi field tambahan nanti:

- `username` unique nullable di awal migrasi, lalu required setelah data siap.
- `identity_type` nullable, contoh `student_number`, `nidn`, `nip`, `nik`, `staff_code`, `email`.
- `identity_number` nullable indexed/unique sesuai strategi.
- `must_change_password` boolean default false.
- `password_changed_at` timestamp nullable.
- `last_login_at` timestamp nullable.
- `last_login_ip` nullable jika dibutuhkan.

Potensi masalah:

- Jika email tetap unique required, data tanpa email akan sulit dibuat.
- Jika username tidak segera dirancang, login berbasis NIM/NIP/NIDN akan menjadi patch di banyak tempat.
- Initial password berbasis tanggal lahir butuh `birth_date` tersedia di profile atau identity metadata, tetapi jangan disimpan/ditampilkan sembarangan.

### roles

Sudah ada:

- `name` unique.
- `label`.
- `description`.
- `active`.
- Relasi many-to-many ke users lewat `user_roles`.

Cukup untuk:

- Role global sederhana.
- Role admin Core dan role domain lain seperti `mahasiswa`, `dosen`, `tata-usaha`, `kaprodi`, `dekan`.

Kurang untuk target Core:

- Belum ada scope aplikasi langsung di role.
- Belum ada permission granular.
- Belum ada grouping role.
- Belum ada metadata system role/non-system role.

Rekomendasi:

- Pertahankan `roles` sebagai role global/persona.
- Gunakan `user_app_accesses.role_slug` untuk role per aplikasi.
- Jika permission granular diperlukan, tambah layer permission nanti, jangan dipaksakan di CORE-MASTER-1.

### user_roles

Sudah ada:

- Pivot `user_id` dan `role_id`.
- Unique `user_id + role_id`.

Cukup untuk:

- Role global user.

Kurang:

- Tidak ada audit siapa assign role.
- Tidak ada active period role.
- Tidak ada app context.

Rekomendasi:

- Tetap sebagai role global.
- Audit role changes via `user_activity_logs`.
- App-specific access tetap di `user_app_accesses`.

### students

Sudah ada:

- `user_id` nullable unique.
- `student_number` unique.
- `name`.
- `email` unique.
- `study_program_id`.
- `enrolled_at`.
- `status`.
- `active`.

Cukup untuk:

- Master mahasiswa dasar.
- Link ke user jika mahasiswa punya akun.

Kurang:

- Belum ada `birth_date` untuk initial password plan.
- Belum ada phone/address/gender/angkatan jika diperlukan.
- Email unique required dapat menyulitkan mahasiswa tanpa email.
- Status masih string bebas, belum enum/controlled vocabulary.
- Belum ada soft deletes.
- Belum ada import source metadata.

Rekomendasi field tambahan nanti:

- `birth_date` nullable.
- `phone` nullable.
- `entry_year` nullable.
- `status` distandarkan: `active`, `inactive`, `graduated`, `leave`, `dropped`.
- Pertimbangkan email nullable unique, bila data real tidak selalu punya email.
- Soft deletes jika data master perlu non-destructive removal.

### lecturers

Sudah ada:

- `user_id` nullable unique.
- `lecturer_number` unique.
- `name`.
- `email` unique.
- `department_id`.
- `study_program_id` nullable.
- `phone`.
- `notes`.
- `active`.

Cukup untuk:

- Master dosen dasar.
- Link ke user.
- Relasi department/prodi.

Kurang:

- Belum memisahkan NIDN, NIP, employee number.
- Belum ada `birth_date`.
- Belum ada status taxonomy.
- Email unique required dapat menyulitkan data tanpa email.
- Belum ada soft deletes.

Rekomendasi:

- Tambah identifier fields atau identity registry agar NIDN/NIP tidak dipaksa masuk satu `lecturer_number`.
- Tambah `birth_date` nullable untuk password flow, dengan akses terbatas.
- Pertimbangkan `lecturer_number` sebagai display primary identifier, sementara `identity_number` di `users`/identity table menjadi login identifier.

### departments

Sudah ada:

- `code` unique.
- `name`.
- `description`.
- `active`.

Cukup untuk:

- Fakultas/departemen dasar.

Kurang:

- Belum ada parent-child structure jika nanti fakultas/departemen/unit lebih kompleks.
- Belum ada type, contoh `faculty`, `department`, `unit`.

Rekomendasi:

- Untuk saat ini cukup.
- Jika kebutuhan unit berkembang, tambah `parent_id` dan `type` pada tahap terpisah.

### study_programs

Sudah ada:

- `department_id`.
- `head_lecturer_id` via migration tambahan.
- `code` unique.
- `name`.
- `description`.
- `active`.

Cukup untuk:

- Master program studi dasar.
- Relasi ke fakultas/departemen.
- Kaprodi/head lecturer.

Kurang:

- Belum ada level/degree metadata.
- Belum ada accreditation/status detail.

Rekomendasi:

- Tambah `degree_level` atau `program_type` jika dibutuhkan dashboard/integrasi.
- Validasi active program saat import mahasiswa/dosen.

### user_app_accesses

Sudah ada:

- `user_id`.
- `app_code`.
- `role_slug`.
- `permissions` JSON.
- `is_active`.
- `activated_at`.
- `deactivated_at`.
- Unique `user_id + app_code + role_slug`.

Cukup untuk:

- Basis app access.
- Dasar shortcut internal nanti.
- Menyatakan user punya akses ke aplikasi tertentu.

Kurang:

- Belum ada app registry formal.
- `app_code` masih free string.
- Belum ada `granted_by`, `revoked_by`, reason, source.
- Belum ada expiry.

Rekomendasi:

- Tambah app registry/config/table.
- Pertahankan `UserAppAccess` sebagai assignment.
- Tambah audit pada perubahan access.
- Jangan jadikan access ini sebagai SSO.

### user_activity_logs

Sudah ada:

- `user_id`.
- `action`.
- `ip_address`.
- `user_agent`.
- `meta` JSON.

Cukup untuk:

- Audit umum.

Kurang:

- Wajib `user_id`, padahal beberapa event bisa system/import/API app.
- Belum ada subject model/table/id.
- Belum ada severity/status.
- Belum ada actor app.

Rekomendasi:

- Long term: tambah audit log lebih kaya atau perluas tabel ini.
- Field yang berguna nanti: `actor_user_id`, `actor_app_code`, `subject_type`, `subject_id`, `event`, `old_values`, `new_values`, `status`.

### core_import_batches

Sudah ada:

- `source`.
- `mode`.
- `status`.
- `started_at`, `finished_at`.
- `operator_id`.
- `options` JSON.
- `summary` JSON.

Cukup untuk:

- Audit batch import KP existing.
- Fondasi import center.

Kurang:

- Belum ada uploaded file metadata.
- Belum ada template type/import type.
- Belum ada status preview/validation decision yang lengkap.
- Belum ada file storage path/private disk indicator.

Rekomendasi:

- Perluasan nanti: `import_type`, `original_filename`, `stored_file_path`, `file_hash`, `row_count`, `valid_count`, `invalid_count`, `conflict_count`, `executed_count`, `failed_count`, `cancelled_at`.
- Simpan uploaded file private, bukan public.

### core_import_records

Sudah ada:

- Link ke batch.
- `source_table`, `source_id`, `source_identifier`.
- `target_table`, `target_id`.
- `action`.
- `payload_snapshot`.
- `message`.
- Index source/target/action.

Cukup untuk:

- Audit per-record import KP.

Kurang:

- Belum ada row number Excel.
- Belum ada validation status.
- Belum ada conflict type.
- Belum ada decision/action selected by admin.
- Belum ada errors/warnings JSON.

Rekomendasi:

- Perluas nanti: `row_number`, `status`, `conflict_type`, `decision`, `errors`, `warnings`, `normalized_payload`, `raw_payload`, `executed_at`.

## Recommended Core Target Architecture

Core target:

- Master Data Center: sumber utama data user, mahasiswa, dosen, staff/employee, departemen, prodi, role, dan app access.
- Identity Center: mengelola akun, username, identity number, active/inactive, password lifecycle, dan link ke profile.
- Role & App Access Center: menyimpan role global dan akses per aplikasi tanpa SSO.
- Excel Import Center: upload, preview, validasi, conflict handling, execute, dan audit.
- Internal Integration Center: API internal protected untuk aplikasi lain membaca data master.
- Audit & Data Quality Center: audit aktivitas penting, import history, dan dashboard kualitas data.

Prinsip:

- Core admin bukan portal publik.
- Core tidak tampil di SAFA public portal.
- Core tetap wajib login walau URL diketik langsung.
- Mahasiswa/dosen/tendik biasa tidak masuk admin Core kecuali ada kebutuhan khusus nanti.
- Aplikasi lain tetap punya guard/login sendiri.
- Integrasi awal memakai API internal dan app access, bukan SSO.

## Staff/Employee Master Data Plan

Kebutuhan:

- Tendik.
- Admin.
- Staf TU.
- Laboran.
- Pegawai non-dosen lain.

Opsi A: Tambah tabel `staff` atau `employees`

Kelebihan:

- Satu profil terstruktur untuk semua non-mahasiswa/non-dosen.
- Mudah dipakai import Excel.
- Mudah dibuat resource Filament.
- Bisa link ke `users` seperti `students` dan `lecturers`.
- Mendukung variasi staff via `staff_type`.

Kekurangan:

- Perlu migration/model/resource baru.
- Perlu penentuan vocabulary `staff_type`.

Opsi B: Simpan semua hanya di `users`

Kelebihan:

- Cepat.
- Tidak perlu tabel profil baru.

Kekurangan:

- Data profile bercampur dengan identity login.
- Sulit menyimpan department/prodi/staff type/birth date/phone/status dengan rapi.
- Sulit membedakan admin sebagai role vs admin sebagai pegawai.
- Kurang cocok untuk Core sebagai master data center.

Opsi C: Tabel terpisah `tendik`, `staf_tu`, `laboran`

Kelebihan:

- Sangat spesifik.

Kekurangan:

- Banyak duplikasi field.
- Import template dan UI menjadi terfragmentasi.
- Perubahan field pegawai harus diulang di banyak tabel.

Rekomendasi:

- Gunakan satu tabel profil baru: `employees` atau `staff`.
- Nama yang direkomendasikan: `employees`, karena lebih umum untuk semua pegawai non-mahasiswa dan non-dosen.
- Gunakan `staff_type` atau `employee_type` untuk membedakan `tendik`, `admin`, `staf_tu`, `laboran`, `other`.

Rancangan field awal:

- `id`
- `user_id` nullable unique, foreign key ke `users`, null on delete.
- `employee_number` nullable unique.
- `nip` nullable unique.
- `nik` nullable unique.
- `staff_code` nullable unique.
- `name`
- `staff_type`, controlled values: `tendik`, `admin`, `staf_tu`, `laboran`, `other`.
- `department_id` nullable.
- `study_program_id` nullable.
- `phone` nullable.
- `email` nullable unique jika real data memungkinkan.
- `birth_date` nullable, access restricted.
- `status` default `active`.
- `is_active` boolean default true.
- `notes` nullable.
- `created_at`, `updated_at`.
- `deleted_at` soft delete jika organisasi ingin non-destructive deletion.

Relasi:

- Employee belongsTo User.
- Employee belongsTo Department.
- Employee belongsTo StudyProgram nullable.

## Identity & Username Plan

Kebutuhan username/login identifier:

- Mahasiswa: NIM.
- Dosen: NIDN/NIP/employee number jika tersedia.
- Tendik/staf/admin/laboran: NIP/NIK/staff_code/email.
- User tetap punya email jika tersedia.
- Identifier harus unique dan tidak bentrok lintas tipe.

Analisis existing:

- `users` belum punya `username`.
- `users` belum punya `identity_type`.
- `users` belum punya `identity_number`.
- `students.student_number` dan `lecturers.lecturer_number` sudah unique, tetapi tidak menjadi identifier login user secara langsung.

Rekomendasi awal:

- Tambahkan field identity di `users` pada tahap CORE-AUTH-1:
  - `username` unique.
  - `identity_type` nullable.
  - `identity_number` nullable.
  - `must_change_password` boolean.
  - `password_changed_at` timestamp nullable.
- Username menjadi login identifier utama untuk user biasa.
- Email tetap disimpan untuk kontak, reset, atau login admin jika diputuskan tetap didukung.
- Saat create/import profile, sistem dapat membuat user linked dengan username dari profile identifier.

Alternative long-term:

- Buat tabel `user_identities` untuk multi-identifier:
  - `user_id`
  - `type`
  - `value`
  - `is_primary`
  - unique `type + value`
- Ini lebih fleksibel jika satu dosen punya NIDN dan NIP sekaligus.

Rekomendasi praktis:

- Fase awal: field `username`, `identity_type`, `identity_number` di `users`.
- Fase lanjutan: pertimbangkan `user_identities` jika kebutuhan multi-identifier nyata.

Validasi:

- Normalize username: trim, lower for email-like, preserve numeric identifiers as string.
- Unique username global.
- Unique identity_number dapat global atau scoped by identity_type; untuk menghindari bentrok, gunakan unique global jika memungkinkan.

## Initial Password & Password Change Plan

Kebutuhan:

- Password awal = tanggal lahir format `dd/mm/yyyy`.
- Password disimpan hashed.
- Password awal sementara.
- User wajib ganti password setelah login pertama.
- User bisa update password setelah login.
- Admin bisa reset password.

Rancangan flow:

1. Admin/import membuat user dengan `birth_date` dari profile.
2. Sistem generate initial password string format `dd/mm/yyyy`.
3. Password langsung di-hash via Laravel hasher.
4. Set `must_change_password = true`.
5. Set `password_changed_at = null`.
6. Saat login berhasil, jika `must_change_password = true`, redirect ke halaman ganti password.
7. Setelah user ganti password:
   - update hash password.
   - set `must_change_password = false`.
   - set `password_changed_at = now()`.
   - tulis audit log.

Admin reset password:

- Reset ke tanggal lahir hanya jika `birth_date` tersedia dan admin diberi peringatan risiko.
- Alternatif lebih aman: generate temporary password/random reset link.
- Semua reset harus audit.

Risiko:

- Tanggal lahir mudah ditebak.
- Birth date adalah data sensitif.
- Import Excel sering membuat birth date tersebar di file.
- Password awal jangan pernah diexport plaintext setelah import.
- Jangan tampilkan password awal di halaman umum; jika perlu, tampilkan sekali di summary terbatas atau lebih baik kirim mekanisme reset aman.

Mitigasi:

- `must_change_password` wajib.
- Login throttling/rate limiting.
- Password policy minimum saat change password.
- Audit reset/change password.
- Jangan expose `birth_date` di API default.
- Imported files private.

Tahap ini tidak mengimplementasikan password flow.

## Excel Import Center Plan

Target import data:

- Users.
- Students.
- Lecturers.
- Staff/employees.
- Roles.
- User role assignment.
- User app accesses.
- Departments.
- Study programs.

Alur:

Upload Excel
→ validate file
→ store private file
→ read heading row
→ identify import type/template
→ parse rows
→ normalize values
→ validate each row
→ detect duplicates/conflicts
→ preview rows
→ show errors/warnings/conflicts
→ admin pilih action per konflik
→ execute import
→ write `core_import_batches`
→ write `core_import_records`
→ show result summary
→ audit activity

Status batch:

- `uploaded`
- `previewed`
- `validated`
- `waiting_decision`
- `executing`
- `executed`
- `partially_failed`
- `failed`
- `cancelled`

Conflict actions:

- `create_new`
- `update_existing`
- `skip`
- `merge_if_safe`
- `mark_error`

Validasi umum:

- Required columns.
- Valid heading names.
- Unique NIM/NIDN/NIP/email/username.
- Valid study program.
- Valid department.
- Valid role.
- Valid app_code.
- Valid date format, especially `birth_date`.
- Valid status.
- Valid active flag.
- No duplicate row inside uploaded file.
- No unauthorized app/role assignment.

Templates:

- Template mahasiswa.
- Template dosen.
- Template staff/tendik/admin/staf TU/laboran.
- Template users.
- Template role assignment.
- Template app access.
- Template departments.
- Template study programs.

Recommended template rules:

- Include clear column names.
- Include sample row.
- Include allowed values sheet.
- Include date format guide.
- Include no password column.
- If initial password is derived from birth date, template includes `birth_date`, not password.

Audit:

- `core_import_batches` stores file/import summary.
- `core_import_records` stores row-level decision/result.
- `user_activity_logs` stores user-level event.

Tahap ini tidak mengimplementasikan import Excel.

## Manual Data Entry Plan

Manual create harus tetap tersedia untuk data kecil.

Resource yang perlu/akan mendukung:

- Create user.
- Create student.
- Create lecturer.
- Create staff/employee.
- Create role.
- Assign role.
- Assign app access.
- Link profile to user.
- Active/inactive status.

Form UX:

- Split sections: identity, profile, organization, access, status.
- Select existing user or create linked user.
- Validate duplicate identifiers before save.
- Show warning jika profile belum linked user.
- Avoid bulk destructive actions by default for sensitive data.

Important:

- Manual create user nanti harus support username and must-change-password.
- Manual create profile dapat optional create linked user.
- Admin should see whether profile has login account or only master record.

## App Access & Integration Plan

Tahap awal:

- Core menyimpan app access.
- Core nanti menyediakan shortcut internal di admin panel.
- Aplikasi lain tetap punya login/guard sendiri.
- Tidak ada SSO dulu.
- Aplikasi lain membaca data Core melalui API internal.
- API protected.

App registry/config:

- `app_code`
- `app_name`
- `app_url`
- `admin_url`
- `is_public_visible`
- `requires_login`
- `is_sensitive`
- `is_active`
- `description`
- `sort_order`

Core value:

- `core-farmasi`
  - `is_public_visible = false`
  - `requires_login = true`
  - `is_sensitive = true`
  - `is_active = true`

Rekomendasi bentuk app registry:

- Fase awal: config `config/farmasi_apps.php`.
- Fase lanjutan: tabel `apps` atau `app_registries` jika admin perlu CRUD aplikasi.

Internal API target:

- `GET /api/v1/health`
- validate token/access.
- get user by id/username/email.
- get student by id/student_number/user.
- get lecturer by id/lecturer_number/user.
- get staff/employee by id/employee_number/user.
- list study programs.
- list departments.
- check app access.
- directory search limited fields.

Security:

- API token/internal app key.
- App code scope.
- Rate limit.
- Object-level authorization.
- No public sensitive data.
- Audit API access if needed.
- Separate user API token from app-to-app integration token long term.

No SSO:

- App access tells whether user should be allowed in an app.
- It does not log the user into that app.
- Each app keeps its own session/guard.

## Dashboard & Data Quality Plan

Dashboard Core should show:

- Total users.
- Total students.
- Total lecturers.
- Total staff/employees.
- Total active/inactive users.
- Total users without role.
- Total users without app access.
- Students without linked user.
- Lecturers without linked user.
- Staff without linked user.
- Duplicate email.
- Duplicate username.
- Duplicate NIM/NIDN/NIP/identity_number.
- Inactive users with active app access.
- Recent imports.
- Recent activity logs.

Data quality checkers:

- Profile has user but user inactive mismatch.
- User active but no role.
- User active but no app access.
- App access active for inactive user.
- Student/lecturer/staff email conflicts.
- Profile duplicate identifiers.
- Missing birth_date for users that need initial password.
- Orphan/invalid department/prodi references.

Dashboard should be admin-only and avoid exposing sensitive fields like birth date by default.

## UI/UX Plan

Navigation grouping:

- Dashboard.
- Master Data:
  - Departments.
  - Study Programs.
  - Students.
  - Lecturers.
  - Staff/Employees.
- Identity & Access:
  - Users.
  - Roles.
  - User Roles.
  - App Access.
  - App Registry.
- Import Center:
  - Upload Import.
  - Import Batches.
  - Templates.
  - Import Records.
- System & Audit:
  - Activity Logs.
  - Data Quality.
  - API/Integration Logs if added.

Resource UI:

- Table search/filter/sort.
- Badge active/inactive/status.
- Filters by role/app/status/prodi/department.
- Clear empty states.
- Forms split into sections.
- Read-only audit metadata.
- Avoid showing sensitive fields unnecessarily.

Import UI:

- Download template buttons.
- Upload form with import type.
- Preview table with row status.
- Conflict filter.
- Per-row decision action.
- Bulk decision for safe conflicts.
- Execute confirmation screen.
- Summary page after import.

## Security Plan

Security principles:

- Core admin only for `super-admin` and `admin-core`.
- Inactive user blocked.
- Role and policy-based resource access.
- Audit logs for important changes.
- Core not public in SAFA.
- No SSO yet.
- No hardcoded secrets.
- Password hashed.
- Forced password change after initial password.
- Upload validation for Excel files.
- Imported files stored private, not public.
- API protected and rate-limited.

Specific recommendations:

- Add policies before broadening resources.
- Restrict import execution to `super-admin`/`admin-core`.
- Validate MIME/extension/file size for Excel.
- Do not store uploaded Excel under public disk.
- Avoid exporting password or birth date.
- Add login throttling if Filament defaults are not enough for future username login.
- Use env/config for internal API keys.
- Rotate integration tokens.
- Log app-to-app API access for sensitive endpoints.

## Recommended Implementation Roadmap

### CORE-MASTER-1: Staff/Employee Master Data Skeleton

Tujuan:

- Tambah master data staff/employee untuk tendik, admin, staf TU, laboran.

Scope:

- Model, migration, Filament resource, basic tests.
- Link optional ke user, department, study program.
- No import yet.

Risiko:

- Medium, karena menambah tabel master baru.

File mungkin disentuh:

- `database/migrations/*`
- `app/Models/Employee.php`
- `app/Filament/Resources/EmployeeResource.php`
- `tests/Feature/*`

Guardrails:

- Migration additive only.
- No data destructive changes.
- No SAFA/KP/TU changes.

Output report:

- `CORE-MASTER-1-STAFF-EMPLOYEE-REPORT.md`

### CORE-AUTH-1: Username, Identity, Initial Password Skeleton

Tujuan:

- Rancang dan implementasi field identity dasar tanpa memaksa seluruh flow kompleks.

Scope:

- Additive fields: `username`, `identity_type`, `identity_number`, `must_change_password`, `password_changed_at`.
- Update UserResource minimal.
- Tests for panel access still strict.
- No public reset flow yet unless scoped.

Risiko:

- Medium/high karena menyentuh auth.

File mungkin disentuh:

- `database/migrations/*`
- `app/Models/User.php`
- `app/Filament/Resources/UserResource.php`
- auth/login customization if needed.
- tests.

Guardrails:

- No bypass login.
- Password hashed only.
- No plaintext password export.

Output report:

- `CORE-AUTH-1-IDENTITY-PASSWORD-SKELETON-REPORT.md`

### CORE-IMPORT-1: Import Center Skeleton and Template Download

Tujuan:

- Buat kerangka import center tanpa execute data massal.

Scope:

- Import batch UI.
- Template download.
- Upload storage private.
- Parse header and basic preview.

Risiko:

- Medium.

File mungkin disentuh:

- Import models/table additive if needed.
- Filament pages/resources.
- storage config.
- tests.

Guardrails:

- No execute import yet.
- No public file storage.

Output report:

- `CORE-IMPORT-1-SKELETON-TEMPLATE-REPORT.md`

### CORE-IMPORT-2: Import Preview Validation for Students/Lecturers/Staff

Tujuan:

- Validasi preview untuk data utama.

Scope:

- Row validation.
- Conflict detection.
- Decision planning.
- No destructive overwrite without explicit choice.

Risiko:

- Medium/high because data quality rules.

File mungkin disentuh:

- Import services.
- Filament preview pages.
- tests.

Guardrails:

- Preview first.
- Execute requires explicit confirmation.
- Audit every row.

Output report:

- `CORE-IMPORT-2-PREVIEW-VALIDATION-REPORT.md`

### CORE-ACCESS-1: App Registry and Internal App Shortcut

Tujuan:

- Formalisasi app registry dan shortcut internal admin.

Scope:

- Config/table app registry.
- Dashboard shortcut based on active `UserAppAccess`.
- No SSO.

Risiko:

- Medium.

File mungkin disentuh:

- `config/farmasi_apps.php`
- Filament widget/page.
- UserAppAccess resource.

Guardrails:

- Shortcut link only.
- No token bridge.
- Core not public.

Output report:

- `CORE-ACCESS-1-APP-REGISTRY-SHORTCUT-REPORT.md`

### CORE-API-1: Internal API Hardening and Directory Endpoints

Tujuan:

- API internal aman dan jelas untuk aplikasi lain.

Scope:

- App token strategy.
- Rate limit.
- Directory endpoints.
- Access check endpoint.

Risiko:

- Medium/high due to security.

File mungkin disentuh:

- routes/api.php.
- middleware.
- controllers.
- config.
- tests.

Guardrails:

- No public sensitive endpoint.
- No SSO.
- No hardcoded token.

Output report:

- `CORE-API-1-INTERNAL-API-HARDENING-REPORT.md`

### CORE-DQ-1: Data Quality Dashboard

Tujuan:

- Dashboard kualitas data dan warning operasional.

Scope:

- Widgets/checkers.
- Duplicate/missing link counters.
- Recent imports/activity.

Risiko:

- Low/medium.

File mungkin disentuh:

- Filament widgets/pages.
- Data quality services.
- tests.

Guardrails:

- Read-only dashboard.
- No sensitive details overexposed.

Output report:

- `CORE-DQ-1-DATA-QUALITY-DASHBOARD-REPORT.md`

### CORE-QA-1: Final Security and Documentation

Tujuan:

- Review keamanan, docs, and readiness.

Scope:

- Test suite.
- Security review.
- Operational runbook.
- Admin docs.

Risiko:

- Low.

File mungkin disentuh:

- docs.
- tests.
- minor config hardening.

Guardrails:

- No broad feature change.
- No destructive command.

Output report:

- `CORE-QA-1-SECURITY-DOCUMENTATION-REPORT.md`

## Commands Run

Command yang dijalankan:

- `php artisan about`
  - Result: success.
  - Ringkasan: Core Farmasi UBP, Laravel 12.60.2, PHP 8.2.12, environment local, database mysql, Filament v5.6.5, Livewire v4.3.0.

- `php artisan route:list`
  - Result: success.
  - Ringkasan: 49 routes. `/admin/login` memakai `App\Filament\Pages\Auth\Login`. Admin routes tetap di `/admin`. API routes tetap di `/api/v1/*`.

- `php artisan test`
  - Result: success.
  - Ringkasan: 24 tests passed, 68 assertions.

Tidak menjalankan `npm run build` pada tahap ini karena CORE-UI-1 sudah memverifikasi asset pipeline dan fokus CORE-MASTER-0 adalah planning arsitektur.

## Test Result

`php artisan test` dijalankan.

Hasil:

- Tests: 24 passed.
- Assertions: 68.
- Duration: 3.81s.

Test memakai environment testing dan tidak mengubah database MySQL Core atau database KP.

## Guardrails Confirmation

Konfirmasi:

- Tidak membuat migration.
- Tidak menjalankan migration.
- Tidak menjalankan `migrate:fresh`.
- Tidak menjalankan `migrate:reset`.
- Tidak menjalankan `migrate:rollback`.
- Tidak drop database.
- Tidak menghapus data.
- Tidak mengubah database KP.
- Tidak execute import KP.
- Tidak menyentuh `apps/safa-ubp`.
- Tidak menyentuh `apps/kp-farmasi`.
- Tidak menyentuh `apps/tu-farmasi`.
- Tidak membuat Core public.
- Tidak membuat SSO/bypass login.
- Tidak membuat app shortcut.
- Tidak membuat import Excel.
- Tidak membuat master data tendik/staf.
- Tidak mengubah flow password.
- Tidak hardcode secret/credential.

## Risks / Notes

Keputusan penting:

- Staff/tendik/admin/staf TU/laboran sebaiknya menggunakan satu tabel profil `employees`, bukan hanya `users` dan bukan banyak tabel kecil.
- Username/identity perlu dirancang sebelum import besar agar tidak terjadi patch login berulang.
- Password awal tanggal lahir punya risiko tinggi dan harus digabung dengan `must_change_password`, throttling, audit, dan larangan export plaintext.
- Import Excel harus preview-first dan conflict-aware, bukan direct import.
- User app access bukan SSO. Tetap gunakan login/guard masing-masing aplikasi.
- App registry sebaiknya dimulai dari config untuk cepat, lalu table jika butuh CRUD.

Risiko tahap berikutnya:

- Menambah identity fields dapat mempengaruhi auth jika tidak dites ketat.
- Import Excel dapat merusak data jika conflict handling tidak matang.
- Birth date dan import files adalah data sensitif.
- API internal harus benar-benar protected agar Core tidak menjadi sumber kebocoran data.
