# Core Secret Management Readiness

## Purpose
Dokumen ini merangkum readiness secret management untuk Core, KP, dan TU sebelum real staging smoke test atau production preparation.

Dokumen ini tidak berisi secret asli.

## Secret Types
Secret yang harus dikelola aman:
- Core app client secret.
- `KP_CORE_CLIENT_SECRET`.
- `TU_CORE_CLIENT_SECRET`.
- Database passwords untuk Core/KP/TU.
- `APP_KEY` masing-masing aplikasi.
- Mail/SMTP credentials jika nanti dipakai.
- Object storage credentials seperti `AWS_ACCESS_KEY_ID` dan `AWS_SECRET_ACCESS_KEY` jika nanti dipakai.
- Any monitoring/webhook token jika ditambahkan di masa depan.

Client ID bukan secret, tetapi tetap tidak perlu dipublikasikan di tempat umum.

## Where Secrets Must Live
Secret harus tinggal di:
- staging/production server environment.
- password manager tim yang disetujui.
- secret manager platform/deployment.
- CI/CD protected secret variables jika dibutuhkan.

Access harus dibatasi pada admin/devops/owner yang berwenang.

## Where Secrets Must Not Live
Secret tidak boleh ditulis di:
- git repository.
- `.env.example`.
- README.
- docs/reports.
- screenshots.
- chat.
- issue tracker publik.
- URL/query string.
- command output yang disimpan sebagai evidence.
- API request logs.
- application logs biasa.

## Environment Example Rule
`.env.example` harus tetap placeholder:
- `KP_CORE_CLIENT_SECRET=`
- `TU_CORE_CLIENT_SECRET=`
- database passwords kosong atau placeholder lokal aman.
- no production URL with credential.
- no token.
- no real app client secret.

## Staging / Production Separation
- Staging app clients harus berbeda dari production app clients.
- Staging secret tidak boleh dipakai production.
- Production secret tidak boleh dipakai staging.
- Rotation cadence dapat berbeda, tetapi leak response harus sama cepatnya.
- Evidence smoke test staging tidak boleh memuat secret staging.

## Access Control
Yang boleh mengakses secret:
- owner/admin yang diberi mandat.
- devops/deployment operator yang membutuhkan akses.
- auditor internal sesuai kebutuhan dan tanpa membuka plaintext jika cukup metadata.

Yang tidak boleh:
- user aplikasi.
- developer yang tidak memerlukan akses deploy.
- publik.
- bot/report/log yang tidak disanitasi.

## Rotation SOP Summary
1. Rotate secret dari Core Admin API Client.
2. Salin secret baru langsung ke secret manager/env.
3. Clear config cache di consumer.
4. Jalankan smoke test read-only.
5. Verifikasi Core API logs tidak menyimpan secret.
6. Catat metadata rotasi tanpa secret.
7. Revoke/retire old secret sesuai SOP.

## Revocation SOP Summary
1. Revoke client di Core Admin jika tidak dipakai atau dicurigai bocor.
2. Verifikasi request lama ditolak.
3. Disable consumer flag jika perlu.
4. Catat waktu revocation dan alasan tanpa secret.
5. Buat credential baru hanya setelah root cause jelas.

## Emergency Secret Leak Response
Jika secret bocor atau dicurigai bocor:
1. Revoke app client terkait di Core.
2. Set consumer flag:
   ```env
   KP_CORE_HTTP_ENABLED=false
   TU_CORE_HTTP_ENABLED=false
   ```
3. Pertahankan mode aman:
   ```env
   KP_CORE_READ_MODE=legacy
   TU_CORE_READ_MODE=disabled
   ```
4. Clear config cache consumer.
5. Review Core API request logs untuk anomali.
6. Review KP/TU logs untuk memastikan secret tidak tersebar lebih jauh.
7. Rotate secret lain jika reuse dicurigai.
8. Catat incident tanpa menyalin secret.

## Readiness Checklist
- Secret manager/password manager tersedia.
- Admin yang boleh issue credential jelas.
- Staging dan production secret dipisahkan.
- `.env.example` tetap placeholder.
- SOP rotate/revoke tersedia.
- Emergency disable tersedia.
- API logs dipastikan tidak mencatat secret.
- Smoke result template tidak meminta secret.

## Do Not Do
- Jangan hardcode secret di config.
- Jangan menulis secret di docs/report.
- Jangan kirim secret melalui URL.
- Jangan menggunakan secret production untuk staging.
- Jangan menjalankan smoke test dengan menampilkan env full.
- Jangan menyimpan screenshot yang berisi secret.
