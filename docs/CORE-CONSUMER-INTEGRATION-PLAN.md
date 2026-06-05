# Core Consumer Integration Plan

Dokumen ini merancang integrasi read-only aplikasi consumer pertama ke Core Farmasi API. Fokus awal adalah KP Farmasi dan TU Farmasi.

Rujukan kontrak data pusat:
- `docs/CORE-CENTRAL-DATA-CONTRACT.md`
- `docs/CORE-MASTER-CRUD-MATRIX.md`

## Tujuan
- Menghubungkan consumer app ke Core sebagai source of truth secara read-only.
- Menggunakan API internal Core dengan app client credentials.
- Menjaga consumer app tetap punya login/guard lokal.
- Menyiapkan smoke test sebelum cutover terbatas.
- Menentukan gap endpoint sebelum adapter diaktifkan.

## Non-Goals
- Tidak membuat SSO.
- Tidak membuat auto-login.
- Tidak membuat cross-app session.
- Tidak membuat token di URL.
- Tidak menulis data dari consumer ke Core.
- Tidak membuat form edit profil utama di consumer app.
- Tidak production cutover.
- Tidak mengganti auth KP/TU.
- Tidak menyimpan real secret di repo, report, atau dokumentasi.

## Core API Endpoints To Use
Endpoint Core yang sudah tersedia:

- `GET /api/v1/health`
  - public health check minimal.
- `GET /api/v1/internal/apps/{app_code}/users/{user}/access`
  - app-client protected.
  - ability: `read:app-access`.
  - cocok untuk mengecek app role aktif user di KP/TU.
- `GET /api/v1/internal/leadership/current`
  - app-client protected.
  - ability: `read:leadership`.
  - cocok untuk Dekan, Kaprodi, dan jabatan resmi aktif.
- `GET /api/v1/users/{id}`
- `GET /api/v1/students/{id}`
- `GET /api/v1/lecturers/{id}`
- `GET /api/v1/employees/{id}`
- `GET /api/v1/study-programs`
- `GET /api/v1/study-programs/{id}`
  - protected by user bearer token, bukan app-client route.
  - safe fields only, tanpa password/hash/token/birth_date default.

App-client directory endpoints:
- `GET /api/v1/internal/directory/users`
- `GET /api/v1/internal/directory/users/{id}`
- `GET /api/v1/internal/directory/students`
- `GET /api/v1/internal/directory/students/{id}`
- `GET /api/v1/internal/directory/lecturers`
- `GET /api/v1/internal/directory/lecturers/{id}`
- `GET /api/v1/internal/directory/employees`
- `GET /api/v1/internal/directory/employees/{id}`
- `GET /api/v1/internal/directory/study-programs`
- `GET /api/v1/internal/directory/study-programs/{id}`
- `GET /api/v1/internal/directory/departments`
- `GET /api/v1/internal/directory/departments/{id}`

Endpoint ini memakai app-client credentials, safe fields, pagination limits, ability checks, rate limiting, dan audit logging.

## Centralized Profile Rule
Core menjadi pemilik profil utama user. Consumer apps seperti KP/TU menampilkan profil secara read-only dari Core dan menyediakan link "Ubah Profil di Core" ke Profile Portal.

Profile Portal Core:
- `/profile`
- `/profile/edit`

Consumer app tidak boleh membuat form edit untuk data profil utama seperti nama resmi, identity number, NIM/NIDN/NIP/employee number, prodi, department, status, role, app access, atau jabatan. Data operasional aplikasi tetap menjadi domain consumer app.

KP/TU link-to-profile configuration:
- KP: `KP_CORE_PROFILE_URL`, fallback from `KP_CORE_BASE_URL/profile`.
- TU: `TU_CORE_PROFILE_URL`, fallback from `TU_CORE_BASE_URL/profile`.
- Link must not include token, client secret, user id, or cross-app session data.
- If user is not logged in to Core, Core handles its own login flow.

## KP Integration Needs
KP Farmasi saat ini punya:
- local auth/guard sendiri.
- `KP_AUTH_MODE=legacy`.
- `KP_MASTER_DATA_READ_MODE=legacy`.
- read-only Core DB bridge existing melalui model `App\Models\Core\*`.
- service `CoreIdentityService`.
- service `KpMasterDataReadService`.
- tests untuk Core read integration.

