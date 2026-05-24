# TU-CONNECT-18 Staging Auth UAT Sign-off Result

Tanggal: 2026-05-24

## Scope

Tahap ini mencatat status eksekusi UAT staging untuk login portal TU via Core HTTP setelah tombol `Login Mahasiswa/Dosen` diperjelas pada UI portal.

Tahap ini tidak melakukan production cutover, tidak mengubah runtime Core, tidak mengubah database, dan tidak mengaktifkan `core_http` sebagai default.

## Previous Evidence

- Dosen login via Core HTTP proven di level HTTP/local-staging.
- Mahasiswa login via Core HTTP proven di level HTTP/local-staging melalui email dan NIM.
- App access `tu-farmasi` untuk dosen dan mahasiswa: has-access.
- Logout/session safety: OK.
- Wrong password: generic failure.
- Tombol `Login Mahasiswa/Dosen` sudah tersedia di root landing, portal home, header portal, detail layanan, dan halaman login.
- Automated tests terakhir setelah polish tombol login: 269 passed / 1441 assertions.

## UAT Execution Status

Status: NOT EXECUTED.

Reason: belum ada hasil tester/staging sign-off yang diberikan untuk dicatat. Karena tidak ada hasil aktual dari tester, report ini tidak menandai UAT sebagai pass.

## Environment

- Default `TU_PORTAL_AUTH_MODE=disabled`.
- Mode staging yang boleh dipakai sementara untuk UAT: `core_http`.
- `TU_CORE_HTTP_ENABLED=true` dan `TU_CORE_READ_MODE=http-shadow` digunakan hanya jika staging UAT dijalankan eksplisit.
- Production cutover: no.

## Scenario Results

| Scenario | Result | Notes |
| --- | --- | --- |
| Tombol login visible | Proven by automated test | UI link `/portal/login` tersedia di landing, portal, header, detail layanan, dan login page. |
| Dosen login | Not executed by tester | HTTP-level/local-staging proof exists from previous QA. |
| Mahasiswa login | Not executed by tester | HTTP-level/local-staging proof exists from previous QA. |
| Wrong password | Not executed by tester | Generic failure proof exists from previous QA. |
| Logout | Not executed by tester | Session clear proof exists from previous QA. |
| Portal `/pengajuan` | Not executed by tester | Must be verified in staging UAT. |
| Portal `/dokumen` | Not executed by tester | Must be verified in staging UAT. |
| Admin auth unaffected | Not executed by tester | Automated/admin route proof exists; staging UAT still required. |
| Security checks | Not executed by tester | Must verify no password/token/secret/path leak during staging UAT. |

## Bug List

No new UAT bug recorded because staging UAT was not executed by tester.

## Sign-off Status

- Tester sign-off: not available.
- Owner sign-off: not available.
- Staging auth UAT decision: NOT EXECUTED.
- Production cutover: no.

## Security Confirmation

- No password written to this report.
- No token/secret written to this report.
- No SSO.
- No auto-login.
- No token URL.
- No write-back to Core.
- Admin auth remains separate.
- Default portal auth remains disabled.

## Recommended Next Step

Execute the staging UAT package:

- `apps/tu-farmasi/docs/TU-CONNECT-12-STAGING-AUTH-UAT-CHECKLIST.md`
- `apps/tu-farmasi/docs/TU-CONNECT-12-STAGING-AUTH-UAT-RESULT-TEMPLATE.md`
- `apps/tu-farmasi/docs/TU-CONNECT-14-STAGING-AUTH-UAT-EXECUTION-RUNBOOK.md`
- `apps/tu-farmasi/docs/TU-CONNECT-14-STAGING-AUTH-UAT-SCENARIOS.md`
- `apps/tu-farmasi/docs/TU-CONNECT-14-STAGING-AUTH-UAT-SIGNOFF.md`

After tester completes and signs off, record actual results in the next stage before any production cutover planning.

