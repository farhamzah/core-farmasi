# CORE-PROFILE-5 Portal Login, First Password Gate & Profile Completion Report

Tanggal: 2026-06-01

## Ringkasan

CORE-PROFILE-5 menambahkan login khusus Profile Portal untuk user biasa, memisahkan akses user dari `/admin/login`, dan menegakkan gate aman untuk first password change serta profile completion.

Tahap ini tidak membuat SSO, tidak membuat token URL, tidak membuat auto-login, tidak membuka `/admin` untuk non-admin, dan tidak mengubah database.

## Route Profile Portal

- `GET /profile/login`: halaman login Profile Portal.
- `POST /profile/login`: submit login user biasa.
- `POST /profile/logout`: logout dari Profile Portal.
- `GET /profile`: halaman profil authenticated user.
- `GET /profile/edit`: edit field kontak aman.
- `PUT /profile`: simpan field kontak aman.
- `GET /profile/change-password`: halaman ganti password Core.
- `PUT /profile/change-password`: simpan password Core baru.
- `GET /profil-saya/login`: redirect ke `/profile/login`.
- `GET /profil-saya`: redirect ke `/profile`.
- `GET /profil-saya/ganti-password`: redirect ke `/profile/change-password`.

## Login Policy

- Guest yang membuka route `/profile*` atau `/profil-saya*` diarahkan ke `/profile/login`.
- Login menerima username/email/nomor identitas.
- Username resmi tetap mengikuti kebijakan:
  - mahasiswa memakai NIM.
  - dosen memakai NIDN/NIP/nomor dosen.
  - tendik/staf/laboran memakai nomor kepegawaian.
- User harus aktif.
- Password diverifikasi memakai hash Core.
- Error credential dibuat generic.
- Tidak ada password/hash/token/secret yang ditampilkan di response.
- Login admin tetap terpisah melalui `/admin/login`.

## First Password Change Gate

- User dengan `must_change_password=true` setelah login diarahkan ke `/profile/change-password`.
- User dengan `must_change_password=true` tidak bisa membuka `/profile` atau `/profile/edit` sebelum mengganti password.
- Ganti password membutuhkan current password dan confirmation.
- Password lama tidak valid setelah ganti password sukses.
- `must_change_password=false` setelah sukses.
- `password_changed_at` diisi.
- Audit `profile.password_changed` dibuat tanpa nilai password lama/baru.

## Profile Completion Gate

- Setelah password awal diganti:
  - jika profil belum lengkap, user diarahkan ke `/profile/edit`.
  - jika profil lengkap, user diarahkan ke `/profile`.
- Completion indicator menampilkan `Profil lengkap` atau `Profil belum lengkap`.
- Warning ditampilkan untuk profil belum lengkap.
- Completion memakai item:
  - linked profile.
  - email.
  - official identifier.
  - phone.
  - address.
- Birth date tidak menjadi syarat completion.

## Safe Profile Update

User tetap hanya boleh mengubah field kontak aman:

- phone.
- address.
- alternate email jika tersedia.

Field resmi tetap terkunci dari Profile Portal:

- NIM.
- NIDN/NIP.
- employee number.
- identity number.
- prodi.
- department.
- status.
- role.
- app access.
- leadership/jabatan.

## File Dibuat/Diubah

- `app/Http/Controllers/ProfileAuthController.php`
- `app/Http/Controllers/ProfilePortalController.php`
- `app/Services/CoreProfilePortalService.php`
- `bootstrap/app.php`
- `routes/web.php`
- `resources/views/profile/login.blade.php`
- `resources/views/profile/show.blade.php`
- `resources/views/profile/edit.blade.php`
- `resources/views/profile/change-password.blade.php`
- `tests/Feature/CoreProfilePortalTest.php`
- `docs/CORE-MANUAL-QA-LOGIN-GUIDE.md`
- `docs/CORE-ACCOUNT-LIFECYCLE-PLAN.md`
- `docs/CORE-CENTRALIZED-PROFILE-PORTAL-PLAN.md`

## Validasi

- `php -l app\Http\Controllers\ProfileAuthController.php`: OK.
- `php -l app\Http\Controllers\ProfilePortalController.php`: OK.
- `php -l routes\web.php`: OK.
- `php artisan optimize:clear`: OK.
- `php artisan route:list --path=profile`: OK, 8 route profile.
- `php artisan route:list --path=admin --method=GET`: OK, 46 route admin GET.
- `npm.cmd run build`: OK.
- `php artisan test --filter=CoreProfilePortalTest`: 24 passed / 128 assertions.
- `php artisan test`: 261 passed / 1462 assertions.

## Security Result

- `/admin` tetap hanya untuk `super-admin` atau `admin-core`.
- Non-admin tetap forbidden dari `/admin`.
- Profile Portal punya login/logout terpisah.
- Tidak ada SSO.
- Tidak ada token URL.
- Tidak ada auto-login ke aplikasi consumer.
- Tidak ada app access otomatis.
- Tidak ada password/hash/token/secret di response atau audit.

## Status

CORE-PROFILE-5 selesai dan aman sebagai baseline Profile Portal login, first password change gate, dan profile completion gate.