Kebutuhan KP dari Core:
- user identity canonical untuk mapping `core_user_id`.
- student data untuk mahasiswa KP.
- lecturer data untuk koordinator/pembimbing/penguji.
- study program data untuk tampilan dan filter.
- app access role `kp-farmasi`:
  - `mahasiswa`
  - `koordinator-kp`
  - `pembimbing-dalam`
  - `pembimbing-lapangan`
  - `penguji`
  - `admin-kp`
- leadership current Dekan/Kaprodi untuk dokumen/surat bila dibutuhkan.
- field supervisor tetap domain lokal KP karena pembimbing lapangan eksternal tidak selalu identity utama Core.
- read-only profile display plus link ke `/profile` Core untuk perubahan profil utama.

Endpoint siap pakai untuk KP sekarang:
- app access check.
- current leadership.
- health.
- app-client student/lecturer/employee reads.
- app-client study program/department list/read.
- optional user safe read via app-client with ability.

## TU Integration Needs
TU Farmasi sudah ada dan memiliki:
- `TU_AUTH_MODE=local`.
- `TU_CORE_READ_MODE=disabled`.
- `config/tu_core.php`.
- read-only Core DB bridge existing.
- `CoreDirectoryService`.
- `CorePersonSearchService`.
- person picker read-only docs dan tests.

Kebutuhan TU dari Core:
- person picker mahasiswa/dosen.
- study program list.
- user identity untuk snapshot service request.
- employee/staf internal bila diperlukan untuk admin/penandatangan.
- current leadership Dekan/Kaprodi/pejabat untuk dokumen resmi.
- app access `tu-farmasi` untuk validasi role aplikasi jika nanti login/guard TU diselaraskan.
- read-only profile/person picker display plus link ke `/profile` Core untuk perubahan profil utama.

Endpoint siap pakai untuk TU sekarang:
- app access check.
- current leadership.
- health.
- app-client person profile reads.
- app-client study program/department list/read.
- app-client employee safe read/list untuk staf/penandatangan.
- portal password verification endpoint untuk TU HTTP shadow, protected by app-client ability `verify:tu-portal-auth`, tanpa token/SSO/session.

Catatan portal auth TU:
- Endpoint Core `POST /api/v1/internal/apps/tu-farmasi/portal-auth/verify` hanya memverifikasi login/password Core dan app access aktif `tu-farmasi`.
- Response gagal tetap generik dan tidak membedakan password salah, user inactive, atau app access tidak ada.
- Endpoint tidak membuat token, tidak membuat cross-app session, tidak auto-login, dan tidak write-back ke TU.
- Active TU API client harus memiliki ability `verify:tu-portal-auth` sebelum portal login via HTTP dapat diuji penuh.

## Environment Variables Needed
Untuk adapter HTTP read-only consumer nanti:

```env
CORE_FARMASI_ENABLED=false
CORE_FARMASI_BASE_URL=
CORE_FARMASI_APP_CODE=kp-farmasi
CORE_FARMASI_CLIENT_ID=
CORE_FARMASI_CLIENT_SECRET=
CORE_FARMASI_TIMEOUT=5
CORE_FARMASI_READ_MODE=legacy
```

Untuk TU:

```env
CORE_FARMASI_ENABLED=false
CORE_FARMASI_BASE_URL=
CORE_FARMASI_APP_CODE=tu-farmasi
CORE_FARMASI_CLIENT_ID=
CORE_FARMASI_CLIENT_SECRET=
CORE_FARMASI_TIMEOUT=5
CORE_FARMASI_READ_MODE=disabled
```

Real client secret harus disimpan di environment/secret manager, bukan repository.

## App Client Credential Issuance SOP
Operational SOP lengkap tersedia di:

- `docs/CORE-APP-CLIENT-CREDENTIAL-SOP.md`

1. Pastikan `core_applications` untuk consumer app aktif.
2. Buat `CoreApiClient` dari admin Core.
3. Berikan ability minimal:
   - KP initial: `read:app-access`, `read:leadership`.
   - TU initial: `read:app-access`, `read:leadership`.
   - Tambahkan ability profile read hanya setelah endpoint app-client read-only dibuat.
4. Salin secret sekali saat create/rotate.
5. Simpan secret di secret manager/environment consumer.
6. Uji invalid secret, revoked client, app_code mismatch, dan rate limit.
7. Dokumentasikan tanggal issuance, owner, app_code, abilities, dan rotation cadence tanpa menulis secret.

## Secret Management SOP
- Jangan commit secret ke repo.
- Jangan menulis secret di README/report/log.
- Jangan mengirim secret di URL/query string.
- Gunakan environment variable atau secret manager.
- Rotate secret berkala dan saat ada indikasi kebocoran.
- Revoke client yang tidak lagi dipakai.
- Batasi ability sesuai kebutuhan.

