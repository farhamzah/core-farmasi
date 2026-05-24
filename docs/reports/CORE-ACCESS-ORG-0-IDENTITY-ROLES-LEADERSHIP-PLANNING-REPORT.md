# CORE-ACCESS-ORG-0 Identity, Roles & Leadership Planning Report

Tanggal planning: 2026-05-23

## Scope

Tahap CORE-ACCESS-ORG-0 hanya planning identity, app-specific roles, dan leadership positions.

Tidak ada implementasi fitur pada tahap ini:

- Tidak membuat migration.
- Tidak menjalankan migration.
- Tidak mengubah database.
- Tidak membuat app shortcut.
- Tidak membuat SSO.
- Tidak membuat API baru.
- Tidak membuat import execute.
- Tidak mengubah data master.

Dokumen ini menjawab kebutuhan owner tentang satu identity/password Core, role berbeda per aplikasi, dan struktur pejabat seperti Dekan/Kaprodi yang harus bisa dipanggil aplikasi lain untuk dokumen/surat.

## Previous Reports Reviewed

Report yang dibaca:

- `docs/reports/CORE-MASTER-0-ARCHITECTURE-PLANNING-REPORT.md`
- `docs/reports/CORE-MASTER-1-EMPLOYEE-STAFF-SKELETON-REPORT.md`
- `docs/reports/CORE-AUTH-1-USERNAME-IDENTITY-PASSWORD-SKELETON-REPORT.md`
- `docs/reports/CORE-AUTH-3-IDENTITY-BIRTH-DATE-ALIGNMENT-REPORT.md`
- `docs/reports/CORE-IMPORT-1-IMPORT-CENTER-SKELETON-REPORT.md`
- `docs/reports/CORE-IMPORT-2-UPLOAD-HEADING-PREVIEW-REPORT.md`

## Current Baseline

Kondisi Core saat ini:

- Login resmi admin: `/admin/login`.
- `/admin` tetap protected oleh Filament panel.
- Test terakhir tahap ini: 64 passed / 237 assertions.
- User identity sudah memiliki:
  - `username`
  - `identity_type`
  - `identity_number`
  - `must_change_password`
  - `password_changed_at`
  - `last_password_reset_at`
  - `password_reset_by`
- Password disimpan hashed.
- Change password flow sudah aktif untuk Core admin.
- Initial password reset dari birth date sudah hardened untuk Student/Lecturer/Employee.
- `students.birth_date`, `lecturers.birth_date`, dan `employees.birth_date` tersedia.
- Employees/tendik/staf/laboran sudah memiliki skeleton master data.
- Import Center sudah memiliki template download, upload private, heading validation, preview skeleton, dan batch metadata.
- App-specific access sudah ada melalui `user_app_accesses`.
- API internal awal sudah ada untuk health, auth token, users, students, lecturers, dan study programs.

Model utama yang relevan:

- `User`
- `Role`
- `UserAppAccess`
- `Student`
- `Lecturer`
- `Employee`
- `Department`
- `StudyProgram`

Struktur jabatan existing:

- `StudyProgram` sudah memiliki `head_lecturer_id`.
- Belum ada tabel leadership/official assignment.
- Belum ada struktur Dekan/Wakil Dekan/Sekretaris Prodi yang historis dan date-aware.

## Central Identity Plan

Core menjadi pusat identity.

Prinsip:

- `users` menyimpan login utama.
- Username/password berasal dari Core.
- Password selalu hashed.
- Aplikasi lain tidak boleh menyimpan password sendiri jika nanti sudah integrasi identity.
- Aplikasi lain boleh tetap punya local session/guard, tetapi sumber identity harus Core.
- SSO belum dibuat sekarang.

Makna “1 user/password untuk semua aplikasi”:

- User memiliki satu akun Core sebagai identity canonical.
- Aplikasi lain melakukan validasi identity ke Core atau menyinkronkan identity dari Core.
- Setelah authenticated di aplikasi lain, aplikasi itu tetap membuat session lokalnya sendiri.
- Password tidak digandakan ke database aplikasi lain.
- Password plaintext tidak pernah dikirim/ditulis ke log/export.

Fase aman:

1. Core menyimpan identity canonical.
2. Aplikasi lain tetap login/guard sendiri, tetapi validasi user/access ke Core.
3. App-specific role dicek dari Core.
4. SSO/token bridge baru dibahas setelah model access dan security matang.

## Global Role vs App-Specific Role Plan

Role global dan role aplikasi harus dipisah.

Role global di Core:

- Disimpan di `roles`.
- Menjawab “orang ini kategori apa secara umum?”
- Contoh:
  - `super-admin`
  - `admin-core`
  - `mahasiswa`
  - `dosen`
  - `employee`

Role per aplikasi:

