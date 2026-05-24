# CORE-AUTH-2 Change Password Flow & Admin Initial Password Reset Report

Tanggal pengerjaan: 2026-05-23

## Scope

Tahap CORE-AUTH-2 membuat flow ganti password untuk authenticated Core admin dan action admin single-record untuk set/reset password awal secara terkontrol.

Fokus tahap ini:

- Membuat halaman `Ganti Password` di panel admin Filament.
- Mengarahkan user dengan `must_change_password = true` ke halaman ganti password.
- Menambahkan action `Reset Password Awal` di UserResource.
- Menggunakan password hash dan tidak menyimpan/menampilkan plaintext.
- Mencatat audit log aman untuk password change/reset.
- Menambah test keamanan dan behavior.

Yang tidak dikerjakan:

- Tidak membuat route public reset password.
- Tidak membuat fitur lupa password publik.
- Tidak membuat bulk reset password.
- Tidak membuat email reset password.
- Tidak membuat SSO.
- Tidak membuat app shortcut.
- Tidak membuat import Excel.
- Tidak mengubah login resmi `/admin/login`.

## Previous Reports Reviewed

Report yang dibaca:

- `docs/reports/CORE-AUTH-1-USERNAME-IDENTITY-PASSWORD-SKELETON-REPORT.md`
- `docs/reports/CORE-MASTER-1-EMPLOYEE-STAFF-SKELETON-REPORT.md`
- `docs/reports/CORE-MASTER-0-ARCHITECTURE-PLANNING-REPORT.md`

## Files Changed

File dibuat:

- `app/Filament/Pages/ChangePassword.php`
- `app/Http/Middleware/EnsureCorePasswordChanged.php`
- `tests/Feature/CoreChangePasswordFlowTest.php`
- `docs/reports/CORE-AUTH-2-CHANGE-PASSWORD-FLOW-REPORT.md`

File diubah:

- `app/Providers/Filament/AdminPanelProvider.php`
- `app/Filament/Resources/UserResource.php`

README tidak diubah karena report ini sudah mencatat hasil tahap dan tidak ada credential/password yang boleh ditulis.

## Change Password Page

Page baru:

- `App\Filament\Pages\ChangePassword`

Route:

- `GET /admin/change-password`
- Route name: `filament.admin.pages.change-password`

Lokasi menu:

- Filament sidebar navigation.
- Group: `Account`.
- Label: `Ganti Password`.
- Icon: `heroicon-o-key`.

Form fields:

- `current_password`
- `new_password`
- `new_password_confirmation`

Validasi:

- Current password wajib diisi.
- Current password harus cocok dengan password user saat ini memakai guard Filament.
- New password wajib diisi.
- New password minimal 8 karakter dan memakai `Password::default()`.
- New password harus confirmed.
- New password tidak boleh sama dengan current password.

Behavior setelah berhasil:

- Password user di-update memakai `Hash::make()`.
- `must_change_password` diset `false`.
- `password_changed_at` diisi `now()`.
- `last_password_reset_at` tidak diubah karena ini bukan reset admin.
- Session password hash guard Filament di-refresh agar session tetap valid.
- Audit log `user.password_changed` dibuat.
- Notification sukses ditampilkan.
- User diarahkan ke dashboard admin.

## Must-Change-Password Enforcement

Middleware baru:

- `App\Http\Middleware\EnsureCorePasswordChanged`

Registrasi:

- Didaftarkan hanya di Filament admin panel auth middleware melalui `AdminPanelProvider`.
- Tidak didaftarkan global, sehingga tidak mengganggu API atau route lain.

Logic:

- Jika guest, middleware membiarkan auth middleware Filament menangani redirect login.
- Jika user login dan `must_change_password = false`, request lanjut normal.
- Jika user login dan `must_change_password = true`, request diarahkan ke `/admin/change-password`.

Pencegahan infinite redirect:

- Route `filament.admin.pages.change-password` dikecualikan.
- Route logout Filament dikecualikan.
- Login page tetap ditangani auth flow existing.

Behavior:

- User dengan `must_change_password = true` diarahkan ke Change Password saat akses `/admin`.
- User dengan `must_change_password = false` bisa akses admin normal.
- Unauthorized user tetap ditolak oleh auth/panel access existing.

## Initial Password Reset Action

Action baru:

- `Reset Password Awal` di `UserResource`.
- Single-record table action.
- Menggunakan confirmation modal.
- Tidak ada bulk reset action.

Sumber `birth_date`:

1. `user->student->birth_date` jika relasi dan field tersedia.
2. `user->lecturer->birth_date` jika relasi dan field tersedia.
3. `user->employee->birth_date` jika relasi dan field tersedia.

Kondisi saat ini:

- `Employee` sudah punya `birth_date`.
- `Student` dan `Lecturer` belum punya `birth_date`, sehingga action akan aman gagal jika hanya profile itu yang tersedia tanpa field tanggal lahir.

Behavior jika `birth_date` tidak tersedia:

- Action berhenti aman.
- Notification: tanggal lahir belum tersedia.
- Password existing tidak diubah.
- Tidak fallback ke password lemah.
- Tidak membuat password dari NIM/NIP/identity number.

