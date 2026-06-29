# Core External People & Access Design

## Tujuan

Core Farmasi menyimpan identitas umum orang luar Fakultas Farmasi yang perlu dikenali atau login ke aplikasi Farmasi, misalnya pembimbing lapangan KP, penguji luar KP, pembimbing luar TA, penguji luar TA, atau mitra dari industri, rumah sakit, apotek, dan universitas lain.

## Prinsip

- `users.identity_type = external` dipakai untuk orang luar yang membutuhkan akun Core.
- Data umum orang luar disimpan di `external_people`.
- Akses aplikasi tetap diberikan lewat `user_app_accesses`.
- Detail operasional dan assignment tetap berada di aplikasi domain masing-masing.
- Core tidak membuat SSO, auto-login, magic link, atau write-back ke aplikasi konsumer.

## Mapping Peran

| Kebutuhan | Core identity | Master Core | App access |
| --- | --- | --- | --- |
| Pembimbing luar KP | `external` | `external_people` | `kp-farmasi` / `pembimbing-lapangan` |
| Penguji luar KP | `external` | `external_people` | `kp-farmasi` / `penguji-luar` |
| Pembimbing luar TA | `external` | `external_people` | `ta-farmasi` / `pembimbing-luar` |
| Penguji luar TA | `external` | `external_people` | `ta-farmasi` / `penguji-luar` |

## Batas Data

Core menyimpan data umum:

- nama
- email
- telepon
- instansi/perusahaan
- jenis instansi
- jabatan/posisi
- profesi
- alamat
- user Core tertaut

Aplikasi domain tetap menyimpan data khusus:

- KP: penempatan, perusahaan KP, pembimbing lapangan per mahasiswa, penilaian, logbook, sidang KP.
- TA: assignment pembimbing/penguji, proposal, seminar/sidang, revisi, yudisium.
- Lab/TU: detail operasional masing-masing aplikasi.

## Approval Permohonan Akun

Permohonan akun tipe Pembimbing Luar sekarang membuat/menautkan:

1. User Core dengan `identity_type = external`.
2. Profil `external_people`.
3. Role global lama tetap dapat dipakai untuk kompatibilitas.
4. `user_app_accesses` hanya dibuat jika admin mengaktifkan opsi pembuatan akses aplikasi saat approve.

## Catatan Keamanan

- Email user internal tidak boleh dipakai untuk permohonan Pembimbing Luar.
- Orang luar tidak otomatis mendapat akses aplikasi sensitif.
- Akses KP/TA harus eksplisit melalui `User App Access`.
- Password awal tetap sementara dan wajib diganti user.
