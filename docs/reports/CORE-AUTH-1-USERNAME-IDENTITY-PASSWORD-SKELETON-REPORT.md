# CORE-AUTH-1 Username, Identity, Initial Password & Must-Change-Password Skeleton Report

Tanggal pengerjaan: 2026-05-23

## Scope

Tahap CORE-AUTH-1 membuat skeleton identity/auth dasar secara additive dan non-destruktif.

Fokus tahap ini:

- Menambahkan field identity ke tabel `users`.
- Menyiapkan strategi username dan identity type.
- Menyiapkan service initial password berbasis tanggal lahir dengan hash.
- Menambahkan marker `must_change_password`.
- Menampilkan field identity di UserResource.
- Menambahkan test agar akses admin tetap aman.

Yang tidak dikerjakan:

- Tidak membuat flow ganti password paksa.
- Tidak membuat public reset password route.
- Tidak membuat bulk reset password.
- Tidak membuat SSO.
- Tidak membuat app shortcut.
- Tidak membuat import Excel.
- Tidak mengubah login resmi `/admin/login`.

## Previous Reports Reviewed

Report yang dibaca:

- `docs/reports/CORE-MASTER-0-ARCHITECTURE-PLANNING-REPORT.md`
- `docs/reports/CORE-MASTER-1-EMPLOYEE-STAFF-SKELETON-REPORT.md`

## Files Changed

File dibuat:

- `database/migrations/2026_05_23_000113_add_identity_auth_columns_to_users_table.php`
- `config/core_identity.php`
- `app/Services/CoreInitialPasswordService.php`
- `tests/Feature/CoreAuthIdentityTest.php`
- `docs/reports/CORE-AUTH-1-USERNAME-IDENTITY-PASSWORD-SKELETON-REPORT.md`

File diubah:

- `app/Models/User.php`
- `app/Filament/Resources/UserResource.php`

README tidak diubah karena report ini sudah mencatat hasil tahap dengan cukup jelas dan tidak ada credential/password yang perlu didokumentasikan.

## Database Changes

Migration baru:

- `2026_05_23_000113_add_identity_auth_columns_to_users_table.php`

Field additive yang ditambahkan ke `users`:

- `username` nullable string unique.
- `identity_type` nullable string indexed.
- `identity_number` nullable string indexed.
- `must_change_password` boolean default false.
- `password_changed_at` nullable timestamp.
- `last_password_reset_at` nullable timestamp.
- `password_reset_by` nullable foreign key ke `users`, `nullOnDelete`.

Alasan aman/non-destruktif:

- Semua field identity nullable agar data user existing tidak rusak.
- Tidak ada rename/drop kolom existing.
- Tidak mengubah email login existing.
- Tidak melakukan backfill username otomatis.
- `password_reset_by` nullable dan `nullOnDelete`, sehingga tidak cascade-delete user.

## Model Changes

Model yang diubah:

- `App\Models\User`

Perubahan:

- Menambahkan fillable:
  - `username`
  - `identity_type`
  - `identity_number`
  - `must_change_password`
  - `password_changed_at`
  - `last_password_reset_at`
  - `password_reset_by`
- Menambahkan casts:
  - `must_change_password` as boolean.
  - `password_changed_at` as datetime.
  - `last_password_reset_at` as datetime.
- Menambahkan relasi:
  - `passwordResetBy()` belongsTo `User`.
- Menambahkan helper:
  - `hasIdentity()`
  - `getDisplayIdentityAttribute()`
  - `markPasswordChanged()`
  - `markMustChangePassword()`
  - `clearMustChangePassword()`

`canAccessPanel()` tidak diubah dan tetap hanya mengizinkan user aktif dengan role aktif `super-admin` atau `admin-core`.

## Identity Strategy

Identity type standar disimpan di:

- `config/core_identity.php`

Daftar identity type:

- `student`
- `lecturer`
- `employee`
- `admin`
- `external`
- `system`

Strategi username:

- Mahasiswa: username ideal dari NIM.
- Dosen: username ideal dari NIDN/NIP/lecturer number jika tersedia.
- Employee/tendik/staf/admin/laboran: username ideal dari employee number/NIP/staff code, atau email jika tidak ada nomor.
- Admin/system: boleh tetap memakai email jika username belum disiapkan.

Tahap ini tidak melakukan backfill username otomatis agar tidak mengubah data existing tanpa keputusan eksplisit.

## Initial Password Strategy

Service baru:

- `App\Services\CoreInitialPasswordService`

Tanggung jawab:

- Membuat initial password dari tanggal lahir dengan format `dd/mm/yyyy`.
- Menghasilkan hash password melalui Laravel hasher.
- Men-set password awal hanya saat service dipanggil eksplisit.
- Menandai `must_change_password = true`.
- Mengisi `last_password_reset_at`.
- Mengisi `password_reset_by` jika operator tersedia.
- Mengosongkan `password_changed_at` setelah reset awal.

Keamanan:

- Password disimpan hashed.
- Password plaintext tidak disimpan.
- Password plaintext tidak ditulis ke report.
- Password plaintext tidak diexport.
- Tidak ada bulk reset password.
- Tidak ada public reset endpoint.

Risiko dan mitigasi:

- Tanggal lahir mudah ditebak, sehingga `must_change_password` wajib dipakai di tahap flow berikutnya.
- Birth date adalah data sensitif, sehingga tidak boleh diexpose sembarangan di API/export.
- Reset password harus diaudit dan dibatasi hanya untuk admin Core.