## Feature Flag Strategy
KP:
- default tetap `KP_AUTH_MODE=legacy`.
- default tetap `KP_MASTER_DATA_READ_MODE=legacy`.
- HTTP adapter KP tersedia sebagai skeleton default-off dengan `KP_CORE_HTTP_ENABLED=false`.
- `KP_CORE_READ_MODE` default tetap `legacy`.
- Mode awal yang aman: API smoke only, lalu `core_preferred` untuk read display jika endpoint profile app-client sudah tersedia.

TU:
- default tetap `TU_AUTH_MODE=local`.
- default tetap `TU_CORE_READ_MODE=disabled`.
- HTTP adapter TU tersedia sebagai skeleton default-off dengan `TU_CORE_HTTP_ENABLED=false`.
- Person picker boleh tetap read-only dan fallback empty jika Core unavailable.
- `TU_CORE_READ_MODE` default tetap `disabled`.

## Fallback To Legacy Mode
- Consumer app tidak boleh crash jika Core API unavailable.
- Disabled mode harus return null/empty result.
- `core_preferred` boleh fallback ke legacy/local data.
- `core_only` atau read-only strict hanya boleh dipakai setelah staging smoke test lulus.
- Error dari Core 401/403/429/500 harus ditangani sebagai unavailable/denied, bukan exception user-facing mentah.

## Smoke Test Plan
Rencana eksekusi gabungan untuk staging tersedia di:

- `docs/CORE-KP-TU-STAGING-SMOKE-EXECUTION-PLAN.md`
- `docs/templates/KP-TU-STAGING-SMOKE-RESULT-TEMPLATE.md`

Core:
1. `GET /api/v1/health` returns OK.
2. valid app client can call app access check.
3. invalid secret rejected.
4. revoked client rejected.
5. app_code mismatch rejected.
6. valid app client can call current leadership.
7. rate limit returns 429 when threshold exceeded.
8. API request logs record safe metadata.

KP/TU consumer:
1. feature flag disabled returns null/empty and does not call Core.
2. enabled mode sends required headers.
3. no token/secret in URL.
4. 401/403/429/500 handled safely.
5. no auth replacement.
6. no database writes to Core.

KP staging smoke checklist sudah disiapkan di `apps/kp-farmasi/docs/CORE-HTTP-ADAPTER-STAGING-SMOKE-TEST.md`. Checklist tersebut memakai credential staging via environment, tidak menulis real secret, dan tetap menjaga `KP_CORE_READ_MODE=legacy` untuk smoke awal.

TU staging smoke checklist sudah disiapkan di `apps/tu-farmasi/docs/CORE-HTTP-ADAPTER-STAGING-SMOKE-TEST.md`. Checklist tersebut memakai credential staging via environment, tidak menulis real secret, dan tetap menjaga `TU_CORE_READ_MODE=disabled` untuk smoke awal.

Paket readiness koneksi TU dari sisi Core tersedia di `docs/CORE-TU-CONNECTION-PACKAGE.md`. Paket ini merangkum app code `tu-farmasi`, required abilities, required app roles, endpoint, env TU, credential handling, dan command `php artisan core:tu-connection-readiness`.

## Consumer App Readiness Matrix
Core menyediakan readiness check non-destruktif untuk consumer aktual workspace dan future consumer:
- KP Farmasi: app code `kp-farmasi`
- TU Farmasi: app code `tu-farmasi`
- TA Farmasi: app code `ta-farmasi`
- Lab Farmasi: app code `lab-farmasi`
- Helpdesk Farmasi: app code `helpdesk-farmasi`

Package koneksi tersedia untuk:
- TA Farmasi: `docs/CORE-TA-CONNECTION-PACKAGE.md`
- Lab Farmasi: `docs/CORE-LAB-CONNECTION-PACKAGE.md`
- Helpdesk Farmasi: `docs/CORE-HELPDESK-CONNECTION-PACKAGE.md`

Status:
- app registry `kp-farmasi`, `tu-farmasi`, `ta-farmasi`, `lab-farmasi`, dan `helpdesk-farmasi` disiapkan active, non-public, dan requires login.
- app role catalog KP/TU/TA/Lab/Helpdesk disiapkan sebagai dynamic app roles.
- required abilities generic readiness masih read-only: users, students, lecturers, employees, study programs, departments, app access, leadership.
- readiness command generik tersedia:
  - `php artisan core:app-connection-readiness kp-farmasi`
  - `php artisan core:app-connection-readiness tu-farmasi`
  - `php artisan core:app-connection-readiness ta-farmasi`
  - `php artisan core:app-connection-readiness lab-farmasi`
  - `php artisan core:app-connection-readiness helpdesk-farmasi`
