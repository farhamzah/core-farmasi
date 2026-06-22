# Lab Farmasi Dev Role Test Users

Dokumen ini hanya untuk local/dev testing Lab Farmasi. Production tetap memakai Core identity/app access sebagai satu pintu login. Tidak ada SSO, token URL, auto-login, login per-role, atau password login baru di Lab.

## Seeder

Jalankan dari `apps/core-farmasi`:

```bash
php artisan db:seed --class=LabFarmasiDevUserSeeder
```

Seeder ini guarded untuk environment `local` dan `testing`. Jika dijalankan di luar environment tersebut, data demo tidak dibuat.

## User Demo

| Nama | Email | Username | Role Lab |
| --- | --- | --- | --- |
| Mahasiswa Lab Demo | lab.demo.mahasiswa@example.test | lab-demo-mahasiswa | mahasiswa |
| Dr. Dosen Lab Demo, M.Farm. | lab.demo.dosen@example.test | lab-demo-dosen | dosen |
| Laboran Lab Demo | lab.demo.laboran@example.test | lab-demo-laboran | laboran |
| Teknisi Lab Demo | lab.demo.teknisi@example.test | lab-demo-teknisi | teknisi |
| Koordinator Lab Demo | lab.demo.koordinator@example.test | lab-demo-koordinator | koordinator_lab |
| Admin Lab Demo | lab.demo.admin@example.test | lab-demo-admin | admin_lab |
| Viewer Lab Demo | lab.demo.viewer@example.test | lab-demo-viewer | viewer |

Setiap user demo hanya diberi satu active `user_app_accesses` untuk `app_code=lab-farmasi` sesuai role pada tabel.

`Dosen Lab Demo` juga dibuat sebagai profil lecturer Core local/dev dengan:

- `front_title`: `Dr.`
- `back_title`: `M.Farm.`
- tampilan resmi/accessor Core: `Dr. Dosen Lab Demo, M.Farm.`

Lab harus menampilkan nama bergelar ini di dropdown `/dev/core-user` dan header setelah user dipilih. Jika data Core production sudah mengisi `front_title`/`back_title`, Lab akan menampilkan nama bergelar dari Core dengan fallback ke `name` lama bila field gelar belum tersedia.

## Cara Test di Lab

1. Pastikan Core dan Lab memakai database local/dev yang sama untuk koneksi read-only Core.
2. Jalankan seeder di Core.
3. Buka Lab:

```text
http://127.0.0.1:8000/dev/core-user
```

4. Pilih user demo dari dropdown `Core user tersedia`.
5. Klik `Pakai User Dev`.
6. Header Lab akan menampilkan user aktif dan role aktif.

Jika user memiliki banyak role dari Core app access, role switcher akan tampil. User demo di dokumen ini sengaja single-role untuk skenario testing per-role.

## Guardrails

- Local/dev only.
- Tidak mengubah production data.
- Tidak membuat login per-role.
- Tidak membuat password login baru di Lab.
- Tidak membuat SSO/token URL/auto-login.
- Tidak melakukan mass grant.
- Production login tetap via Core identity/app access.
