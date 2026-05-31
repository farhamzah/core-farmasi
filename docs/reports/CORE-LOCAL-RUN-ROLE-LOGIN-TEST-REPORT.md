# CORE Local Run, Role Count & Login Test Report

Tanggal test: 2026-06-01

## Scope

Local run, role count, and login access check untuk `apps/core-farmasi`.

Tidak menyentuh aplikasi lain, tidak menjalankan migration destructive, tidak menghapus data, tidak mengubah password, tidak membuat user baru, tidak commit, dan tidak push.

## Runtime

- DB status: OK, `php artisan about` berhasil membaca MySQL database `core_farmasi_ubp`.
- App: `Core Farmasi UBP`.
- Laravel: `12.60.2`.
- PHP: `8.2.12`.
- Environment: `local`.
- Timezone: `Asia/Jakarta`.
- Filament: `v5.6.5`.
- Storage public link: linked.
- `php artisan optimize:clear`: OK.
- `php artisan migrate:status`: OK, semua migration Core yang ada berstatus `Ran`.
- `php artisan route:list --path=admin --method=GET`: OK, 44 routes.
- `php artisan route:list --path=api`: OK, 29 routes.
- `php artisan test`: OK, `220 passed, 1130 assertions`.

## Global Roles

Total global roles: 13.

| ID | Name | Label | Active |
| --- | --- | --- | --- |
| 1 | `super-admin` | Super Admin | yes |
| 2 | `admin-core` | Admin Core | yes |
| 3 | `admin-safa` | Admin SAFA | yes |
| 4 | `admin-kp` | Admin KP | yes |
| 5 | `koordinator-kp` | Koordinator KP | yes |
| 6 | `mahasiswa` | Mahasiswa | yes |
| 7 | `dosen` | Dosen | yes |
| 8 | `pembimbing-dalam` | Pembimbing Dalam | yes |
| 9 | `pembimbing-lapangan` | Pembimbing Lapangan | yes |
| 10 | `penguji` | Penguji | yes |
| 11 | `tata-usaha` | Tata Usaha | yes |
| 12 | `kaprodi` | Kaprodi | yes |
| 13 | `dekan` | Dekan | yes |

Admin roles yang bisa masuk Core Admin sesuai `User::canAccessPanel()`:

- `super-admin`
- `admin-core`

Role lain seperti `admin-kp`, `admin-safa`, `admin-tu`, atau app-specific role tidak otomatis bisa masuk `/admin` Core kecuali user juga punya global role `super-admin` atau `admin-core`.

## App Roles

Total app-specific roles: 50.

### `core-farmasi`

Total: 2.

- `admin-core` | Admin Core | active=yes
- `super-admin` | Super Admin | active=yes

### `helpdesk-farmasi`

Total: 6.

- `admin-helpdesk` | Admin Helpdesk | active=yes
- `agent` | Agent | active=yes
- `requester` | Requester | active=yes
- `supervisor` | Supervisor | active=yes
- `teknisi` | Teknisi | active=yes
- `viewer` | Viewer | active=yes

### `kp-farmasi`

Total: 6.

- `admin-kp` | Admin KP | active=yes
- `koordinator-kp` | Koordinator KP | active=yes
- `mahasiswa` | Mahasiswa | active=yes
- `pembimbing-dalam` | Pembimbing Dalam | active=yes
- `pembimbing-lapangan` | Pembimbing Lapangan | active=yes
- `penguji` | Penguji | active=yes

### `lab-farmasi`

Total: 20.

- `admin_lab` | Admin Lab | active=yes
- `admin-lab` | Admin Lab | active=yes
- `dosen` | Dosen | active=yes
- `kepala-lab` | Kepala Lab | active=yes
- `koordinator_lab` | Koordinator Lab | active=yes
- `lab-admin` | Admin Lab | active=yes
- `lab-asisten` | Asisten Praktikum | active=yes
- `lab-dosen` | Dosen | active=yes
- `lab-kepala-lab` | Kepala Lab | active=yes
- `lab-koordinator` | Koordinator Lab | active=yes
- `lab-laboran` | Laboran | active=yes
- `lab-mahasiswa` | Mahasiswa | active=yes
- `lab-teknisi` | Teknisi | active=yes
- `lab-viewer` | Viewer | active=yes
- `laboran` | Laboran | active=yes
- `mahasiswa` | Mahasiswa | active=yes
- `peminjam-alat` | Peminjam Alat | active=yes
- `pengguna-lab` | Pengguna Lab | active=yes
- `teknisi` | Teknisi | active=yes
- `viewer` | Viewer | active=yes

### `safa-ubp`

Total: 1.

- `admin-safa` | Admin SAFA | active=yes

### `ta-farmasi`

Total: 9.

- `admin-ta` | Admin TA | active=yes
- `dekan` | Dekan | active=yes
- `dosen` | Dosen | active=yes
- `dosen-pembimbing` | Dosen Pembimbing | active=yes
- `kaprodi` | Kaprodi | active=yes
- `koordinator-ta` | Koordinator TA | active=yes
- `mahasiswa` | Mahasiswa | active=yes
- `penguji` | Penguji | active=yes
- `validator` | Validator | active=yes

