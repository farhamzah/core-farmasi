# CORE-INTEGRATION-1 Read-Only Consumer Integration Planning Report

## Scope
Tahap ini membuat planning dan smoke-readiness untuk integrasi read-only aplikasi consumer pertama ke Core API. Tahap ini bukan cutover, bukan SSO, bukan auto-login, bukan write-back, dan tidak mengubah auth KP/TU.

Implementasi tahap ini docs-only di Core. Tidak ada perubahan kode runtime KP/TU, tidak ada migration, tidak ada database write, dan tidak ada real secret yang ditulis.

## Previous Reports Reviewed
- CORE-QA-2 Final Regression & Release Readiness Report
- CORE-API-2 App Client Credentials & Token Rotation Report
- CORE-API-3 API Audit & Rate Limit per Client Report
- CORE-API-4 Log Retention & Pruning Report
- CORE-ARCHITECTURE-SUMMARY
- CORE-INTERNAL-API
- CORE-RELEASE-READINESS-CHECKLIST

## Core API Readiness
Endpoint yang tersedia:

- `GET /api/v1/health`
  - public health check.
- `GET /api/v1/internal/apps/{app_code}/users/{user}/access`
  - protected by app client headers.
  - ability: `read:app-access`.
  - siap untuk smoke test app access KP/TU.
- `GET /api/v1/internal/leadership/current`
  - protected by app client headers.
  - ability: `read:leadership`.
  - siap untuk Dekan/Kaprodi/jabatan resmi aktif.
- `GET /api/v1/users/{id}`
- `GET /api/v1/students/{id}`
- `GET /api/v1/lecturers/{id}`
- `GET /api/v1/employees/{id}`
- `GET /api/v1/study-programs`
- `GET /api/v1/study-programs/{id}`
  - protected by user bearer token.
  - safe response only.
  - belum cocok sebagai server-to-server app-client consumer read endpoint.

Core API readiness untuk smoke read-only awal: OK untuk app access dan leadership.

Gap yang dicatat:
- app-client profile/directory endpoints untuk students/lecturers/employees/study programs belum tersedia.
- Consumer HTTP adapter penuh sebaiknya menunggu endpoint app-client read-only khusus consumer agar tidak mencampur app client dan user bearer token.

## KP Consumer Analysis
KP Farmasi sudah memiliki:
- local auth/guard sendiri.
- `KP_AUTH_MODE=legacy`.
- `KP_MASTER_DATA_READ_MODE=legacy`.
- read-only Core DB bridge via `App\Models\Core\*`.
- `CoreIdentityService`.
- `KpMasterDataReadService`.
- tests untuk read-only Core DB integration.

Kebutuhan KP:
- user identity canonical.
- student data untuk mahasiswa KP.
- lecturer data untuk koordinator/pembimbing/penguji.
- study program data.
- app access role `kp-farmasi`.
- leadership Dekan/Kaprodi untuk dokumen bila dibutuhkan.
- field supervisor tetap domain lokal KP.

Endpoint Core yang dibutuhkan KP:
- app access check: available.
- current leadership: available.
- student/lecturer/study program app-client reads: future endpoint.

Status legacy:
- KP tetap aman dalam mode legacy.
- Existing Core DB read bridge sudah read-only, tetapi integrasi HTTP API belum diaktifkan.

Risiko:
- Mengaktifkan adapter HTTP sekarang untuk student/lecturer would be partial karena endpoint profile read saat ini masih bearer-token based.
- Cutover auth/read mode KP tidak boleh dilakukan sebelum staging smoke test.

## TU Consumer Analysis
TU Farmasi sudah ada dan memiliki:
- `TU_AUTH_MODE=local`.
- `TU_CORE_READ_MODE=disabled`.
- `config/tu_core.php`.
- read-only Core DB bridge.
- `CoreDirectoryService`.
- `CorePersonSearchService`.
- dokumentasi `TU-04` dan `TU-32` untuk Core read-only bridge/person picker.

Kebutuhan TU:
- person picker mahasiswa/dosen.
- study program list.
- user identity snapshot.
- employee/staf internal bila diperlukan.
- leadership Dekan/Kaprodi/pejabat untuk dokumen resmi.
- app access `tu-farmasi` bila login/guard TU nanti diselaraskan.

Endpoint Core yang dibutuhkan TU:
- app access check: available.
- current leadership: available.
- person search/profile reads via app client: future endpoint.
- study program app-client list/read: future endpoint.

Status:
- TU tetap default disabled untuk Core read.
- Tidak ada adapter HTTP baru dibuat pada tahap ini.