- TU juga punya readiness khusus: `php artisan core:tu-connection-readiness`.
- TA juga punya readiness khusus: `php artisan core:ta-app-readiness`.
- Lab juga punya readiness khusus: `php artisan core:lab-app-readiness`.
- TA HTTP read-only adapter skeleton sudah tersedia di `apps/ta-farmasi` dengan default disabled, no SSO, no write-back, dan smoke command `php artisan ta:core-smoke-test`.
- Lab HTTP read-only adapter skeleton sudah tersedia di `apps/lab-farmasi` dengan default disabled, no SSO, no write-back, dan smoke command `php artisan lab:core-smoke-test`.
- KP, TU, TA, dan Lab sudah memiliki active API client untuk staging/readiness lokal. Secret tidak ditulis di dokumen.
- KP generic readiness sudah didukung dan sudah dapat memberi verdict staging. KP legacy/Core bridge yang sudah berjalan tetap terpisah dari API client generic.
- belum ada app Helpdesk skeleton atau adapter implementation di app Helpdesk.
- Helpdesk belum punya real API client sampai app/adapter dibuat.
- tidak ada SSO, auto-login, token URL, atau write-back.

Next step untuk Lab adalah memasang credential staging ke environment Lab yang aman dan menjalankan `php artisan lab:core-smoke-test`. Next step untuk KP adalah menjalankan HTTP smoke generic hanya jika jalur adapter HTTP KP akan dipakai; jangan mengubah bridge/login yang sudah ada tanpa tahap cutover terpisah. Next step untuk Helpdesk adalah application planning/skeleton dan read-only adapter saat app consumer sudah ada.

## Profile Cutover Notes
Catatan cutover profil tersedia di:

- `docs/CORE-PROFILE-CUTOVER-NOTES.md`

Current state:
- Core owns canonical profile edits.
- KP/TU expose safe link to Core `/profile` when configured.
- Existing local profile forms in KP/TU remain legacy/operational until a separate cutover stage.
- App-specific operational fields remain local.
- No SSO, no auto-login, no write-back, and no token URL.

Future cutover must inventory duplicate fields, make Core-owned fields read-only in KP/TU, keep app-specific fields local, run staging validation, and get owner approval.

## Recommended Sequence
1. KP HTTP read-only adapter default-off selesai.
2. Run KP tests with HTTP fake selesai.
3. KP staging smoke test plan selesai.
4. Issue staging app client for KP with minimal abilities.
5. Run real staging smoke test.
6. Decide KP read mode progression only after staging smoke passes.
7. TU HTTP read-only adapter default-off selesai.
8. Run TU tests with HTTP fake.
9. TU staging smoke test plan selesai di `apps/tu-farmasi/docs/CORE-HTTP-ADAPTER-STAGING-SMOKE-TEST.md`.
10. Run Core readiness command for TU using `php artisan core:tu-connection-readiness`.
11. Issue staging app client for TU with minimal abilities.
12. Run TU real staging smoke test before enabling any production flow.
13. Use `docs/CORE-KP-TU-STAGING-SMOKE-EXECUTION-PLAN.md` and the result template for the first combined KP/TU staging run.
14. Only consider profile field cutover after smoke test and profile link validation pass.
15. TA HTTP read-only adapter skeleton selesai; lanjut issue TA staging client dan smoke test hanya saat credential/staging siap.
16. Lab HTTP read-only adapter skeleton selesai; lanjut issue Lab staging client dan smoke test hanya saat credential/staging siap.
17. Helpdesk Core readiness package selesai; lanjut Helpdesk app planning/skeleton lalu read-only adapter saat aplikasi consumer sudah ada.

## Risks
- Existing KP/TU read bridges use database read-only, while HTTP adapter is still default-off.
- Enabling HTTP adapter before staging credentials and smoke tests would create operational risk.
- TU person picker and leadership usage should remain behind explicit staging validation until real credentials are issued.
- TU smoke test command `php artisan tu:core-smoke-test` is read-only and default-safe while `TU_CORE_HTTP_ENABLED=false`.
- Consumer app object-level authorization remains each consumer's responsibility.
- Secret handling needs operational discipline outside code.
- Production readiness needs staging smoke test with real app client credentials.
- Real Core profile URL must be configured in staging before profile link verification.