- Disimpan di `user_app_accesses`.
- Menjawab “orang ini boleh melakukan apa di aplikasi tertentu?”
- Field existing:
  - `user_id`
  - `app_code`
  - `role_slug`
  - `permissions`
  - `is_active`
  - `activated_at`
  - `deactivated_at`

Contoh:

User Dr. A:

- Global: `dosen`
- `kp-farmasi`: `pembimbing_dalam`
- `kp-farmasi`: `penguji`
- `tu-farmasi`: `dosen`
- `dossier-dosen`: `dosen`

Catatan penting:

- `pembimbing` dan `penguji` bukan identitas utama.
- `pembimbing` dan `penguji` adalah peran fungsional pada aplikasi tertentu atau assignment domain tertentu.
- Role global jangan dipakai untuk semua authorization aplikasi.
- Role app jangan dipakai sebagai jabatan resmi untuk dokumen.

## Student Role Plan

Mahasiswa relatif sederhana:

- Profil utama: `Student`.
- User linked: `students.user_id`.
- Global role: `mahasiswa`.
- App-specific roles:
  - `kp-farmasi`: `mahasiswa`
  - `tu-farmasi`: `mahasiswa`
  - `tracer-study`: `alumni` jika nanti ada.

Rekomendasi:

- Tetap gunakan `user_app_accesses` untuk kontrol per aplikasi.
- Jangan menganggap semua mahasiswa otomatis punya semua akses aplikasi.
- Status mahasiswa di `students.status` tetap dipakai sebagai data akademik.
- Active/inactive user tetap dipakai untuk akses login.

## Lecturer Role Plan

Dosen dapat memiliki beberapa lapisan:

- Profil master: `Lecturer`.
- Global role: `dosen`.
- App-specific roles:
  - `kp-farmasi`: `pembimbing_dalam`
  - `kp-farmasi`: `penguji`
  - `kp-farmasi`: `koordinator`
  - `tu-farmasi`: `dosen`
  - `dossier-dosen`: `dosen`
- Structural position:
  - `kaprodi`
  - `dekan`
  - `wakil_dekan`
  - `sekretaris_prodi`
  - `kepala_lab`

Pemisahan penting:

- Dosen sebagai global role berarti orang tersebut adalah dosen.
- Pembimbing/penguji adalah role/assignment aplikasi atau domain akademik.
- Kaprodi/Dekan adalah jabatan organisasi resmi.
- Jabatan organisasi tidak cukup disimpan sebagai role login karena dokumen resmi butuh pejabat aktif, periode, dan unit yang dipimpin.

## Kaprodi Plan

Kondisi existing:

- `study_programs` memiliki `head_lecturer_id`.
- `StudyProgram::headLecturer()` sudah tersedia.

Kegunaan saat ini:

- Bisa menjadi quick reference Kaprodi/head lecturer aktif per program studi.
- Cukup untuk kebutuhan sederhana seperti menampilkan nama Kaprodi saat ini.

Kelemahan jika hanya memakai `head_lecturer_id`:

- Tidak ada riwayat jabatan.
- Tidak ada `start_date` dan `end_date`.
- Tidak ada dasar SK/decree number.
- Sulit mencatat Sekretaris Prodi/Wakil Kaprodi.
- Sulit audit perubahan pejabat.
- Sulit query pejabat pada tanggal dokumen tertentu.

Rekomendasi:

- Pertahankan `study_programs.head_lecturer_id` sebagai quick reference/cache saat ini.
- Untuk arsitektur jangka panjang, buat `leadership_assignments`.
- `head_lecturer_id` dapat disinkronkan dari active leadership assignment bertipe `kaprodi` jika nanti diperlukan.

## Dekan Plan

Dekan tidak cocok hanya disimpan sebagai role `dekan`.

Alasan:

- Role `dekan` hanya menjawab hak akses/login.
- Dokumen resmi butuh pejabat aktif yang memegang jabatan pada tanggal tertentu.
- Dekan punya unit/fakultas yang dipimpin.
- Jabatan punya periode efektif.
- Jabatan perlu riwayat.
- Jabatan dapat berubah tanpa harus mengubah role aplikasi.
- Dekan bisa juga punya role app tertentu, tetapi itu berbeda dari jabatan resmi.

Rekomendasi:

- Dekan disimpan sebagai leadership assignment.
- User/lecturer/employee yang menjabat Dekan boleh memiliki role global/app sesuai kebutuhan akses, tetapi dokumen mengambil nama pejabat dari leadership assignment.

Contoh query konseptual:

- Dekan Fakultas Farmasi saat ini:
  - `position_type = dekan`
  - `unit_type = faculty`
  - `unit_id = Fakultas Farmasi`
  - `is_active = true`
  - `start_date <= today`
  - `end_date is null or end_date >= today`