## UserResource Changes

Resource yang diubah:

- `App\Filament\Resources\UserResource`

Form:

- Section `Akun` tetap berisi:
  - name
  - email
  - password
  - active
  - roles
- Section `Identitas Login` baru:
  - `username` nullable unique.
  - `identity_type` select dari config.
  - `identity_number` nullable.
  - `must_change_password` toggle.
  - `password_changed_at` read-only.
  - `last_password_reset_at` read-only.
  - `password_reset_by` read-only relationship.

Table:

- Menambahkan kolom:
  - username
  - identity type badge
  - identity number
  - must change password boolean
  - password changed at toggleable

Filters:

- `identity_type`
- `must_change_password`
- `active`

Reset initial password action:

- Belum dibuat pada tahap ini.
- Alasan: tahap ini skeleton identity/auth. Action reset password perlu desain permission, audit, birth date source, dan UX konfirmasi yang lebih ketat agar tidak membuka risiko operasional.

## Must-Change-Password Plan

Yang sudah dibuat:

- Field `must_change_password`.
- Helper di model User.
- Badge/kolom/filter di UserResource.
- Service untuk menandai user wajib ganti password saat initial password di-set eksplisit.

Yang belum dibuat:

- Halaman change password mandiri.
- Redirect paksa setelah login.
- Middleware aktif untuk memblok akses sebelum password diganti.
- Audit log khusus password change/reset.

Rekomendasi tahap berikutnya:

- Buat CORE-AUTH-2 untuk halaman change password aman.
- Aktifkan redirect paksa hanya setelah halaman change password stabil.
- Tambahkan audit log untuk password reset dan password change.

## Security Confirmation

Konfirmasi:

- Login resmi tetap `/admin/login`.
- `/admin` tetap protected.
- `canAccessPanel()` tidak dilonggarkan.
- Role access existing tidak dilemahkan.
- Tidak ada SSO.
- Tidak ada app shortcut.
- Tidak ada public reset route.
- Tidak ada plaintext password yang disimpan.
- Tidak ada plaintext password di report.
- Tidak ada bulk reset password.
- Tidak ada hardcoded secret/credential.

## Commands Run

Command yang dijalankan:

- `php artisan optimize:clear`
  - Result: success.
  - Cache/config/routes/views/blade-icons/filament cleared.

- `php artisan migrate`
  - Result: success.
  - Migration `2026_05_23_000113_add_identity_auth_columns_to_users_table` ran.

- `php artisan test`
  - Result: success.
  - Final result: 37 tests passed, 123 assertions.

- `php artisan route:list --path=users`
  - Result: success.
  - Routes for `/admin/users`, `/admin/users/create`, and `/admin/users/{record}/edit` registered.

- `php artisan migrate:status --path=database\migrations\2026_05_23_000113_add_identity_auth_columns_to_users_table.php`
  - Result: success.
  - Migration status: `Ran`, batch `3`.

Command not run:

- `npm run build`
  - Alasan: tidak ada perubahan frontend asset, CSS, JS, Vite input, atau public build pada tahap ini.

Catatan:

- `git status` tidak bisa dijalankan karena workspace ini tidak terdeteksi sebagai git repository.

## Test Result

`php artisan test` result terakhir:

- Tests: 37 passed.
- Assertions: 123.
- Duration: 65.40s.

Test baru mencakup:

- `users` table punya kolom identity/auth baru.
- User model fillable/casts bekerja.
- Username unique saat diisi.
- Helper password state bekerja.
- Initial password service menghasilkan format yang benar dan menyimpan password sebagai hash.
- Super-admin masih bisa membuka UserResource.
- User tanpa role Core admin tetap ditolak.

## Manual Check

Checklist:

- UserResource bisa dibuka: OK via test super-admin dan route list.
- Field identity tampil: OK via UserResource form definition dan test resource.
- Filter identity/must_change_password tersedia: OK via UserResource definition.
- Reset action aman: OK, action belum dibuat pada tahap ini sehingga tidak ada reset publik/bulk.
- Guest tetap diarahkan ke login: OK, behavior existing tidak diubah.
- Unauthorized user tetap ditolak: OK via test.
- Tidak ada 500 error: OK via test.

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
- Tidak bulk reset password.
- Tidak expose password plaintext.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.

## Risks / Notes

Risiko/sisa pekerjaan:

- Forced password change page belum dibuat.
- Middleware/redirect wajib ganti password belum diaktifkan.
- Password reset action admin belum dibuat.
- Audit log khusus password reset/change belum dibuat.
- Student dan Lecturer belum memiliki `birth_date`, sehingga initial password berbasis tanggal lahir belum lengkap untuk semua profile.
- Import Excel belum dibuat.
- App shortcut belum dibuat.
- API internal belum dibuat.
- Data quality dashboard belum dibuat.

Catatan penting:

- Service initial password sudah siap, tetapi hanya boleh dipanggil eksplisit oleh flow admin/action yang aman.
- Field baru di `users` masih nullable agar data existing aman.
- Username login belum menggantikan email login pada tahap ini.

## Recommended Next Step

Rekomendasi tahap berikutnya:

- `CORE-AUTH-2 Change Password Flow`

Alasan:

- CORE-AUTH-1 sudah menyiapkan `must_change_password` dan service initial password.
- Tahap berikutnya paling aman adalah membuat halaman user change password, redirect paksa yang scoped, validasi password, dan audit log sebelum membuat import Excel yang bisa membuat banyak akun sekaligus.
