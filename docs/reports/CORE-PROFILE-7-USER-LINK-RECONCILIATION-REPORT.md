# CORE-PROFILE-7 User Link Reconciliation Report

Tanggal: 2026-06-01

## Masalah

Profile Portal menampilkan `Belum ada profil tertaut` ketika akun yang login tidak memiliki relasi `student`, `lecturer`, atau `employee` melalui kolom `user_id`.

Kasus aktual:

- User login: `farhamzah@ubpkarawang.ac.id`, user ID 21.
- Record dosen Farhamzah ada, tetapi `lecturers.user_id` menunjuk ke user ID 2.
- User ID 2 adalah `admin@sikp.test`, sehingga link profil dosen salah target.

## Best Practice yang Diterapkan

- Email profil resmi menjadi sinyal canonical paling kuat untuk menautkan profil ke user Core.
- Jika profil sudah tertaut ke user lain tetapi email profil cocok dengan user berbeda, sistem menandai `relink`.
- Relink hanya boleh dilakukan jika target user belum punya profil sejenis lain.
- Default command adalah dry-run dan tidak menulis database.
- Apply hanya dilakukan dengan flag eksplisit `--apply`.
- Field identitas dosen bisa di-backfill dari `lecturer_number` hanya jika bentuknya jelas:
  - 10 digit: `nidn`
  - 18 digit: `nip`
  - 16 digit: `nuptk`
- Semua apply dicatat ke `user_activity_logs`.

## Command Baru

```bash
php artisan core:reconcile-profile-user-links
```

Opsi:

- `--apply`
- `--only=students,lecturers,employees`
- `--email=`
- `--identifier=`
- `--backfill-identifiers`

## Contoh Kasus Farhamzah

Dry-run:

```bash
php artisan core:reconcile-profile-user-links --only=lecturers --email=farhamzah@ubpkarawang.ac.id --backfill-identifiers
```

Apply aman:

```bash
php artisan core:reconcile-profile-user-links --apply --only=lecturers --email=farhamzah@ubpkarawang.ac.id --backfill-identifiers
```

## Dampak

- Mencegah kasus profil resmi tidak tampil karena `user_id` salah.
- Membantu admin memperbaiki data lama tanpa query manual.
- Tetap aman karena konflik target profil diblokir.