Behavior jika `birth_date` tersedia:

- Menggunakan `CoreInitialPasswordService`.
- Password awal dihitung dari tanggal lahir dengan format `dd/mm/yyyy`.
- Password langsung di-hash.
- `must_change_password` diset `true`.
- `last_password_reset_at` diisi.
- `password_reset_by` diisi dengan admin operator.
- Audit log `user.initial_password_reset` dibuat.
- Notification sukses tidak menampilkan password plaintext.

Keamanan:

- `Hash::make()` digunakan oleh service.
- Plaintext password tidak disimpan.
- Plaintext password tidak ditulis ke log/report/notification.
- Tidak ada email/reset export.

## Audit Log

Model audit yang digunakan:

- `App\Models\UserActivityLog`

Event yang dicatat:

- `user.password_changed`
- `user.initial_password_reset`

Metadata aman:

- `target_user_id`
- `changed_by` atau `reset_by`

Yang tidak dicatat:

- Password plaintext.
- Tanggal lahir.
- Hash password.
- Token/secret.

Catatan:

- Struktur `user_activity_logs` masih sederhana, tetapi cukup untuk audit dasar tahap ini.
- Audit lebih rinci bisa diperkuat pada tahap data quality/security berikutnya.

## Security Confirmation

Konfirmasi:

- Login resmi tetap `/admin/login`.
- `/admin` tetap protected.
- `canAccessPanel()` tidak dilonggarkan.
- Role access existing tidak dilemahkan.
- Tidak ada route public reset password.
- Tidak ada fitur lupa password publik.
- Tidak ada SSO.
- Tidak ada app shortcut.
- Tidak ada import Excel.
- Tidak ada bulk reset password.
- Tidak ada plaintext password di DB/log/report/notification.
- Tidak ada hardcoded secret/credential.

## Commands Run

Command yang dijalankan:

- `php artisan optimize:clear`
  - Result: success.
  - Cache/config/routes/views/blade-icons/filament cleared.

- `php artisan test`
  - Result: success.
  - Final result: 47 tests passed, 162 assertions.

- `php artisan route:list --path=change-password`
  - Result: success.
  - `GET /admin/change-password` registered to `App\Filament\Pages\ChangePassword`.

- `php artisan route:list --path=users`
  - Result: success.
  - UserResource routes tetap terdaftar di `/admin/users`.

- `Test-Path public\build\manifest.json`
  - Result: `True`.
  - Asset build existing tetap tersedia.

Command not run:

- `php artisan migrate`
  - Alasan: tahap ini tidak membuat migration baru.

- `npm run build`
  - Alasan: tidak ada perubahan frontend asset, CSS, JS, Vite input, atau public build.

## Test Result

`php artisan test` result terakhir:

- Tests: 47 passed.
- Assertions: 162.
- Duration: 48.32s.

Test baru mencakup:

- Authorized Core admin bisa membuka Change Password page.
- Guest diarahkan ke `/admin/login`.
- Unauthorized user ditolak.
- Password berhasil diganti jika current password benar.
- Password gagal diganti jika current password salah.
- Password baru wajib confirmation.
- Password baru disimpan hashed, bukan plaintext.
- Setelah password berhasil diganti, `must_change_password = false`.
- Setelah password berhasil diganti, `password_changed_at` terisi.
- User dengan `must_change_password = true` diarahkan ke `/admin/change-password`.
- User dengan `must_change_password = false` tidak diarahkan.
- Reset password awal gagal aman jika `birth_date` tidak tersedia.
- Reset password awal berhasil dari `employee.birth_date`.
- Reset password awal menghasilkan hash, men-set `must_change_password = true`, `last_password_reset_at`, dan `password_reset_by`.
- Audit log password change/reset dibuat.

## Manual Check

Checklist:

- Change Password page bisa dibuka authorized user: OK via HTTP test.
- Guest diarahkan ke login: OK via HTTP test.
- Unauthorized user ditolak: OK via HTTP test.
- Password berhasil diganti: OK via Livewire test.
- Must-change-password redirect bekerja: OK via HTTP test.
- Reset initial password action aman: OK via Filament table action test.
- Tidak ada 500 error: OK via full test suite dan route list.

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

- Import Excel belum dibuat.
- App shortcut belum dibuat.
- API internal belum dibuat.
- Data quality dashboard belum dibuat.
- Student dan Lecturer belum memiliki `birth_date`, sehingga reset initial password otomatis untuk profile tersebut belum bisa berjalan tanpa penambahan field di tahap data master/import.
- Audit log masih basic dan belum punya subject model formal.
- Logout session lain setelah password change belum diimplementasikan; session guard saat ini di-refresh untuk session aktif.

Catatan:

- Action reset password awal single-record saja, sesuai guardrail.
- Tidak ada password plaintext yang ditampilkan atau dicatat.
- Flow ini hanya berlaku untuk Core admin panel.

## Recommended Next Step

Rekomendasi tahap berikutnya:

- `CORE-IMPORT-1 Import Center Skeleton & Template Download`

Alasan:

- Identity dan password lifecycle dasar sudah siap.
- Import Center dapat mulai dibuat dengan tetap preview-first, tanpa execute massal langsung.
