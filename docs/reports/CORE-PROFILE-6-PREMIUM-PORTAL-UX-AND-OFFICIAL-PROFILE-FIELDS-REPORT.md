# CORE-PROFILE-6 Premium Portal UX and Official Profile Fields Report

Tanggal: 2026-06-01

## Tujuan

Meningkatkan Profile Portal Core Farmasi agar terasa lebih layak sebagai halaman awal pengguna, sekaligus menyiapkan tampilan data resmi dosen, mahasiswa, dan tendik yang lebih lengkap.

## Referensi Ringkas

- SISTER menampilkan data pokok dosen seperti tanggal lahir, NIDN/NIDK, NIP, jabatan, kepangkatan, dan pendidikan terakhir pada area profil/layanan terkait.
- Panduan perubahan data dosen SISTER mengelompokkan data profil ke profil dasar, kependudukan/NIK, alamat dan kontak, kepegawaian/NIP, jabatan fungsional, penempatan, kualifikasi, dan kompetensi.
- Informasi Manajemen PTK SISTER menyatakan NUPTK menjadi identitas unik PTK, dengan NIDN/NIDK/NUP/NITK masih dipakai selama masa transisi.
- Layanan PDDikti mahasiswa mencakup perubahan data NIM, nama, nama ibu kandung, tempat/tanggal lahir, jenis kelamin, status mahasiswa, tanggal masuk, alamat, kota, dan kode pos.

## Perubahan

- Menambahkan field identitas resmi dosen:
  - `national_id_number` untuk NIK/No. KTP
  - `nip`
  - `nidn`
  - `nidk`
  - `nuptk`
- Menambahkan field tersebut ke model dan Filament LecturerResource.
- Import Center dosen sekarang bisa menyimpan `nidn`, `nidk`, `nip`, `nuptk`, dan `identity_number` ke profil dosen.
- Internal Directory API dosen dapat mencari/filter field identitas resmi baru.
- Profile Portal menampilkan:
  - hero identitas yang lebih kuat
  - progress kelengkapan profil
  - kontak aman
  - profil resmi tertaut
  - identifier chips untuk NIM/NIDN/NIDK/NIP/NUPTK/NIK
  - standar data utama mahasiswa/dosen/tendik
  - security panel
- Edit Kontak dibuat lebih rapi dan tidak buntu untuk akun tanpa profil tertaut.
- Login Profile Portal diperbarui agar lebih representatif sebagai entry point pengguna.

## Keamanan

- Data resmi tetap read-only untuk user.
- NIK/No. KTP dimasking di Profile Portal.
- User tetap hanya bisa mengubah field kontak aman.
- Password tidak ditampilkan dan tetap diganti melalui halaman khusus.
- Tidak ada app access otomatis.

## Catatan

- Perubahan database bersifat additive.
- Migration dijalankan dengan `php artisan migrate`, bukan `migrate:fresh`.
- Tidak ada perubahan ke aplikasi lain.
