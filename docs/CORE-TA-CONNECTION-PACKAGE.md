# Core TA Connection Package

## Purpose
Dokumen ini menyiapkan paket koneksi future consumer TA Farmasi ke Core Farmasi. Paket ini hanya readiness dari sisi Core: app registry, role catalog, required abilities, endpoint mapping, env placeholder, dan SOP aman untuk membuat credential nanti.

Tahap ini tidak membuat real secret, tidak menjalankan smoke test nyata, tidak membuat SSO, tidak membuat auto-login, tidak membuat token URL, dan tidak melakukan write-back.

## App Code
```text
ta-farmasi
```

Core application `ta-farmasi` harus:
- registered di Core Applications.
- active.
- `is_public_visible=false`.
- `requires_login=true`.
- `is_sensitive=false`.

## Required App Roles
Role aplikasi TA yang perlu tersedia di Core app role catalog:
- `mahasiswa`
- `dosen`
- `dosen-pembimbing`
- `penguji`
- `koordinator-ta`
- `admin-ta`
- `kaprodi`
- `dekan`
- `validator`

Catatan:
- `dosen-pembimbing`, `penguji`, `koordinator-ta`, dan `validator` adalah app-specific roles.
- Dekan/Kaprodi sebagai jabatan resmi tetap sebaiknya diambil dari Core leadership assignments, bukan hanya role login aplikasi.

## Required Abilities
Future TA app client minimal membutuhkan:
- `read:users`
- `read:students`
- `read:lecturers`
- `read:employees`
- `read:study-programs`
- `read:departments`
- `read:app-access`
- `read:leadership`

Jangan memberi write ability. Adapter TA awal harus read-only.

## Core Endpoints
Endpoint Core yang dibutuhkan TA:
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

URL sebenarnya harus diisi di environment server TA, bukan repository. Link ini bukan SSO. Jika user belum login Core, Core akan meminta login resmi.

## TA Environment Placeholder
Contoh env future TA tanpa secret asli:

```env
TA_CORE_HTTP_ENABLED=false
TA_CORE_READ_MODE=disabled
TA_CORE_BASE_URL=
TA_CORE_PROFILE_URL=
TA_CORE_APP_CODE=ta-farmasi
TA_CORE_CLIENT_ID=
TA_CORE_CLIENT_SECRET=
TA_CORE_TIMEOUT=5
TA_CORE_CONNECT_TIMEOUT=3
TA_CORE_VERIFY_SSL=true
TA_CORE_FAIL_SILENTLY=true
```

Default TA harus tetap disabled sampai adapter dan smoke test staging dibuat.

## Readiness Command
Jalankan di Core:

```bash
php artisan core:app-connection-readiness ta-farmasi
```

Command ini read-only dan memeriksa:
- app registry `ta-farmasi`.
- required app roles.
- active API client count.
- ability coverage pada active API client.
- active `user_app_accesses` count.
- internal endpoint registry.
- `/profile` route.
- readiness verdict.

Command tidak menampilkan secret, secret hash, password, atau token.

## Creating API Client Later
Credential TA belum dibuat pada tahap ini. Saat staging sudah siap:
1. Buat app client `ta-farmasi` dari Core Admin > API Clients.
2. Pilih hanya required abilities.
3. Salin one-time secret langsung ke secret manager/staging env.
4. Jangan tulis secret di docs, report, chat, screenshot, log, atau URL.
5. Jalankan readiness command kembali.
6. Jalankan smoke test TA setelah adapter TA dibuat.

## Not Done
- Tidak membuat real API client secret.
- Tidak membuat TA adapter.
- Tidak mengubah app TA.
- Tidak menjalankan TA command.
- Tidak menjalankan smoke test.
- Tidak membuat SSO.
- Tidak membuat auto-login.
- Tidak membuat token URL.
- Tidak write-back.
