# TU-CONNECT-14 Staging Auth UAT Package Report

Tanggal: 2026-05-24

## Scope

Membuat paket eksekusi UAT staging untuk portal login TU via Core HTTP auth bagi dosen dan mahasiswa.

Tahap ini hanya dokumentasi. Tidak ada runtime change, migration, database change, SSO, auto-login, token URL, write-back ke Core, atau production cutover.

## Previous Evidence

- TU-CONNECT-10: GO for staging/local use only.
- TU-CONNECT-11: mahasiswa login via Core HTTP proven.
- TU-CONNECT-13: staging tester UAT not executed.
- Dosen login HTTP-level proven: yes.
- Mahasiswa login HTTP-level proven: yes.
- App access dosen/mahasiswa: has-access.
- Session safety: OK.
- Default rollback: `TU_PORTAL_AUTH_MODE=disabled`.
- Production cutover: no.

## Docs Created

TU docs:

- `apps/tu-farmasi/docs/TU-CONNECT-14-STAGING-AUTH-UAT-EXECUTION-RUNBOOK.md`
- `apps/tu-farmasi/docs/TU-CONNECT-14-STAGING-AUTH-UAT-SCENARIOS.md`
- `apps/tu-farmasi/docs/TU-CONNECT-14-STAGING-AUTH-UAT-RESULT-FORM.md`
- `apps/tu-farmasi/docs/TU-CONNECT-14-STAGING-AUTH-UAT-SIGNOFF.md`

Core report:

- `apps/core-farmasi/docs/reports/TU-CONNECT-14-STAGING-AUTH-UAT-PACKAGE-REPORT.md`

## Package Content

The UAT execution package includes:

- staging/local environment setup;
- temporary `core_http` mode instructions;
- preflight commands;
- app access smoke commands for dosen and mahasiswa;
- URL list;
- safe test account references without passwords;
- detailed scenario checklist;
- result form;
- bug list table;
- rollback instructions;
- sign-off form.

## Production Status

Production cutover remains NO-GO.

Production cannot proceed until:

- staging UAT is executed;
- result form is filled;
- sign-off is approved;
- blocker bugs are resolved;
- production cutover planning is explicitly approved.

## Security Confirmation

- no password in docs/report: OK
- no token/hash/secret in docs/report: OK
- no SSO: OK
- no auto-login: OK
- no token URL: OK
- no write-back: OK
- no admin auth replacement: OK
- no Core runtime/code change: OK
- no KP/SAFA changes: OK

## Commands Run

TU:

- `php artisan tu:portal-login-preflight`
- `php artisan tu:core-smoke-test --user-id=3`
- `php artisan tu:core-smoke-test --user-id=4`
- `php artisan test`
- `composer validate`

Core:

- Core tests not run because Core runtime/code was not changed; only docs/report were created.

## Recommended Next Step

TU-CONNECT-15 Execute Staging Auth UAT and Record Results.
