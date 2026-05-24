# Core App Client Credential SOP

## A. Purpose
SOP ini mengatur pembuatan, penyimpanan, rotasi, revokasi, dan emergency disable app client credentials untuk staging KP/TU yang membaca Core API secara read-only.

Dokumen ini tidak berisi credential asli. Secret tidak boleh ditulis di repository, report, tiket publik, chat, screenshot, URL, atau log.

## B. Who Can Issue Credentials
- Hanya super-admin/admin-core yang berwenang boleh membuat dan mengelola API Clients di Core Admin.
- Credential dibuat melalui Core Admin > API Clients.
- Issuer wajib memastikan application code benar, aplikasi aktif, dan ability mengikuti prinsip least privilege.
- Issuer wajib mencatat metadata operasional tanpa secret: app_code, environment, owner, tanggal dibuat, ability, dan target rotasi.

## C. Required App Clients
Staging membutuhkan app client terpisah:
- `kp-farmasi` staging client.
- `tu-farmasi` staging client.
- `ta-farmasi` staging client, jika adapter TA sudah dibuat.
- `lab-farmasi` staging client, jika adapter Lab sudah dibuat.
- `helpdesk-farmasi` staging client, jika app Helpdesk dan adapter sudah dibuat.

Production client, jika nanti dibuat, harus berbeda dari staging client dan mengikuti SOP production secret management.

Untuk TU, Core menyediakan command issuance aman:

```bash
php artisan core:issue-tu-api-client
```

Default command adalah dry-run. Secret hanya dibuat dan ditampilkan sekali jika command dijalankan dengan `--apply`. Secret tidak boleh disalin ke report, repository, chat, screenshot, atau URL. Setelah issue/rotate, jalankan `php artisan core:tu-connection-readiness`.

Jika TU client sudah ada dan hanya perlu tambahan ability tanpa rotasi secret, gunakan:

```bash
php artisan core:grant-tu-api-client-ability
php artisan core:grant-tu-api-client-ability --apply --all-required
```

Command grant ability default dry-run, tidak menampilkan secret/hash, tidak merotasi secret, dan hanya menambah ability yang belum ada.

## D. Required Abilities for KP
Minimal ability untuk KP staging smoke test:
- `read:users`
- `read:students`
- `read:lecturers`
- `read:study-programs`
- `read:app-access`
- `read:leadership`

Jangan memberi ability yang tidak dipakai smoke test. Tambahan ability harus punya alasan dan dicatat.

## E. Required Abilities for TU
Minimal ability untuk TU staging smoke test:
- `read:users`
- `read:students`
- `read:lecturers`
- `read:employees`
- `read:study-programs`
- `read:departments`
- `read:app-access`
- `read:leadership`
- `verify:tu-portal-auth`

`verify:tu-portal-auth` dipakai untuk verifikasi password portal TU melalui endpoint app-client Core. Ability ini tidak membuat SSO, token, session lintas aplikasi, auto-login, atau write-back. Jangan memberi ability write karena endpoint consumer tahap ini read-only.

## E2. Required Abilities for Future TA/Lab/Helpdesk
Minimal ability untuk future TA/Lab/Helpdesk staging smoke test:
- `read:users`
- `read:students`
- `read:lecturers`
- `read:employees`
- `read:study-programs`
- `read:departments`
- `read:app-access`
- `read:leadership`

TA/Lab/Helpdesk credential belum dibuat pada readiness package awal. Buat secret hanya saat adapter dan staging smoke plan sudah siap, lalu simpan one-time secret di secret manager/staging env.

## F. Secret Handling
- Client secret hanya ditampilkan sekali saat create/rotate.
- Secret disalin langsung ke password manager atau staging secret manager yang disetujui.
- Secret tidak boleh dikirim lewat chat, email biasa, screenshot, atau issue tracker.
- Secret tidak boleh masuk `.env.example`, README, docs, report, log, atau database plaintext.
- Secret tidak boleh dikirim melalui URL/query string.
- Jika secret dicurigai bocor, rotate atau revoke segera.
- Jika smoke test memakai command output sebagai evidence, sensor semua nilai secret sebelum evidence disimpan.

## G. Rotation Procedure
1. Buat jadwal maintenance pendek untuk staging consumer terkait.
2. Di Core Admin, rotate secret untuk app client yang benar.
3. Salin secret baru ke staging secret manager/environment secara aman.
4. Clear config cache di consumer:
   ```bash
   php artisan config:clear
   ```
5. Jalankan smoke test consumer terkait.
6. Pastikan Core API request logs menunjukkan request valid dan tidak ada secret.
7. Catat tanggal rotasi, issuer, app_code, dan hasil smoke tanpa secret.
8. Jika memakai parallel credential window, revoke secret lama sesuai kebijakan operasional.

## H. Revocation Procedure
1. Revoke client di Core Admin saat client tidak lagi dipakai atau dicurigai bocor.
2. Verifikasi request memakai credential revoked ditolak oleh Core.
3. Expected response untuk client revoked adalah forbidden/unauthorized sesuai middleware.
4. Update staging env consumer jika perlu agar tidak terus mencoba credential revoked.
5. Catat hasil revocation tanpa secret.

## I. Audit
Core menyediakan audit metadata:
- API request logs untuk request app-client.
- API client `last_used_at`.
- Status active/revoked.
- Ability yang dicek pada request bila tersedia.

Audit tidak menyimpan:
- client secret,
- request body penuh,
- authorization header,
- password,
- token,
- stack trace.

## J. Emergency Disable
Jika ada indikasi secret leak, traffic aneh, atau consumer error:
1. Set consumer feature flag ke nonaktif:
   ```env
   KP_CORE_HTTP_ENABLED=false
   TU_CORE_HTTP_ENABLED=false
   ```
2. Pertahankan mode aman:
   ```env
   KP_CORE_READ_MODE=legacy
   TU_CORE_READ_MODE=disabled
   ```
3. Clear config/cache consumer:
   ```bash
   php artisan optimize:clear
   ```
4. Revoke app client di Core jika leak dicurigai.
5. Periksa Core API request logs.
6. Catat incident tanpa secret.

Tidak ada rollback database yang diperlukan untuk disable integrasi read-only.
