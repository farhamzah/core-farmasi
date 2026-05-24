# CORE-AUTH-3 Identity Birth Date Alignment & Initial Password Hardening Report

Tanggal pengerjaan: 2026-05-23

## Scope

Tahap CORE-AUTH-3 menyelaraskan `birth_date` pada identity profiles agar action `Reset Password Awal` bisa bekerja konsisten untuk:

- Students.
- Lecturers.
- Employees / staff.

Perubahan bersifat additive dan non-destruktif. Tidak ada perubahan ke aplikasi KP, SAFA, atau TU.

## Files Changed

File dibuat:

- `database/migrations/2026_05_23_000114_add_birth_date_to_students_and_lecturers_table.php`
- `docs/reports/CORE-AUTH-3-IDENTITY-BIRTH-DATE-ALIGNMENT-REPORT.md`

File diubah:

- `app/Models/Student.php`
- `app/Models/Lecturer.php`
- `app/Services/CoreInitialPasswordService.php`
- `app/Filament/Resources/StudentResource.php`
- `app/Filament/Resources/LecturerResource.php`
- `app/Filament/Resources/UserResource.php`
- `tests/Feature/CoreChangePasswordFlowTest.php`

## Migration

Migration baru:

- `2026_05_23_000114_add_birth_date_to_students_and_lecturers_table.php`

Field additive:

- `students.birth_date` nullable date.
- `lecturers.birth_date` nullable date.

Alasan aman:

- Field nullable, sehingga data existing tidak wajib diisi langsung.
- Tidak menghapus/mengubah kolom existing.
- Tidak mengubah password/login existing.
- Migration dijalankan dengan `php artisan migrate` biasa, bukan fresh/reset/rollback.

Migration status:

- `Ran`, batch `4`.

## Model Changes

Model yang diubah:

- `Student`
  - Menambahkan fillable `birth_date`.
  - Menambahkan cast `birth_date` sebagai `date`.

- `Lecturer`
  - Menambahkan fillable `birth_date`.
  - Menambahkan cast `birth_date` sebagai `date`.

Employee:

- Tidak diubah karena `employees.birth_date` dan cast-nya sudah ada dari CORE-MASTER-1.

## Resource Changes

StudentResource:

- Menambahkan field form `birth_date` dengan label `Tanggal Lahir`.
- Menambahkan column table `birth_date` dengan label `Tanggal Lahir`.
- Column dibuat toggleable hidden by default agar data sensitif tidak terlalu menonjol.

LecturerResource:

- Menambahkan field form `birth_date` dengan label `Tanggal Lahir`.
- Menambahkan column table `birth_date` dengan label `Tanggal Lahir`.
- Column dibuat toggleable hidden by default agar data sensitif tidak terlalu menonjol.

UserResource:

- Action `Reset Password Awal` tetap single-record.
- Birth date resolver dipindahkan ke `CoreInitialPasswordService`.
- Audit metadata ditambah `method: birth_date_based`.

## Initial Password Reset Behavior

Resolver baru:

- `CoreInitialPasswordService::resolveBirthDateForUser(User $user)`

Urutan resolve:

1. `user->student->birth_date`
2. `user->lecturer->birth_date`
3. `user->employee->birth_date`

Behavior jika `birth_date` tersedia:

- Password awal dihitung dari tanggal lahir format `dd/mm/yyyy`.
- Password langsung di-hash.
- `must_change_password` diset `true`.
- `last_password_reset_at` diisi.
- `password_reset_by` diisi dengan admin operator.
- Notification sukses tidak menampilkan password.

Behavior jika `birth_date` kosong/tidak tersedia:

- Action gagal aman.
- Password existing tidak berubah.
- `must_change_password` tidak dipaksa berubah.
- Tidak fallback ke password lemah.
- Tidak membuat password dari NIM/NIDN/NIP/email/identity number.

Konsistensi setelah tahap ini:

- Student: OK, memakai `students.birth_date`.
- Lecturer: OK, memakai `lecturers.birth_date`.
- Employee/staff: OK, tetap memakai `employees.birth_date`.

## Audit Log Behavior

Model audit:

- `UserActivityLog`

