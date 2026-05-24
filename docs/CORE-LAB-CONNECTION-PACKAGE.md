# Core Lab Connection Package

## Purpose
Dokumen ini menyiapkan paket koneksi future consumer Lab Farmasi ke Core Farmasi. Paket ini hanya readiness dari sisi Core: app registry, role catalog, required abilities, endpoint mapping, env placeholder, dan SOP aman untuk membuat credential nanti.

Tahap ini tidak membuat real secret, tidak menjalankan smoke test nyata, tidak membuat SSO, tidak membuat auto-login, tidak membuat token URL, dan tidak melakukan write-back.

## App Code
```text
lab-farmasi
```

Core application `lab-farmasi` harus:
- registered di Core Applications.
- active.
- `is_public_visible=false`.
- `requires_login=true`.
- `is_sensitive=false`.

## Required App Roles
Role aplikasi Lab yang perlu tersedia di Core app role catalog:
- `mahasiswa`
- `dosen`
- `laboran`
- `kepala-lab`
- `admin-lab`
- `pengguna-lab`
- `peminjam-alat`
- `teknisi`
- `viewer`

Catatan:
- `kepala-lab`, `pengguna-lab`, `peminjam-alat`, dan `teknisi` adalah app-specific roles.
- Kepala Lab sebagai jabatan resmi sebaiknya diambil dari Core leadership assignments jika dipakai untuk dokumen resmi.

## Required Abilities
Future Lab app client minimal membutuhkan:
- `read:users`
- `read:students`
- `read:lecturers`
- `read:employees`
- `read:study-programs`
- `read:departments`
- `read:app-access`
- `read:leadership`

Jangan memberi write ability. Adapter Lab awal harus read-only.

## Core Endpoints
Endpoint Core yang dibutuhkan Lab:
- `GET /api/v1/internal/directory/users`
- `GET /api/v1/internal/directory/students`
- `GET /api/v1/internal/directory/lecturers`
- `GET /api/v1/internal/directory/employees`
- `GET /api/v1/internal/directory/study-programs`
- `GET /api/v1/internal/directory/departments`
- `GET /api/v1/internal/leadership/current`
- `GET /api/v1/internal/apps/{app_code}/users/{user}/access`
- `GET /profile`

Internal API harus dipanggil dengan app-client headers:
- `X-Core-App-Code`
- `X-Core-Client-Id`
- `X-Core-Client-Secret`
- `Accept: application/json`

Secret tidak boleh dikirim lewat URL.

## Core Profile URL
Profile Portal Core untuk edit profil utama:

```text
https://core-staging.example.test/profile
```

URL sebenarnya harus diisi di environment server Lab, bukan repository. Link ini bukan SSO. Jika user belum login Core, Core akan meminta login resmi.

## Lab Environment Placeholder
Contoh env future Lab tanpa secret asli:

```env
LAB_CORE_HTTP_ENABLED=false
LAB_CORE_READ_MODE=disabled
LAB_CORE_BASE_URL=
LAB_CORE_PROFILE_URL=
LAB_CORE_APP_CODE=lab-farmasi
LAB_CORE_CLIENT_ID=
LAB_CORE_CLIENT_SECRET=
LAB_CORE_TIMEOUT=5
LAB_CORE_CONNECT_TIMEOUT=3
LAB_CORE_VERIFY_SSL=true
LAB_CORE_FAIL_SILENTLY=true
```

Default Lab harus tetap disabled sampai adapter dan smoke test staging dibuat.

## Readiness Command
Jalankan di Core:

```bash
php artisan core:app-connection-readiness lab-farmasi
```

Command ini read-only dan memeriksa:
- app registry `lab-farmasi`.
- required app roles.
- active API client count.
- ability coverage pada active API client.
- active `user_app_accesses` count.
- internal endpoint registry.
- `/profile` route.
- readiness verdict.

Command tidak menampilkan secret, secret hash, password, atau token.

## Creating API Client Later
Credential Lab belum dibuat pada tahap ini. Saat staging sudah siap:
1. Buat app client `lab-farmasi` dari Core Admin > API Clients.
2. Pilih hanya required abilities.
3. Salin one-time secret langsung ke secret manager/staging env.
4. Jangan tulis secret di docs, report, chat, screenshot, log, atau URL.
5. Jalankan readiness command kembali.
6. Jalankan smoke test Lab setelah adapter Lab dibuat.

## Not Done
- Tidak membuat real API client secret.
- Tidak membuat Lab adapter.
- Tidak mengubah app Lab.
- Tidak menjalankan Lab command.
- Tidak menjalankan smoke test.
- Tidak membuat SSO.
- Tidak membuat auto-login.
- Tidak membuat token URL.
- Tidak write-back.
