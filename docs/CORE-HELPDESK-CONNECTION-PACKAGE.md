# Core Helpdesk Connection Package

## Purpose
Dokumen ini menyiapkan paket koneksi future consumer Helpdesk Farmasi ke Core Farmasi. Paket ini hanya readiness dari sisi Core: app registry, role catalog, required abilities, endpoint mapping, env placeholder, dan SOP aman untuk membuat credential nanti.

Tahap ini tidak membuat real secret, tidak menjalankan smoke test nyata, tidak membuat app Helpdesk skeleton, tidak membuat SSO, tidak membuat auto-login, tidak membuat token URL, dan tidak melakukan write-back.

## App Code
```text
helpdesk-farmasi
```

Core application `helpdesk-farmasi` harus:
- registered di Core Applications.
- active.
- `is_public_visible=false`.
- `requires_login=true`.
- `is_sensitive=false`.

## Required App Roles
Role aplikasi Helpdesk yang perlu tersedia di Core app role catalog:
- `requester`
- `agent`
- `admin-helpdesk`
- `teknisi`
- `supervisor`
- `viewer`

Catatan:
- `requester`, `agent`, `teknisi`, dan `supervisor` adalah app-specific roles.
- Profil utama tetap diedit di Core.
- Helpdesk nanti hanya menyimpan tiket, komentar, status, kategori, SLA, lampiran, dan data operasional helpdesk.

## Required Abilities
Future Helpdesk app client minimal membutuhkan:
- `read:users`
- `read:students`
- `read:lecturers`
- `read:employees`
- `read:study-programs`
- `read:departments`
- `read:app-access`
- `read:leadership`

Jangan memberi write ability. Adapter Helpdesk awal harus read-only.

## Core Endpoints
Endpoint Core yang dibutuhkan Helpdesk:
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

URL sebenarnya harus diisi di environment server Helpdesk, bukan repository. Link ini bukan SSO. Jika user belum login Core, Core akan meminta login resmi.

## Helpdesk Environment Placeholder
Contoh env future Helpdesk tanpa secret asli:

```env
HELPDESK_CORE_HTTP_ENABLED=false
HELPDESK_CORE_READ_MODE=disabled
HELPDESK_CORE_BASE_URL=
HELPDESK_CORE_PROFILE_URL=
HELPDESK_CORE_APP_CODE=helpdesk-farmasi
HELPDESK_CORE_CLIENT_ID=
HELPDESK_CORE_CLIENT_SECRET=
HELPDESK_CORE_TIMEOUT=5
HELPDESK_CORE_CONNECT_TIMEOUT=3
HELPDESK_CORE_VERIFY_SSL=true
HELPDESK_CORE_FAIL_SILENTLY=true
```

Default Helpdesk harus tetap disabled sampai adapter dan smoke test staging dibuat.

## Readiness Command
Jalankan di Core:

```bash
php artisan core:app-connection-readiness helpdesk-farmasi
```

Command ini read-only dan memeriksa:
- app registry `helpdesk-farmasi`.
- required app roles.
- active API client count.
- ability coverage pada active API client.
- active `user_app_accesses` count.
- internal endpoint registry.
- `/profile` route.
- readiness verdict.

Command tidak menampilkan secret, secret hash, password, atau token.

## Creating API Client Later
Credential Helpdesk belum dibuat pada tahap ini. Saat staging sudah siap:
1. Buat app client `helpdesk-farmasi` dari Core Admin > API Clients.
2. Pilih hanya required abilities.
3. Salin one-time secret langsung ke secret manager/staging env.
4. Jangan tulis secret di docs, report, chat, screenshot, log, atau URL.
5. Jalankan readiness command kembali.
6. Jalankan smoke test Helpdesk setelah adapter Helpdesk dibuat.

## Not Done
- Tidak membuat real API client secret.
- Tidak membuat app Helpdesk skeleton.
- Tidak mengubah app consumer.
- Tidak menjalankan Helpdesk command.
- Tidak menjalankan smoke test.
- Tidak membuat SSO.
- Tidak membuat auto-login.
- Tidak membuat token URL.
- Tidak write-back.