## Recommended Leadership Structure

Rekomendasi minimal tahap implementasi nanti:

`leadership_assignments`

- `id`
- `position_type`
  - `dekan`
  - `wakil_dekan`
  - `kaprodi`
  - `sekretaris_prodi`
  - `kepala_lab`
  - `koordinator_kp`
  - `other`
- `unit_type`
  - `faculty`
  - `department`
  - `study_program`
  - `lab`
  - `committee`
- `unit_id` nullable atau morph reference.
- `person_type`
  - `lecturer`
  - `employee`
- `person_id`
- `title_prefix` nullable.
- `official_name_snapshot` nullable.
- `start_date`
- `end_date` nullable.
- `is_active`
- `decree_number` nullable.
- `notes` nullable.
- `created_at`, `updated_at`
- `deleted_at` soft delete jika cocok.

Rationale:

- Bisa menyimpan Dekan, Wakil Dekan, Kaprodi, Sekretaris Prodi, Kepala Lab, dan Koordinator KP dalam satu pola.
- Bisa memanggil pejabat aktif untuk dokumen.
- Bisa menyimpan riwayat.
- Bisa memakai lecturer atau employee sebagai pejabat.
- Bisa menyimpan snapshot nama resmi agar dokumen historis tidak berubah jika nama profil diedit.

Cara mencari pejabat aktif:

- Dekan Fakultas Farmasi saat ini:
  - cari assignment `position_type = dekan`
  - unit `faculty`/Fakultas Farmasi
  - active dan date valid.

- Kaprodi S1 Farmasi saat ini:
  - cari assignment `position_type = kaprodi`
  - unit `study_program`
  - `unit_id = S1 Farmasi`
  - active dan date valid.

Policy yang disarankan:

- Untuk satu unit dan satu `position_type`, hanya boleh ada satu active assignment pada periode tanggal yang overlap.
- Perubahan jabatan harus audit.
- Jabatan tidak otomatis memberi app access kecuali admin menambahkan `user_app_accesses`.

## Organization Unit Plan

Kondisi existing:

- `departments` memiliki `code`, `name`, `description`, `active`.
- `study_programs` belongsTo `department`.
- Belum ada `organizational_units`.

Opsi A: Pakai `departments` existing dulu

Kelebihan:

- Cepat.
- Tidak perlu tabel baru untuk tahap awal.
- Cocok jika struktur hanya Fakultas Farmasi + Program Studi.

Kekurangan:

- Nama `departments` kurang fleksibel untuk fakultas/unit/lab/committee.
- Tidak ada `type` atau parent-child.
- Sulit untuk unit seperti lab, panitia, atau pusat layanan.

Opsi B: Buat `organizational_units` nanti

Kelebihan:

- Fleksibel untuk faculty, department, study program, lab, committee.
- Bisa parent-child.
- Leadership assignment bisa menunjuk unit secara konsisten.

Kekurangan:

- Perlu migrasi dan sinkronisasi dengan departments/study_programs.
- Risiko overlap model jika terlalu cepat dibuat.

Rekomendasi:

- Untuk CORE-ORG-1, gunakan pendekatan pragmatis:
  - Support `unit_type = study_program` dengan `unit_id` ke `study_programs`.
  - Support `unit_type = department` dengan `unit_id` ke `departments`.
  - Untuk Fakultas Farmasi, gunakan `departments` existing jika memang record fakultas sudah direpresentasikan di sana.
- Jika kebutuhan unit makin luas, rancang `organizational_units` pada tahap khusus.

## Impact to Import

Dampak ke import:

- CORE-IMPORT-3 tetap bisa lanjut untuk row validation Students/Lecturers/Employees.
- Validasi Students/Lecturers/Employees belum perlu execute leadership assignment.
- Import role/app access nanti harus membedakan:
  - global role: `roles`
  - app-specific role: `user_app_accesses.role_slug`
  - leadership position: `leadership_assignments`
- Import leadership assignment perlu template terpisah.

Template leadership assignment yang disarankan:

- `position_type`
- `unit_type`
- `unit_code`
- `person_identity`
- `person_type`
- `title_prefix`
- `official_name_snapshot`
- `start_date`
- `end_date`
- `is_active`
- `decree_number`
- `notes`

Rekomendasi urutan:

- CORE-IMPORT-3 tetap lanjut karena masih preview/validation dan belum execute import.
- Sebelum app access execute penuh dan sebelum dokumen resmi memakai pejabat otomatis, implement CORE-ORG-1.
- Import execute untuk role/app access sebaiknya menunggu perbedaan global role/app role/leadership makin stabil.

## Impact to API/Integration

Endpoint/fungsi masa depan yang dibutuhkan aplikasi lain:

