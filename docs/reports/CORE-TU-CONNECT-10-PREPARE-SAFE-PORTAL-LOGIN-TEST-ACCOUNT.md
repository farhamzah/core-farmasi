# CORE-TU-CONNECT-10 Prepare Safe Portal Login Test Account

Tanggal: 2026-05-24

## Tujuan

Menyiapkan akun uji aman di Core untuk mengulang TU-CONNECT-9 dan membuktikan login portal TU via Core HTTP password verification endpoint dengan sukses. Tahap ini tidak membuat SSO, auto-login, token URL, write-back, migration, atau production cutover.

## Previous Context

TU-CONNECT-9 menunjukkan:

- mode `core_http` sementara: preflight READY;
- password endpoint Core: ready;
- app access `--user-id=3`: OK, `has-access`;
- invalid login/password dummy: OK, generic failure;
- login sukses user nyata: skipped karena password uji aman belum tersedia saat itu;
- rollback ke disabled: OK.

## Akun Test

Status: selected existing local/staging test account.

Detail aman:

- user_id: 3
- type: dosen
- active user: yes
- app_code: `tu-farmasi`
- app access: active
- role_slug: `dosen`
- email domain: local/test domain

Password:

- password test: available to tester through existing local/staging test credential channel
- password printed: no
- password/hash/token included in report: no
- password reset performed: no

Catatan: tidak ada password atau hash yang ditulis pada dokumen ini.

## App Access Check

`user_id=3` memiliki active app access:

- `user_app_accesses.app_code = tu-farmasi`
- `user_app_accesses.is_active = true`
- `user_app_accesses.role_slug = dosen`

Core readiness juga mengonfirmasi:

- app registered: yes
- app active: yes
- active API client count: 1
- active user app access count: 1
- portal verify endpoint available: yes
- readiness verdict: `ready_for_staging_config`

## Password Safety

Tidak ada reset password dilakukan karena akun test existing sudah memiliki password test lokal yang tersedia untuk tester melalui kanal aman local/staging.

Guardrail:

- jangan menulis password di report;
- jangan commit credential;
- jangan memakai akun production sensitif;
- jangan reset akun production;
- jangan memakai password test pada production.

## Commands Run

```bash
php artisan core:tu-connection-readiness
php artisan test
```

Inspeksi akun dilakukan via read-only `php artisan tinker --execute` dengan output aman tanpa password/hash.

## Test Result

- `php artisan core:tu-connection-readiness`: OK, verdict `ready_for_staging_config`
- `php artisan test`: 206 passed / 998 assertions

## Security Guardrails

- no password/hash output: OK
- no secret output: OK
- no production sensitive reset: OK
- no mass assignment: OK
- no migration: OK
- no migrate:fresh/reset/rollback: OK
- no SSO: OK
- no token URL: OK
- no auto-login: OK
- no write-back to TU: OK
- no TU/KP/SAFA changes: OK

## Next Step

Ulangi TU-CONNECT-9 Portal Login Local/Staging Manual QA dengan akun test:

- user_id: 3
- type: dosen
- app access: active
- password: ambil melalui kanal aman tester/local staging, jangan tulis di report

Jika login berhasil:

- lanjut TU-CONNECT-10 Portal Core HTTP Auth Go/No-Go for Staging dari sisi TU.
