# Core Manual QA Login Guide

Tanggal: 2026-06-01

## URLs

- Admin: `http://127.0.0.1:8000/admin/login`
- Profile Portal Login: `http://127.0.0.1:8000/profile/login`
- Profile Portal: `http://127.0.0.1:8000/profile`
- Change Password: `http://127.0.0.1:8000/profile/change-password`

## QA Account Command

Gunakan command berikut untuk melihat rencana akun QA tanpa menulis database:

```bash
php artisan core:manual-qa-accounts
```

Gunakan command berikut untuk menyiapkan akun QA lokal:

```bash
php artisan core:manual-qa-accounts --apply --reset-admin-password --create-users --assign-app-access --show-credentials
```

Credential QA hanya dicetak oleh command untuk kebutuhan manual test lokal. Credential tidak disimpan di dokumen ini.

## Admin Login

1. Buka `http://127.0.0.1:8000/admin/login`.
2. Login memakai credential admin QA yang dicetak oleh command.
3. Pastikan dashboard admin terbuka.
4. Pastikan menu master data, Import Center, Roles, dan User App Accesses terlihat sesuai role `super-admin`.

## User Profile Login

1. Buka `http://127.0.0.1:8000/profile/login`.
2. Login memakai username/password awal QA yang dicetak oleh command.
3. Untuk user dengan `must_change_password=true`, pastikan langsung diarahkan ke `http://127.0.0.1:8000/profile/change-password`.
4. Ganti password dengan current password awal dan password baru.
5. Pastikan password lama tidak bisa dipakai lagi.
6. Jika profil belum lengkap, pastikan setelah ganti password diarahkan ke `http://127.0.0.1:8000/profile/edit`.
7. Lengkapi phone/address yang aman diedit.
8. Pastikan `/profile` menampilkan indikator kelengkapan profil dan tombol logout.

## Admin Creates User Manually

1. Login sebagai Admin Core.
2. Buka `Users`.
3. Klik `Create`.
4. Isi nama, email, username, identity type, identity number, password awal sementara, dan active status.
5. Untuk mahasiswa, username mengikuti NIM.
6. Untuk dosen, username mengikuti NIDN/NIP/lecturer number.
7. Untuk tendik/staf/laboran, username mengikuti employee number.
8. Pastikan `Must Change Password` aktif untuk akun baru.
9. Simpan.

## Auto User From Master Data

Jika Admin Core membuat master data mahasiswa/dosen/tendik tanpa memilih `user_id`, Core otomatis mencoba membuat atau menautkan user.

Aturan:

- Mahasiswa: username = NIM.
- Dosen: username = NIDN/NIP/lecturer number.
- Tendik/staf/laboran: username = employee number.
- Password awal = `NamaDepan + 4 karakter akhir identifier + !`.
- User baru selalu `must_change_password=true`.
- Role admin dan app access tidak diberikan otomatis.

Contoh:

- Nama: `Andi nurjanah`.
- NIM: `221011402637`.
- Username: `221011402637`.
- Password awal: `Andi2637!`.

Untuk backfill data lama yang sudah telanjur tidak punya user:

```bash
php artisan core:provision-master-users --only=students --identifier=221011402637 --show-passwords
php artisan core:provision-master-users --apply --only=students --identifier=221011402637
```

## Admin Assigns Global Role

1. Login sebagai Admin Core.
2. Buka `Users`.
3. Edit user target.
4. Pilih role global sesuai kebutuhan.
5. Jangan beri `super-admin` atau `admin-core` ke user biasa.
6. Simpan.

## Admin Assigns App Access

1. Login sebagai Admin Core.
2. Buka `User App Accesses`.
3. Klik `Create`.
4. Pilih user.
5. Isi `app_code`.
6. Isi `role_slug`.
7. Set `is_active=true`.
8. Simpan.

Contoh QA app access:

- Mahasiswa: `tu-farmasi:mahasiswa`, `ta-farmasi:mahasiswa`, `lab-farmasi:mahasiswa`.
- Dosen: `tu-farmasi:dosen`, `ta-farmasi:dosen-pembimbing`, `lab-farmasi:dosen`.
- Tendik: `tu-farmasi:staf-tu`, `lab-farmasi:laboran`.

## Manual Test Checklist

- Admin login ke `/admin`.
- Admin membuka dashboard.
- Admin membuka User CRUD.
- Admin membuat user manual.
- Admin membuat student/lecturer/employee manual.
- Admin membuka Import Center.
- Admin membuka Roles.
- Admin membuat User App Access.
- User QA login ke `/profile`.
- User QA login dari `/profile/login`.
- User QA dengan password awal diarahkan ke `/profile/change-password`.
- User QA melihat profil sendiri.
- User QA tidak bisa membuka `/admin`.
- User QA mengganti password sendiri.
- User QA mengubah phone/address aman.
- User QA melihat indikator profil lengkap/belum lengkap.
- User QA bisa logout dari Profile Portal.
- Password/hash/token/secret tidak tampil di UI.
- Tidak ada SSO, token URL, atau auto-login.

## Security Notes

- Password tersimpan hashed di database.
- Password awal QA hanya untuk local manual test.
- User biasa tidak mendapat role global admin.
- App access diberikan eksplisit.
- Consumer apps tetap memakai verifikasi Core sesuai desain integrasi masing-masing.
