# TU-CONNECT-19 UAT Tester Instruction Pack Report

Tanggal: 2026-05-24

## Scope

Tahap ini membuat paket instruksi UAT ringkas untuk tester agar login portal TU via Core HTTP dapat diuji dan hasil sign-off dapat dicatat.

Tahap ini hanya dokumentasi. Tidak ada perubahan runtime Core/TU, tidak ada migration, tidak ada write-back, dan tidak ada production cutover.

## Previous Evidence

Berdasarkan TU-CONNECT-18:

- Dosen login Core HTTP sudah terbukti di level HTTP/local-staging.
- Mahasiswa login Core HTTP sudah terbukti di level HTTP/local-staging.
- Tombol `Login Mahasiswa/Dosen` sudah visible dan terbukti lewat automated test.
- Staging UAT tester masih pending.
- Production cutover masih no.
- Default tetap `TU_PORTAL_AUTH_MODE=disabled`.

## Files Created

- `apps/tu-farmasi/docs/TU-CONNECT-19-UAT-TESTER-INSTRUCTIONS.md`
- `apps/tu-farmasi/docs/TU-CONNECT-19-UAT-RESULT-FORM-FOR-TESTER.md`
- `apps/tu-farmasi/docs/TU-CONNECT-19-UAT-SIGNOFF-FOR-TESTER.md`
- `apps/tu-farmasi/docs/TU-CONNECT-19-UAT-OWNER-SUMMARY.md`
- `apps/core-farmasi/docs/reports/TU-CONNECT-19-UAT-TESTER-INSTRUCTION-PACK-REPORT.md`

## UAT Status

UAT tester: pending.

Tester sign-off: not available.

## Production Cutover Status

Production cutover: no.

Cutover planning must wait until tester runs UAT, records results, and signs off.

## Security Confirmation

- No password in docs.
- No token/secret in docs.
- No SSO.
- No auto-login.
- No token URL.
- No write-back to Core.
- Admin auth remains separate.
- Default portal auth remains disabled.

## Next Step

Give the TU-CONNECT-19 tester instruction pack to tester. After tester fills result form and sign-off, process the result in TU-CONNECT-20.

