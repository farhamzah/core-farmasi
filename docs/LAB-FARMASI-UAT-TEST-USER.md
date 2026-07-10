# Lab Farmasi UAT Test User

Dokumen ini menjelaskan cara menyiapkan satu akun Core untuk percobaan/UAT Lab Farmasi dengan semua role Lab aktif.

## Tujuan

- Mempermudah pengujian UI/UX dan akses role Lab Farmasi.
- Tetap memakai login resmi Core.
- Tidak membuat login per-role di Lab.
- Tidak menyimpan password di repository.
- Tidak membuat SSO, token URL, atau auto-login.

## Akun UAT

- Email: `farhamzah@ubpkarawang.ac.id`
- App: `lab-farmasi`
- Role aktif yang disiapkan:
  - `admin_lab`
  - `koordinator_lab`
  - `laboran`
  - `teknisi`
  - `dosen`
  - `mahasiswa`
  - `viewer`

Password tidak ditulis di dokumen ini. Jika perlu reset password, masukkan melalui environment variable sementara saat menjalankan command.

## Command

Dry-run:

```bash
php artisan core:lab-farmasi-uat-test-user
```

Apply tanpa mengubah password:

```bash
php artisan core:lab-farmasi-uat-test-user --apply
```

Apply dan set/reset password dari environment sementara:

```bash
read -s CORE_LAB_UAT_PASSWORD
export CORE_LAB_UAT_PASSWORD
php artisan core:lab-farmasi-uat-test-user --apply --set-password
unset CORE_LAB_UAT_PASSWORD
```

Jika `APP_ENV=production`, command akan menolak apply kecuali operator menambahkan `--allow-production` setelah review manual.

## Cara Test Di Lab Farmasi

1. Login ke Lab Farmasi melalui halaman login Core.
2. Gunakan akun `farhamzah@ubpkarawang.ac.id`.
3. Setelah login, role switcher akan muncul karena akun punya lebih dari satu role.
4. Pilih role yang ingin diuji:
   - Admin Lab
   - Koordinator/Kepala Lab
   - Laboran
   - Teknisi
   - Dosen
   - Mahasiswa
   - Viewer
5. Ulangi skenario UI/UX per role tanpa membuat user baru.

## Guardrails

- Command hanya menyiapkan satu user eksplisit.
- Tidak ada mass user grant.
- Tidak menghapus user.
- Tidak mencetak password.
- Tidak membuat token URL.
- Tidak mengubah kontrak Core sebagai source of truth.
- Lab tetap consumer app access dari Core.
