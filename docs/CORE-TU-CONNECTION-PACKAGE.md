# Core TU Connection Package

## Purpose
Dokumen ini menyiapkan paket koneksi read-only TU Farmasi ke Core Farmasi.

Paket ini membantu admin/devops menyiapkan credential staging, ability, endpoint, env TU, dan preflight readiness tanpa membuat SSO, tanpa auto-login, tanpa write-back, dan tanpa menyimpan secret asli di repository.

## App Code
TU memakai app code:

```text
tu-farmasi
```

Core application `tu-farmasi` harus:
- registered di Core Applications.
- active.
- `is_public_visible=false`.
- `requires_login=true`.

## Required Abilities
App client TU staging minimal membutuhkan ability:
- `read:users`
- `read:students`
- `read:lecturers`
- `read:employees`
- `read:study-programs`
- `read:departments`
- `read:app-access`
- `read:leadership`
- `verify:tu-portal-auth`

`verify:tu-portal-auth` hanya memverifikasi login/password Core dan akses aktif `tu-farmasi` untuk portal TU. Ability ini tidak membuat token, session, SSO, auto-login, atau write-back. Jangan memberi write ability karena adapter TU tahap ini read-only.

## Required App Roles
Role aplikasi TU yang perlu tersedia di Core app role catalog:
- `admin-tu`
- `staf-tu`
- `dosen`
- `mahasiswa`
- `validator`
- `penandatangan`

Role ini dipakai untuk `user_app_accesses` TU dan validasi akses aplikasi. Role tidak membuat SSO dan tidak mengganti auth TU.

## Core Endpoints
Endpoint Core yang dibutuhkan TU:
- `GET /api/v1/internal/directory/users`
- `GET /api/v1/internal/directory/students`
- `GET /api/v1/internal/directory/lecturers`
- `GET /api/v1/internal/directory/employees`
- `GET /api/v1/internal/directory/study-programs`
- `GET /api/v1/internal/directory/departments`
- `GET /api/v1/internal/leadership/current`
- `GET /api/v1/internal/apps/tu-farmasi/users/{user}/access`
- `POST /api/v1/internal/apps/tu-farmasi/portal-auth/verify`
- `GET /profile`

Internal API endpoint harus dipanggil dengan app-client headers:
- `X-Core-App-Code`
- `X-Core-Client-Id`
- `X-Core-Client-Secret`
- `Accept: application/json`

Secret tidak boleh dikirim melalui URL.

## TU Portal Password Verification
Endpoint portal auth TU:

```text
POST /api/v1/internal/apps/tu-farmasi/portal-auth/verify
```

Endpoint ini menerima `login` dan `password`, lalu Core memverifikasi password dengan data user Core, memastikan user aktif, dan memastikan user punya `user_app_accesses` aktif untuk `tu-farmasi`. Response sukses hanya berisi identity payload aman dan role app TU. Response gagal selalu generik:

```json
{
  "authenticated": false,
  "has_access": false,
  "reason": "invalid_credentials_or_access"
}
```

Endpoint ini bukan SSO, tidak membuat session lintas aplikasi, tidak membuat token, tidak mengubah password, dan tidak mengembalikan password/hash/token/secret.

## Core Profile URL
Profile Portal Core untuk edit profil utama:

```text
https://core-staging.example.test/profile
```

URL production/staging sebenarnya harus diisi di environment server, bukan repository. Link ini bukan SSO. Jika user belum login Core, Core akan meminta login resmi.

## TU Environment Checklist
Contoh env TU staging tanpa secret asli:

```env
TU_CORE_HTTP_ENABLED=true
TU_CORE_READ_MODE=http-shadow
TU_CORE_BASE_URL=https://core-staging.example.test
TU_CORE_PROFILE_URL=https://core-staging.example.test/profile
TU_CORE_APP_CODE=tu-farmasi
TU_CORE_CLIENT_ID=...
TU_CORE_CLIENT_SECRET=...
TU_CORE_TIMEOUT=5
TU_CORE_CONNECT_TIMEOUT=3
TU_CORE_VERIFY_SSL=true
TU_CORE_FAIL_SILENTLY=true
```

