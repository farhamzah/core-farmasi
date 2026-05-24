# TU-CONNECT-13 Staging Auth UAT Execution Result

Tanggal: 2026-05-24

## Scope

Mencatat status eksekusi UAT staging untuk portal login TU via Core HTTP auth bagi dosen dan mahasiswa.

Tahap ini hanya dokumentasi/report. Tidak ada runtime change, migration, database change, SSO, auto-login, token URL, write-back ke Core, atau production cutover.

## Previous Evidence

Evidence teknis yang sudah tersedia:

- TU-CONNECT-10: GO for staging/local use only.
- Dosen login Core HTTP proven: `user_id=3`, success.
- Dosen app access `tu-farmasi`: active / has-access.
- Mahasiswa login Core HTTP proven: `user_id=4`, success via email.
- Mahasiswa login Core HTTP proven: `user_id=4`, success via NIM.
- Mahasiswa app access `tu-farmasi`: active / has-access.
- Logout: OK, session cleared.
- Wrong password: OK, generic failure.
- Session safety: OK, no password/hash/token/secret.
- Default rollback: `TU_PORTAL_AUTH_MODE=disabled`.
- TU tests: 268 passed / 1423 assertions.
- Core tests from TU-CONNECT-11: 206 passed / 998 assertions.

## UAT Execution Status

Status: NOT EXECUTED.

Reason:

- Tidak ada hasil tester/staging sign-off baru yang diberikan untuk tahap ini.
- Tidak ada actual UAT result table yang sudah diisi dari `TU-CONNECT-12-STAGING-AUTH-UAT-RESULT-TEMPLATE.md`.
- Tidak ada screenshot path atau bug report hasil UAT staging yang diberikan.

Catatan:

- HTTP-level local/staging QA untuk dosen dan mahasiswa sudah pernah dieksekusi dan sukses.
- Tahap ini tidak mengarang hasil UAT browser/staging oleh tester.
- Production cutover tetap NO-GO sampai UAT staging ditandatangani.

## UAT Results

| Area | Status | Result | Notes |
| --- | --- | --- | --- |
| Environment setup | NOT EXECUTED | Not recorded | Belum ada hasil tester/staging. |
| Dosen login email | NOT EXECUTED | Not recorded | Evidence teknis sebelumnya: success via local/staging HTTP-level QA. |
| Dosen login NIDN/NIP | NOT EXECUTED | Not recorded | Belum ada hasil tester/staging. |
| Mahasiswa login email | NOT EXECUTED | Not recorded | Evidence teknis sebelumnya: success via local/staging HTTP-level QA. |
| Mahasiswa login NIM | NOT EXECUTED | Not recorded | Evidence teknis sebelumnya: success via local/staging HTTP-level QA. |
| Wrong password | NOT EXECUTED | Not recorded | Evidence teknis sebelumnya: generic failure OK. |
| Logout | NOT EXECUTED | Not recorded | Evidence teknis sebelumnya: session cleared OK. |
| `/portal/pengajuan` | NOT EXECUTED | Not recorded | Belum ada hasil tester/staging. |
| `/portal/dokumen` | NOT EXECUTED | Not recorded | Belum ada hasil tester/staging. |
| Portal submit | NOT EXECUTED | Not recorded | Belum ada hasil tester/staging. |
| Portal attachment upload | NOT EXECUTED | Not recorded | Belum ada hasil tester/staging. |
| Portal tracking | NOT EXECUTED | Not recorded | Belum ada hasil tester/staging. |
| Portal archive download | NOT EXECUTED | Not recorded | Belum ada hasil tester/staging. |
| Security checks | NOT EXECUTED | Not recorded | Belum ada hasil tester/staging. |
| Rollback | NOT EXECUTED | Not recorded | Current local default remains disabled. |

## Bugs / Issues

No UAT bugs were reported in this stage.

Known open UAT items:

- Tester must execute `apps/tu-farmasi/docs/TU-CONNECT-12-STAGING-AUTH-UAT-CHECKLIST.md`.
- Tester must fill `apps/tu-farmasi/docs/TU-CONNECT-12-STAGING-AUTH-UAT-RESULT-TEMPLATE.md`.
- User without `tu-farmasi` app access and inactive app access scenarios still need safe test users.
- Browser visual evidence/screenshot paths remain unavailable.

## Security Confirmation

- no password in report: OK
- no password/hash/token in report: OK
- no secret in report: OK
- no SSO: OK
- no auto-login: OK
- no token URL: OK
- no write-back: OK
- no admin auth replacement: OK
- default remains disabled: OK
- no Core runtime/code changes: OK
- no KP/SAFA changes: OK

## Production Cutover Status

Production cutover: NO-GO.

Reasons:

- UAT staging by tester is not executed.
- Sign-off is not available.
- Negative app access scenarios are not fully covered in UAT.
- Browser visual QA evidence is not available.

## Commands Run

TU:

- `php artisan tu:portal-login-preflight`
- `php artisan tu:core-smoke-test --user-id=3`
- `php artisan tu:core-smoke-test --user-id=4`
- `php artisan test`
- `composer validate`

Core:

- Core tests not run because Core runtime/code was not changed; only this documentation report was created.

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

- `apps/tu-farmasi/docs/TU-CONNECT-12-STAGING-AUTH-UAT-CHECKLIST.md`
- `apps/tu-farmasi/docs/TU-CONNECT-12-STAGING-AUTH-UAT-RESULT-TEMPLATE.md`

After tester sign-off:

- TU-CONNECT-14 Production Cutover Planning for Portal Core HTTP Auth

If UAT fails:

- TU-CONNECT-14 Auth Bugfix / UAT Issue Resolution
