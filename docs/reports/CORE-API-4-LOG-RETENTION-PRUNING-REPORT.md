# CORE-API-4 Log Retention & Pruning Report

## Scope
Tahap ini membuat kebijakan retensi dan pruning untuk `core_api_request_logs`. Fitur ini hanya mengelola audit log API internal secara aman, bukan endpoint API baru, bukan SSO, bukan auto-login, dan bukan perubahan data master.

## Previous Reports Reviewed
- CORE-API-3 API Audit & Rate Limit per Client Report
- CORE-API-2 App Client Credentials & Token Rotation Report

## Files Changed
- `config/core_api.php`
- `app/Services/CoreApiLogPruningService.php`
- `app/Console/Commands/PruneCoreApiRequestLogsCommand.php`
- `bootstrap/app.php`
- `tests/Feature/CoreApiClientCredentialTest.php`
- `docs/CORE-INTERNAL-API.md`
- `README.md`
- `docs/reports/CORE-API-4-LOG-RETENTION-PRUNING-REPORT.md`

## Config
`config/core_api.php` ditambah konfigurasi `audit_logs`.

Default:
- `enabled`: true
- `retention_days`: 90
- `keep_failed_requests_days`: 180
- `prune_chunk_size`: 1000
- `keep_recent_minimum`: 1000
- `prune_enabled`: true
- `dry_run_default`: true

Env fallback:
- `CORE_API_LOG_ENABLED`
- `CORE_API_LOG_RETENTION_DAYS`
- `CORE_API_LOG_FAILED_RETENTION_DAYS`
- `CORE_API_LOG_PRUNE_CHUNK_SIZE`
- `CORE_API_LOG_KEEP_RECENT_MINIMUM`
- `CORE_API_LOG_PRUNE_ENABLED`
- `CORE_API_LOG_PRUNE_DRY_RUN`

Tidak ada secret atau credential yang ditulis di config.

## Pruning Service
`CoreApiLogPruningService` dibuat untuk menghitung dan membersihkan log lama secara aman.

Behavior:
- dry-run menghitung eligible logs tanpa delete.
- force prune menghapus log eligible.
- cutoff memakai `created_at < cutoff_date`.
- successful request memakai `retention_days`.
- failed/non-success request memakai `keep_failed_requests_days` secara default.
- opsi `include_failed` membuat failed requests mengikuti cutoff normal.
- delete dilakukan dalam chunk.
- `keep_recent_minimum` menjaga jumlah log terbaru agar tidak semua log habis karena cutoff.
- retention invalid (`<= 0`) tidak melakukan prune dan mengembalikan error aman.

Service hanya menyentuh `core_api_request_logs`. Tidak menghapus data master, users, roles, app access, atau API clients.

## Artisan Command
Command dibuat:

```bash
php artisan core:prune-api-request-logs
```

Options:
- `--dry-run`
- `--force`
- `--days=`
- `--chunk=`
- `--include-failed`

Default behavior adalah dry-run. Delete aktual hanya terjadi saat `--force` diberikan. Command menampilkan summary metrik, tidak menampilkan row log penuh, tidak menampilkan body, header, token, password, atau secret.

Contoh:

```bash
php artisan core:prune-api-request-logs --dry-run
php artisan core:prune-api-request-logs --force
php artisan core:prune-api-request-logs --force --days=120
```

## Admin UI
`CoreApiRequestLogResource` tetap read-only dan protected. Tidak ada bulk delete manual yang ditambahkan agar pembersihan log tetap lewat command/service yang punya dry-run dan force safety.

Dokumentasi dan README menjelaskan kebijakan retention/pruning serta command operasional.

## Documentation
`docs/CORE-INTERNAL-API.md` diperbarui dengan:
- konfigurasi retensi log
- command dry-run dan force
- failed request retention
- chunk delete
- larangan menyimpan secret/body/header
- catatan scheduler production belum diwajibkan pada baseline ini

`README.md` diperbarui dengan catatan singkat bahwa API request log memiliki pruning command dengan dry-run/force safety.

## Security Confirmation
- Tidak ada secret/body/header yang disimpan atau ditampilkan.
- Tidak ada data master yang dihapus.
- Tidak ada API clients yang dihapus.
- Tidak ada users/roles/app access yang dihapus.
- Tidak ada prune tanpa cutoff.
- Tidak ada delete tanpa `--force`.
- Dry-run tersedia dan menjadi default.
- Tidak ada SSO.
- Tidak ada auto-login.
- Tidak ada token URL.
- Tidak ada public API baru.
- `/admin/login` tidak diubah.
- Role access tidak dilonggarkan.

## Commands Run
- `php artisan optimize:clear` - OK
- `php artisan core:prune-api-request-logs --dry-run` - OK
  - total logs: 0
  - eligible logs: 0
  - deleted logs: 0
- `php artisan test` - OK, 156 passed / 748 assertions
- `php artisan migrate` - tidak dijalankan karena tidak ada migration baru pada tahap ini
- `npm run build` - tidak dijalankan karena tidak ada perubahan frontend asset/CSS/JS

## Test Result
`php artisan test` berhasil:

- 156 tests passed
- 748 assertions

Coverage yang ditambahkan:
- pruning service dry-run menghitung eligible logs tanpa delete.
- force prune menghapus hanya log lama.
- log baru tetap dipertahankan.
- retention invalid tidak menghapus log.
- failed request retention lebih lama secara default.
- command dry-run tidak delete.
- command `--force` delete sesuai cutoff.
- pruning tidak menghapus `core_api_clients`.
- resource API request log tetap protected/read-only lewat existing coverage.

## Manual Check
- dry-run works: OK
- force behavior tested: OK
- old logs eligible: OK
- new logs retained: OK
- protected logs UI OK: OK
- no 500 error: OK

## Guardrails Confirmation
- Tidak menjalankan migrate:fresh.
- Tidak menjalankan migrate:reset.
- Tidak menjalankan migrate:rollback.
- Tidak drop database.
- Tidak menghapus data master.
- Tidak menghapus api clients.
- Tidak prune log baru.
- Tidak prune tanpa cutoff.
- Tidak execute import KP.
- Tidak mengubah database KP.
- Tidak menyentuh SAFA/KP/TU.
- Tidak membuat Core public.
- Tidak membuat SSO/bypass login.
- Tidak membuat cross-app user session.
- Tidak membuat token di URL.
- Tidak expose password/hash/token/secret.
- Tidak menyimpan secret di logs.
- Tidak menyimpan request body penuh.
- Tidak mengubah login resmi `/admin/login`.
- Tidak melonggarkan role access.

## Risks / Notes
- Scheduler production belum diaktifkan otomatis; command sudah siap untuk SOP operasional.
- Monitoring/alerting belum dibuat.
- Per-client usage dashboard bisa dibuat nanti.
- Retention policy default perlu divalidasi lagi saat volume trafik consumer app nyata sudah terlihat.

## Recommended Next Step
Rekomendasi tahap berikutnya: **CORE-QA-2 Final Regression & Release Readiness**.

Alasannya, API audit, rate limit, credential safety, dan pruning sudah siap. Tahap QA final akan membantu memastikan seluruh rangkaian Core stabil sebelum integrasi consumer app pertama berjalan.
