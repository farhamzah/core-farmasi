# CORE Account Request Approval Flow Report

Tanggal: 2026-06-07

## Ringkasan

Fitur Permohonan Akun diperluas dari waiting-list skeleton menjadi alur approval aman:

1. Pendaftar mengisi `/account-request` jika `CORE_PUBLIC_ACCOUNT_REQUEST_ENABLED=true`.
2. Data masuk ke tabel `account_requests` dengan status `pending`.
3. Admin Core meninjau di `/admin/account-requests`.
4. Action `Approve & Buat Akun` membuat atau menautkan:
   - `users`
   - `students`, `lecturers`, atau `employees`
   - role global dasar sesuai jenis pemohon
5. `user_app_accesses` tidak dibuat otomatis.

## Field Baru

Migration additive menambahkan field pendukung:

- `address`
- `birth_date`
- `gender`
- `nip`
- `nidn`
- `nidk`
- `nuptk`
- `staff_type`
- `position_title`

## Kebijakan Approval

- Mahasiswa wajib memiliki email, NIM, dan program studi.
- Dosen wajib memiliki email, nomor utama dosen, dan departemen.
- Tendik/staf/laboran wajib memiliki email, nomor pegawai, dan jenis staf.
- Konflik nomor identitas dengan email berbeda akan memblokir approval.
- Existing user yang cocok akan ditautkan, bukan dibuat duplikat.
- Password awal dibuat memakai kebijakan Core `first_name_identifier_suffix`.
- User baru wajib mengganti password pada login pertama.

## Guardrail

- Tidak meminta password pada form publik.
- Tidak membuat app access otomatis.
- Tidak memberi role admin otomatis.
- Tidak membuka `/admin` untuk non-admin.
- Public form tetap dikontrol oleh env:

```env
CORE_PUBLIC_ACCOUNT_REQUEST_ENABLED=false
```

Aktifkan hanya jika operator siap memproses waiting list:

```env
CORE_PUBLIC_ACCOUNT_REQUEST_ENABLED=true
```

## Validasi

Focused test:

```text
php artisan test tests\Feature\CoreAccountRequestTest.php
17 passed / 105 assertions
```