### `tu-farmasi`

Total: 6.

- `admin-tu` | Admin TU | active=yes
- `dosen` | Dosen | active=yes
- `mahasiswa` | Mahasiswa | active=yes
- `penandatangan` | Penandatangan | active=yes
- `staf-tu` | Staf TU | active=yes
- `validator` | Validator | active=yes

## User Login Candidates

Total users terbaca: 16.

Admin Core candidates:

| User ID | Name | Email | Username | Active | Global Roles |
| --- | --- | --- | --- | --- | --- |
| 1 | Core Farmasi Admin | `admin@core-farmasi.local` | empty | yes | `super-admin` |

Non-admin users yang aktif tetapi tidak bisa masuk `/admin` Core hanya karena role mereka:

| User ID | Email | Global Roles |
| --- | --- | --- |
| 2 | `admin@sikp.test` | `admin-kp` |
| 3 | `koordinator@sikp.test` | `koordinator-kp`, `pembimbing-dalam`, `penguji` |
| 4 | `mahasiswa@sikp.test` | `mahasiswa` |
| 5 | `dosen@sikp.test` | `pembimbing-dalam` |
| 6 | `lapangan@sikp.test` | `pembimbing-lapangan` |
| 7 | `mahasiswa2@sikp.test` | `mahasiswa` |
| 8 | `dosen2@sikp.test` | `pembimbing-dalam` |
| 9 | `penguji@sikp.test` | `penguji` |
| 10-16 | Lab demo users | no global roles; app access only |

Tidak ada password, hash, token, atau secret yang ditampilkan.

## App Access Summary

Total user app accesses: 30.

| App | Count | Sample |
| --- | ---: | --- |
| `core-farmasi` | 1 | user_id=1 role=`super-admin` active=yes |
| `kp-farmasi` | 11 | user_id=2 role=`admin-kp`; user_id=4 role=`mahasiswa`; user_id=6 role=`pembimbing-lapangan` |
| `lab-farmasi` | 14 | user_id=10 role=`mahasiswa`; user_id=15 role=`admin_lab`; user_id=16 role=`viewer` |
| `safa-ubp` | 1 | user_id=1 role=`super-admin` active=yes |
| `ta-farmasi` | 1 | user_id=1 role=`super-admin` active=yes |
| `tu-farmasi` | 2 | user_id=3 role=`dosen`; user_id=4 role=`mahasiswa` |

Catatan: beberapa inactive lab access untuk user_id=1 tetap tercatat tetapi tidak aktif.

## Local URLs

Server lokal:

- PID: `17396`
- URL base: `http://127.0.0.1:8000`

URLs:

- Core Admin login: `http://127.0.0.1:8000/admin/login`
- Profile Portal: `http://127.0.0.1:8000/profile`

HTTP check:

- `/admin/login`: HTTP `200`.
- `/profile` sebagai guest: HTTP `302`, redirect ke login. Ini expected karena Profile Portal butuh authenticated user.

## How To Login

Core Admin:

- Buka `http://127.0.0.1:8000/admin/login`.
- Gunakan email/username user Core admin dan password yang sudah ada di database lokal.
- User harus aktif.
- User harus punya global role aktif `super-admin` atau `admin-core`.
- Pada data saat ini, kandidat admin Core adalah user_id=1, email `admin@core-farmasi.local`.

Profile Portal:

- Buka `http://127.0.0.1:8000/profile`.
- User cukup authenticated.
- User hanya bisa melihat/mengubah profil dirinya sendiri.
- Non-admin tetap tidak bisa masuk `/admin`.

Jika password tidak diketahui:

- Jangan menebak password.
- Jangan reset password otomatis.
- Jika ada admin lain yang bisa login, gunakan flow admin resmi untuk reset password awal.
- Jika tidak ada admin yang bisa login, minta approval owner untuk membuat/reset admin lokal khusus development.

## Commands Run

```bash
php artisan about
php artisan optimize:clear
php artisan migrate:status
php artisan route:list --path=admin --method=GET
php artisan route:list --path=api
php artisan tinker --execute="read global roles"
php artisan tinker --execute="read app-specific roles"
php artisan tinker --execute="read users with roles"
php artisan tinker --execute="read user app access"
php artisan test
Test-NetConnection 127.0.0.1 -Port 8000
Test-NetConnection 127.0.0.1 -Port 8001
php artisan serve --host=127.0.0.1 --port=8000
curl.exe -s -o NUL -w "%{http_code}" http://127.0.0.1:8000/admin/login
curl.exe -s -o NUL -w "%{http_code}" http://127.0.0.1:8000/profile
```

## Security Confirmation

- No password shown: OK.
- No hash shown: OK.
- No token/secret shown: OK.
- No password changed: OK.
- No user created: OK.
- No DB destructive action: OK.

## Guardrails

- No `migrate:fresh`: OK.
- No `migrate:reset`: OK.
- No `migrate:rollback`: OK.
- No drop DB: OK.
- No delete data: OK.
- No password reset without permission: OK.
- No commit/push: OK.
- No app outside Core touched: OK.
