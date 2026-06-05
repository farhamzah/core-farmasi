# Core App Readiness Matrix Fix - 2026-06-04

## Scope
Perbaikan ini menutup gap readiness Core untuk consumer aktual workspace:

- `lab-farmasi`
- `kp-farmasi`
- `tu-farmasi`
- `ta-farmasi`
- `helpdesk-farmasi`

Guardrail tetap berlaku: tidak ada SSO, auto-login, magic link/token URL, write-back, mass access grant otomatis, atau secret yang ditulis di dokumen.

## Perubahan
- Generic command `php artisan core:app-connection-readiness` sekarang mendukung `kp-farmasi` dan `tu-farmasi`.
- `lab-farmasi` diprovision dengan active API client read-only.
- `kp-farmasi` sudah didukung oleh generic readiness command dan terdeteksi memiliki active API client read-only.
- One-time secret Lab hanya disimpan ke environment lokal/staging Lab dan tidak dicetak di report.
- Test readiness diperluas agar matrix consumer workspace tidak mengecil kembali.
- Dokumen `docs/CORE-CONSUMER-INTEGRATION-PLAN.md` diperbarui dengan status consumer aktual.

## Required Abilities Read-only
Generic readiness memakai ability read-only berikut:

- `read:users`
- `read:students`
- `read:lecturers`
- `read:employees`
- `read:study-programs`
- `read:departments`
- `read:app-access`
- `read:leadership`

## Readiness Result
| App | Command | Result |
| --- | --- | --- |
| Lab | `php artisan core:app-connection-readiness lab-farmasi` | `ready_for_staging_config` |
| KP | `php artisan core:app-connection-readiness kp-farmasi` | `ready_for_staging_config` |
| TU | `php artisan core:app-connection-readiness tu-farmasi` | `ready_for_staging_config` |
| TA | `php artisan core:app-connection-readiness ta-farmasi` | `ready_for_staging_config` |

KP sekarang sudah supported oleh generic readiness command dan memberi verdict staging yang jelas. Ini tidak mengubah koneksi KP-Core yang sebelumnya sudah berjalan lewat bridge/read integration.

Catatan operasional: Lab saat validasi memiliki lebih dari satu active API client read-only. Ini tidak memblokir readiness, tetapi operator sebaiknya memastikan credential mana yang dipakai oleh environment Lab, lalu revoke client yang tidak digunakan melalui prosedur aman bila sudah jelas.

## Security
- Tidak ada secret dicatat di report.
- Client ID hanya ditampilkan dalam bentuk masked hint oleh command readiness.
- `.env` Lab tetap ignored oleh Git.
- Tidak ada migration, database reset, import execute, atau perubahan login.

## Validation
- `php artisan test --filter=AppConnectionReadinessTest`: PASS, 6 tests / 96 assertions.
- `php artisan optimize:clear`: PASS.
- `php artisan core:lab-app-readiness`: PASS, verdict `ready`.
- `php artisan core:app-connection-readiness lab-farmasi`: PASS, verdict `ready_for_staging_config`.
- `php artisan core:app-connection-readiness kp-farmasi`: PASS, verdict `ready_for_staging_config`.
- `php artisan core:app-connection-readiness tu-farmasi`: PASS, verdict `ready_for_staging_config`.
- `php artisan core:app-connection-readiness ta-farmasi`: PASS, verdict `ready_for_staging_config`.
- `php artisan core:app-connection-readiness helpdesk-farmasi`: PASS, verdict `missing_api_client`.
- `composer validate`: PASS.
- `php artisan test`: PASS, 284 tests / 1638 assertions.

## Next
1. Jalankan smoke test Lab dari sisi `apps/lab-farmasi` setelah Core URL dan credential staging benar.
2. Putuskan apakah KP perlu HTTP generic smoke, atau cukup memakai bridge/read integration yang sudah ada.
3. Helpdesk tetap future consumer sampai app/adapter dibuat.
