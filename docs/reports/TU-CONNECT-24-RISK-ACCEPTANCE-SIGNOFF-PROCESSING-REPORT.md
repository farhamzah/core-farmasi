# TU-CONNECT-24 Risk Acceptance Sign-off Processing Report

Tanggal: 2026-05-24

## Scope

Tahap ini menyiapkan dokumen risk acceptance dan human UAT sign-off consolidation untuk Portal Core HTTP Auth. Tahap ini tidak melakukan production cutover dan tidak mengubah runtime Core/TU.

## Evidence

- Controlled enablement execution: PASS WITH LIMITATIONS.
- Temporary `core_http`: preflight READY.
- App access smoke `user_id=3`: has-access.
- App access smoke `user_id=4`: has-access.
- Wrong password generic failure: OK.
- Rollback to `TU_PORTAL_AUTH_MODE=disabled`: OK.
- Previous login proof for dosen and mahasiswa remains evidence.
- No SSO, auto-login, token URL, or write-back.
- Admin auth unaffected.
- TU tests pass.

## Limitations

- Fresh successful login in latest run was skipped because password was not available to Codex runtime.
- Human UAT/sign-off pending.
- Browser visual QA limited.
- Production environment not validated.
- Production rollback not executed.

## Documents Created

- `apps/tu-farmasi/docs/TU-CONNECT-24-OWNER-RISK-ACCEPTANCE.md`
- `apps/tu-farmasi/docs/TU-CONNECT-24-HUMAN-UAT-SIGNOFF-CONSOLIDATION.md`
- `apps/tu-farmasi/docs/TU-CONNECT-24-CUTOVER-READINESS-DECISION-SUMMARY.md`
- `apps/core-farmasi/docs/reports/TU-CONNECT-24-RISK-ACCEPTANCE-SIGNOFF-PROCESSING-REPORT.md`

## Production Status

Production cutover: NO-GO.

Production remains blocked until owner risk acceptance or human UAT sign-off is completed.

## Next Step

If owner accepts risk:

- TU-CONNECT-25 Production Cutover Planning for Portal Core HTTP Auth.

If owner does not accept risk:

- Repeat fresh login execution with runtime password or execute full human UAT.