- Current dean:
  - get Dekan aktif Fakultas Farmasi.
- Current head of study program:
  - get Kaprodi aktif berdasarkan study program code/id.
- Current official signers:
  - daftar pejabat aktif untuk dokumen/surat.
- User app access:
  - cek apakah user punya access aktif ke `app_code` dan `role_slug`.
- Validate app role:
  - cek role user dalam aplikasi tertentu.
- Directory lecturer/employee:
  - pencarian dosen/pegawai dengan field terbatas.
- Identity lookup:
  - get user by username/identity/email.

Security API:

- API harus protected.
- Object-level authorization harus diperhatikan.
- Aplikasi hanya boleh membaca data yang dibutuhkan.
- Sensitive fields seperti password, api_token, birth_date default tidak diexpose.
- Official signer endpoint harus bisa dipakai aplikasi internal, bukan publik.
- App-to-app token lebih baik dipisahkan dari token user jangka panjang.

## Updated Roadmap

Roadmap yang disesuaikan:

1. `CORE-ACCESS-ORG-0`
   - Planning identity, app-specific roles, dan leadership positions.
   - Status: dokumen ini.

2. `CORE-IMPORT-3`
   - Row validation & conflict detection untuk Students/Lecturers/Employees.
   - Scope tetap preview/validation, no execute.
   - Alasan: tidak bergantung penuh pada leadership implementation.

3. `CORE-ORG-1`
   - Implement leadership assignments skeleton.
   - Model/table/resource untuk Dekan/Kaprodi/pejabat.
   - No API public, no SSO.

4. `CORE-IMPORT-4`
   - Admin decision UI for conflicts.
   - Per-row decisions: create/update/skip/error.

5. `CORE-IMPORT-5`
   - Execute import Students/Lecturers/Employees.
   - Audit row-level.
   - No password export.

6. `CORE-ACCESS-1`
   - App registry and app access UI/shortcut internal.
   - No SSO.

7. `CORE-API-1`
   - Internal API for identity/access/official positions.
   - Protected app-to-app integration.

8. `CORE-DQ-1`
   - Data quality dashboard.
   - Duplicate identities, missing app access, missing officials, expired assignments.

## Security Notes

Prinsip keamanan:

- Satu password pusat tidak berarti semua aplikasi boleh bypass auth.
- Aplikasi lain tetap butuh authentication/session/guard yang aman.
- App-specific authorization wajib.
- Role per aplikasi tidak boleh disamakan dengan role global.
- Official position tidak boleh hanya role auth.
- Leadership assignment harus date-aware dan auditable.
- API harus protected.
- Tidak ada SSO sebelum desain token/session matang.
- Tidak ada password plaintext.
- Tidak ada auto-login antar aplikasi.
- Core tidak boleh tampil sebagai portal publik SAFA.

Risiko utama:

- Mencampur role global dan app role akan membuat akses terlalu longgar.
- Menyimpan Dekan/Kaprodi sebagai role saja akan membuat dokumen resmi salah saat jabatan berubah.
- SSO prematur dapat membuka risiko bypass login dan token leakage.
- API official signer tanpa authorization dapat membocorkan data internal.

## Commands Run

Command yang dijalankan:

- `php artisan route:list`
  - Result: success.
  - Ringkasan: 54 routes. Admin routes tetap di `/admin`, termasuk `/admin/import-center` dan `/admin/change-password`. Login resmi tetap `/admin/login`. API existing tetap `/api/v1/*`; tidak ada endpoint leadership baru.

- `php artisan test`
  - Result: success.
  - Final result: 64 tests passed, 237 assertions.

Tidak menjalankan command migration apa pun.

## Test Result

`php artisan test` dijalankan.

Hasil:

- Tests: 64 passed.
- Assertions: 237.
- Duration: 7.56s.

## Guardrails Confirmation

Konfirmasi:

- Tidak membuat migration.
- Tidak menjalankan migration.
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
- Tidak membuat import execute.
- Tidak mengubah data master.
- Tidak bulk reset password.
- Tidak expose password plaintext.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.
- Tidak hardcode secret/credential.

## Recommended Next Step

Rekomendasi:

- Lanjut `CORE-IMPORT-3 Row Validation & Conflict Detection for Students/Lecturers/Employees` dulu.

Alasan:

- CORE-IMPORT-3 masih sebatas row validation dan conflict detection, belum execute.
- Validasi Students/Lecturers/Employees dapat memperkuat kualitas data tanpa menunggu tabel leadership.
- Setelah validation import stabil, lanjut `CORE-ORG-1` untuk implement leadership assignments.
- Sebelum `CORE-ACCESS-1` dan API official signer, leadership structure perlu diimplementasikan agar Dekan/Kaprodi tidak dimodelkan sebagai role auth semata.