## Integration Plan
Feature flag:
- KP default tetap `KP_AUTH_MODE=legacy`.
- KP default tetap `KP_MASTER_DATA_READ_MODE=legacy`.
- TU default tetap `TU_AUTH_MODE=local`.
- TU default tetap `TU_CORE_READ_MODE=disabled`.
- HTTP adapter future harus default `CORE_FARMASI_ENABLED=false`.

Env variables future:
- `CORE_FARMASI_ENABLED=false`
- `CORE_FARMASI_BASE_URL=`
- `CORE_FARMASI_APP_CODE=kp-farmasi` atau `tu-farmasi`
- `CORE_FARMASI_CLIENT_ID=`
- `CORE_FARMASI_CLIENT_SECRET=`
- `CORE_FARMASI_TIMEOUT=5`
- `CORE_FARMASI_READ_MODE=legacy` atau `disabled`

App client credential SOP:
- buat client dari Core admin.
- ability minimal untuk smoke awal: `read:app-access`, `read:leadership`.
- secret ditampilkan sekali, lalu disimpan di secret manager/env consumer.
- jangan menulis secret di repo/report/log.
- test invalid secret, revoked client, app_code mismatch, 429, dan success request.

Fallback strategy:
- disabled mode return null/empty result.
- core preferred mode fallback ke legacy/local data.
- Core unavailable tidak boleh membuat consumer crash.
- production cutover hanya setelah staging smoke test.

## Implementation Done
Dokumen dibuat:
- `apps/core-farmasi/docs/CORE-CONSUMER-INTEGRATION-PLAN.md`
- `apps/core-farmasi/docs/reports/CORE-INTEGRATION-1-READ-ONLY-CONSUMER-PLANNING-REPORT.md`

Dokumen diperbarui:
- `apps/core-farmasi/docs/CORE-INTERNAL-API.md`
- `apps/core-farmasi/docs/CORE-ARCHITECTURE-SUMMARY.md`
- `apps/core-farmasi/README.md`

Adapter KP/TU tidak dibuat pada tahap ini karena:
- KP/TU sudah punya read-only database bridge existing.
- Core app-client endpoint untuk profile/directory read belum tersedia.
- Memaksa HTTP adapter sekarang berisiko menjadi partial integration yang belum cocok untuk student/lecturer reads.

## Security Confirmation
- no SSO: OK
- no auto-login: OK
- no write-back: OK
- no production cutover: OK
- no real secret: OK
- no token URL: OK
- no auth replacement: OK
- no database changes in consumer: OK
- no KP/TU runtime behavior change: OK
- no Core public exposure: OK

## Commands Run
Core:
- `php artisan optimize:clear` - OK
- `php artisan test` - OK, 156 passed / 748 assertions

KP:
- tests tidak dijalankan karena tidak ada file KP yang diubah.

TU:
- tests tidak dijalankan karena tidak ada file TU yang diubah.

No migration command was run.

## Test Result
Core test result:
- 156 tests passed
- 748 assertions

KP/TU:
- tidak dijalankan; tahap ini tidak mengubah kode KP/TU dan tidak melakukan cutover.

## Guardrails Confirmation
- Tidak menjalankan migrate:fresh.
- Tidak menjalankan migrate:reset.
- Tidak menjalankan migrate:rollback.
- Tidak drop database.
- Tidak mengubah database KP/TU.
- Tidak execute import.
- Tidak execute Core import.
- Tidak cutover.
- Tidak mengganti auth KP/TU.
- Tidak membuat SSO/bypass login.
- Tidak membuat cross-app session.
- Tidak membuat token URL.
- Tidak menulis real secret.
- Tidak expose password/hash/token/secret.
- Tidak membuat Core public.
- Tidak menyentuh SAFA.

## Risks / Notes
- App-client profile/directory read endpoints belum tersedia.
- KP/TU saat ini punya read-only database bridge; transisi ke HTTP API perlu desain endpoint agar tidak bergantung pada user bearer token.
- Consumer app tetap harus menerapkan object-level authorization sendiri.
- Secret management SOP perlu diterapkan di staging sebelum real app client digunakan.
- Staging smoke test dengan real app client credentials belum dilakukan.

## Recommended Next Step
Rekomendasi: **CORE-INTEGRATION-2 KP Read-Only Adapter Implementation**.

Urutan aman:
1. Tambahkan Core app-client profile/directory read endpoints untuk kebutuhan KP.
2. Buat KP HTTP read-only adapter default-off.
3. Test dengan HTTP fake.
4. Issue staging app client credentials.
5. Jalankan smoke test staging tanpa cutover auth.