Default repository tetap harus aman:
- `TU_CORE_HTTP_ENABLED=false`
- `TU_CORE_READ_MODE=disabled`
- no real client id.
- no real client secret.

## Creating the API Client in Core Admin
1. Login ke Core Admin sebagai admin yang berwenang.
2. Buka API Clients.
3. Buat client untuk app code `tu-farmasi`.
4. Pilih hanya required abilities.
5. Simpan client id dan one-time secret langsung ke secret manager/staging env.
6. Jangan tulis secret di chat, docs, report, screenshot, log, atau URL.
7. Jalankan readiness command kembali.

## Issuing the API Client by Command
Core menyediakan command aman untuk menyiapkan API client TU:

```bash
php artisan core:issue-tu-api-client
```

Default command adalah dry-run:
- cek application `tu-farmasi`.
- tampilkan required abilities.
- cek active API client yang sudah ada.
- tidak membuat secret.
- tidak menulis database.

Untuk menampilkan env template placeholder tanpa secret asli:

```bash
php artisan core:issue-tu-api-client --show-env-template
```

Untuk membuat client baru hanya di environment yang memang dituju:

```bash
php artisan core:issue-tu-api-client --apply
```

Saat `--apply`, secret plaintext ditampilkan sekali di terminal. Secret harus langsung disimpan ke secret manager/staging env dan tidak boleh masuk docs, report, chat, screenshot, log, atau URL.

Jika active client sudah ada, command tidak membuat duplikat secara default. Untuk rotate secret existing:

```bash
php artisan core:issue-tu-api-client --apply --rotate-existing
```

Jika ada multiple active client dan semua memang perlu dirotasi:

```bash
php artisan core:issue-tu-api-client --apply --rotate-existing --force-rotate
```

Setelah issue/rotate, jalankan:

```bash
php artisan core:tu-connection-readiness
```

Jika verdict sudah `ready_for_staging_config`, lanjutkan konfigurasi TU staging env dan smoke test TU.

## Granting Portal Auth Ability Without Secret Rotation
Jika TU API client sudah ada tetapi belum memiliki ability portal auth, gunakan command:

```bash
php artisan core:grant-tu-api-client-ability
```

Default command adalah dry-run:
- mencari active API client `tu-farmasi`.
- menampilkan missing ability.
- tidak menulis database.
- tidak membaca, menampilkan, atau merotasi secret.

Untuk menambahkan seluruh required abilities yang masih missing tanpa mengganti secret:

```bash
php artisan core:grant-tu-api-client-ability --apply --all-required
```

Command ini hanya menambah ability yang belum ada, mempertahankan ability existing, tidak mengubah `client_id`, dan tidak mengubah `secret_hash`.

## Readiness Command
Jalankan di Core:

```bash
php artisan core:tu-connection-readiness
```

Command ini read-only dan memeriksa:
- app `tu-farmasi` registered/active/non-public.
- required app roles present/missing.
- active API client count.
- ability coverage pada active API client.
- active `user_app_accesses` count.
- internal endpoint registry.
- portal password verify endpoint.
- `/profile` route.
- readiness verdict.

Command tidak menampilkan secret, secret hash, password, atau token.

## TU Smoke Test After Env Is Ready
Setelah credential staging aman dan env TU dikonfigurasi:

```bash
php artisan config:clear
php artisan tu:core-smoke-test
```

Smoke test harus tetap read-only dan harus memverifikasi Core API logs, TU logs, profile link tanpa token, dan tidak ada DB mutation.

## Not Done By This Package
- Tidak membuat real secret di repo.
- Tidak menjalankan real smoke test.
- Tidak mengaktifkan TU default.
- Tidak melakukan cutover.
- Tidak membuat SSO.
- Tidak membuat auto-login.
- Tidak membuat cross-app session.
- Tidak membuat token URL.
- Tidak melakukan write-back.
- Tidak mengubah database TU.
