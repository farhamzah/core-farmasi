# TU-CONNECT-12 Staging Auth UAT Checklist Report

Tanggal: 2026-05-24

## Scope

Membuat checklist dan result template UAT staging untuk portal login TU via Core HTTP auth bagi dosen dan mahasiswa.

Tahap ini hanya dokumentasi/checklist. Tidak ada runtime change, migration, SSO, auto-login, token URL, write-back ke Core, atau production cutover.

## Evidence from TU-CONNECT-10 / TU-CONNECT-11

Evidence yang sudah tersedia:

- GO for staging/local use only.
- Dosen login via Core HTTP terbukti sukses.
- Dosen test `user_id=3` memiliki app access `tu-farmasi` aktif.
- Mahasiswa login via Core HTTP terbukti sukses via email.
- Mahasiswa login via Core HTTP terbukti sukses via NIM.
- Mahasiswa test `user_id=4` memiliki app access `tu-farmasi` aktif.
- Logout berhasil dan session cleared.
- Wrong password menghasilkan generic failure.
- Session safety OK: tidak ada password/hash/token/secret.
- Default rollback OK: `TU_PORTAL_AUTH_MODE=disabled`.
- TU tests: 268 passed / 1423 assertions.
- Core tests: 206 passed / 998 assertions.

## UAT Checklist Created

TU docs created:

- `apps/tu-farmasi/docs/TU-CONNECT-12-STAGING-AUTH-UAT-CHECKLIST.md`
- `apps/tu-farmasi/docs/TU-CONNECT-12-STAGING-AUTH-UAT-RESULT-TEMPLATE.md`

Checklist mencakup:

- environment setup;
- dosen login test;
- mahasiswa login test;
- negative tests;
- portal flow after login;
- security checks;
- rollback steps.

Result template mencakup:

- tester/date/environment;
- user type;
- login method;
- expected/actual/pass-fail;
- screenshot path optional;
- password-not-recorded checkbox;
- sign-off table.

## Limitations

- Browser visual UAT belum dieksekusi pada tahap ini.
- User tanpa app access dan inactive app access masih perlu disiapkan untuk negative UAT staging.
- Production cutover tetap belum disetujui.
- Staging UAT sign-off masih perlu diisi oleh tester/TU.

## Production Status

Production remains NO-GO.

Syarat minimum sebelum production:

- staging UAT dosen selesai;
- staging UAT mahasiswa selesai;
- negative app access tests selesai;
- portal submit/download checks selesai;
- security checks selesai;
- tester/TU sign-off;
- explicit production cutover approval.

## Security Confirmation

- no password in docs/report: OK
- no token/hash/secret in docs/report: OK
- no SSO: OK
- no auto-login: OK
- no token URL: OK
- no write-back: OK
- no admin auth replacement: OK
- no Core runtime/code changes: OK
- no KP/SAFA changes: OK

## Commands Run

TU:

- `php artisan tu:portal-login-preflight`
- `php artisan tu:core-smoke-test --user-id=3`
- `php artisan tu:core-smoke-test --user-id=4`
- `php artisan test`
- `composer validate`

Core:

- Core tests not run because Core runtime/code was not changed; only this documentation report was created.

## Recommended Next Step

TU-CONNECT-13 Staging Auth UAT Execution Result.

If UAT is completed and signed off, the next planning track may be:

- Production cutover planning, still without SSO/token URL and only after explicit approval.
