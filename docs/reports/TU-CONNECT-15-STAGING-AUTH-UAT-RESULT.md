# TU-CONNECT-15 Staging Auth UAT Result

Tanggal: 2026-05-24

## Scope

Mencatat hasil eksekusi UAT staging auth untuk portal login TU via Core HTTP bagi dosen dan mahasiswa.

Tahap ini hanya dokumentasi/report. Tidak ada runtime change, migration, database change, write-back ke Core, SSO, auto-login, token URL, admin auth replacement, atau production cutover.

## Previous Evidence

Evidence teknis yang sudah tersedia:

- Dosen login HTTP-level proven: yes, `user_id=3`.
- Mahasiswa login HTTP-level proven: yes, `user_id=4`, email dan NIM.
- App access `tu-farmasi` dosen/mahasiswa: has-access.
- Logout/session safety: OK pada QA HTTP-level sebelumnya.
- Wrong password: OK, generic failure pada QA HTTP-level sebelumnya.
- Default rollback: `TU_PORTAL_AUTH_MODE=disabled`.
- TU automated tests: 268 passed / 1423 assertions.
- Core tests from previous QA: 206 passed / 998 assertions.
- Production cutover: no.

## UAT Execution Status

Status: NOT EXECUTED.

Reason:

- Belum ada hasil tester/staging sign-off.
- `TU-CONNECT-14-STAGING-AUTH-UAT-RESULT-FORM.md` masih berupa form kosong.
- `TU-CONNECT-14-STAGING-AUTH-UAT-SIGNOFF.md` belum diisi.
- Tidak ada screenshot path, bug report, atau evidence tester baru yang diberikan untuk tahap ini.

This report does not invent pass results for tester/staging UAT.

## Environment

Recorded current safe/default state:

- default portal auth: `TU_PORTAL_AUTH_MODE=disabled`
- HTTP shadow config available for staging/local
- production cutover: no

UAT execution mode expected when tester runs it:

```env
TU_PORTAL_AUTH_MODE=core_http
TU_CORE_HTTP_ENABLED=true
TU_CORE_READ_MODE=http-shadow
```

## Scenario Results

| Scenario | Status | Result | Notes |
| --- | --- | --- | --- |
| AUTH-ENV-01 environment readiness | NOT EXECUTED | Not recorded | Tester has not run staging UAT. |
| AUTH-DOSEN-01 dosen email login | NOT EXECUTED | Not recorded | Technical HTTP-level evidence exists from prior QA. |
| AUTH-DOSEN-02 dosen NIDN/NIP login | NOT EXECUTED | Not recorded | Tester has not run staging UAT. |
| AUTH-MHS-01 mahasiswa email login | NOT EXECUTED | Not recorded | Technical HTTP-level evidence exists from prior QA. |
| AUTH-MHS-02 mahasiswa NIM login | NOT EXECUTED | Not recorded | Technical HTTP-level evidence exists from prior QA. |
| AUTH-NEG-01 wrong password | NOT EXECUTED | Not recorded | Technical HTTP-level evidence exists from prior QA. |
| AUTH-NEG-02 user without app access | NOT EXECUTED | Not recorded | Safe user still needs to be prepared for UAT. |
| AUTH-NEG-03 inactive app access | NOT EXECUTED | Not recorded | Safe user still needs to be prepared for UAT. |
| AUTH-NEG-04 disabled mode rollback | NOT EXECUTED | Not recorded | Current default remains disabled. |
| AUTH-NEG-05 Core unavailable | NOT EXECUTED | Not recorded | Tester has not run staging UAT. |
| AUTH-NEG-06 admin auth unaffected | NOT EXECUTED | Not recorded | Tester has not run staging UAT. |
| AUTH-FLOW-01 portal pengajuan | NOT EXECUTED | Not recorded | Tester has not run staging UAT. |
| AUTH-FLOW-02 submit/upload/tracking | NOT EXECUTED | Not recorded | Tester has not run staging UAT. |
| AUTH-FLOW-03 final archive download | NOT EXECUTED | Not recorded | Tester has not run staging UAT. |
| AUTH-SEC-01 security checks | NOT EXECUTED | Not recorded | Tester has not run staging UAT. |

## Bug List

No UAT bugs were reported in this stage.

Open items:

- Execute the TU-CONNECT-14 runbook in staging.
- Fill the TU-CONNECT-14 result form.
- Complete sign-off.
- Prepare safe no-app-access and inactive-app-access users if those negative scenarios are required.

## Security Confirmation

- no password in report: OK
- no token/hash/secret in report: OK
- no SSO: OK
- no auto-login: OK
- no token URL: OK
- no write-back: OK
- admin auth unaffected: OK
- production cutover: no
- no Core runtime/code changes: OK
- no KP/SAFA changes: OK

## Production Cutover Status

Production cutover: NO-GO.

Reasons:

- Staging UAT by tester is not executed.
- Sign-off is not available.
- Negative app-access scenarios remain unexecuted in UAT.
- Browser/screenshot evidence is unavailable.

## Commands Run

TU:

- `php artisan tu:portal-login-preflight`
- `php artisan tu:core-smoke-test --user-id=3`
- `php artisan tu:core-smoke-test --user-id=4`
- `php artisan test`
- `composer validate`

Core:

- Core tests not run because Core runtime/code was not changed; only docs/report were created.

## Test Result

TU:

- `php artisan tu:portal-login-preflight`: OK, safe default `NOT READY` because `TU_PORTAL_AUTH_MODE=disabled`.
- `php artisan tu:core-smoke-test --user-id=3`: OK, has-access.
- `php artisan tu:core-smoke-test --user-id=4`: OK, has-access.
- `php artisan test`: 268 passed / 1423 assertions.
- `composer validate`: OK, `./composer.json is valid`.

Core:

- `php artisan test`: not run because Core runtime/code was not changed; docs/report only.

## Guardrails Confirmation

- Tidak menjalankan migration.
- Tidak menjalankan migrate:fresh/reset/rollback.
- Tidak mengubah database TU/Core.
- Tidak cutover production.
- Tidak mengganti auth TU admin.
- Tidak membuat SSO.
- Tidak membuat auto-login.
- Tidak membuat token URL.
- Tidak menyimpan password/token.
- Tidak expose secret/password/hash/token.
- Tidak write-back.
- Tidak mengubah Core runtime/code.
- Tidak menyentuh KP/SAFA.

## Recommended Next Step

Execute staging UAT checklist first.

Use:

- `apps/tu-farmasi/docs/TU-CONNECT-14-STAGING-AUTH-UAT-EXECUTION-RUNBOOK.md`
- `apps/tu-farmasi/docs/TU-CONNECT-14-STAGING-AUTH-UAT-SCENARIOS.md`
- `apps/tu-farmasi/docs/TU-CONNECT-14-STAGING-AUTH-UAT-RESULT-FORM.md`
- `apps/tu-farmasi/docs/TU-CONNECT-14-STAGING-AUTH-UAT-SIGNOFF.md`

If UAT passes and sign-off is complete:

- TU-CONNECT-16 Production Cutover Planning for Portal Core HTTP Auth

If UAT fails:

- TU-CONNECT-16 Auth Bugfix / UAT Issue Resolution