Event tetap dicatat:

- `user.initial_password_reset`

Metadata aman:

- `target_user_id`
- `reset_by`
- `method: birth_date_based`

Yang tidak dicatat:

- Password plaintext.
- Hash password.
- Tanggal lahir.
- Token/secret.

## Security Confirmation

Konfirmasi:

- Password plaintext tidak disimpan.
- Password plaintext tidak ditulis ke audit log/report/notification.
- Reset password awal tetap single-record.
- Tidak ada bulk reset password.
- Tidak ada public reset route.
- Tidak ada SSO.
- Tidak ada app shortcut.
- Tidak ada import Excel.
- `/admin/login` tetap login resmi.
- Role access admin tidak dilonggarkan.
- `canAccessPanel()` tidak diubah.

## Commands Run

Command yang dijalankan:

- `php artisan optimize:clear`
  - Result: success.
  - Cache/config/routes/views/blade-icons/filament cleared.

- `php artisan migrate`
  - Result: success.
  - Migration `2026_05_23_000114_add_birth_date_to_students_and_lecturers_table` ran.

- `php artisan test`
  - Result: success.
  - Final result: 49 tests passed, 178 assertions.

- `php artisan migrate:status --path=database\migrations\2026_05_23_000114_add_birth_date_to_students_and_lecturers_table.php`
  - Result: success.
  - Migration status: `Ran`, batch `4`.

- `php artisan route:list --path=change-password`
  - Result: success.
  - Change Password route tetap tersedia di `/admin/change-password`.

Command not run:

- `npm run build`
  - Alasan: tidak ada perubahan frontend asset, CSS, JS, Vite input, atau public build.

## Test Result

`php artisan test` result terakhir:

- Tests: 49 passed.
- Assertions: 178.
- Duration: 43.05s.

Test yang ditambahkan/diperkuat:

- Reset initial password mahasiswa memakai `student.birth_date`.
- Reset initial password dosen memakai `lecturer.birth_date`.
- Reset initial password employee tetap memakai `employee.birth_date`.
- Reset gagal aman jika `birth_date` kosong.
- Password hasil reset hashed, bukan plaintext.
- `must_change_password` menjadi `true` setelah reset.
- `last_password_reset_at` dan `password_reset_by` terisi setelah reset.
- Audit log memakai `method: birth_date_based`.
- Plaintext password tidak muncul di audit metadata.
- Change Password flow tetap lulus.
- Role access admin tetap tidak longgar.

## Guardrails Confirmation

Konfirmasi:

- Tidak menjalankan `migrate:fresh`.
- Tidak menjalankan `migrate:reset`.
- Tidak menjalankan `migrate:rollback`.
- Tidak drop database.
- Tidak menghapus data existing.
- Tidak execute import KP.
- Tidak mengubah database KP.
- Tidak menyentuh `apps/kp-farmasi`.
- Tidak menyentuh `apps/safa-ubp`.
- Tidak menyentuh `apps/tu-farmasi`.
- Tidak membuat Core public.
- Tidak membuat SSO/bypass login.
- Tidak membuat app shortcut.
- Tidak membuat import Excel.
- Tidak membuat public reset route.
- Tidak bulk reset password.
- Tidak expose plaintext password.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.

## Risks / Notes

Risiko/catatan:

- `birth_date` adalah data sensitif, sehingga table column dibuat toggleable hidden by default.
- Data existing Student/Lecturer belum otomatis punya tanggal lahir; perlu diisi manual atau melalui import tahap berikutnya.
- Initial password berbasis tanggal lahir tetap berisiko ditebak, sehingga `must_change_password` dan change password enforcement tetap penting.
- Audit log masih memakai struktur sederhana. Long-term audit bisa ditambah subject model formal.

## Recommended Next Step

Rekomendasi tahap berikutnya:

- `CORE-IMPORT-1 Import Center Skeleton & Template Download`

Catatan untuk tahap import:

- Template Student/Lecturer/Employee harus memasukkan `birth_date`.
- Import harus preview-first.
- Jangan export password plaintext.
- Reset password massal tetap jangan dibuat sampai ada desain permission, audit, dan konfirmasi yang matang.
