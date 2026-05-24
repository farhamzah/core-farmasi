# TU-CONNECT-21 Cutover Decision Draft Report

Tanggal: 2026-05-24

## Scope

Tahap ini membuat draft keputusan cutover untuk Portal Core HTTP Auth berdasarkan technical UAT evidence. Dokumen ini bukan production approval dan tidak melakukan cutover.

## Evidence

- HTTP shadow ready.
- Core password verification endpoint ready.
- App access dosen `user_id=3`: has-access.
- App access mahasiswa `user_id=4`: has-access.
- Dosen login previously proven.
- Mahasiswa login previously proven.
- Wrong password generic failure: OK.
- Logout/session safety: OK.
- No password/hash/token/secret exposure.
- Admin auth unaffected.
- TU tests pass.

## Limitations

- Fresh successful login not executed in TU-CONNECT-20 because password was unavailable at Codex runtime.
- Final human tester sign-off pending.
- Browser visual QA pending.
- User without app access negative test skipped.
- Production environment not validated.
- Production rollback not executed.

## Decision Draft

- GO for staging/local controlled use with `TU_PORTAL_AUTH_MODE=core_http`.
- NO-GO for production cutover until final sign-off or explicit owner risk acceptance.
- NO-GO for SSO/auto-login/token URL.

## Production Status

Production cutover: NO-GO.

Repository/default production mode remains disabled.

## Files Created

- `apps/tu-farmasi/docs/TU-CONNECT-21-CUTOVER-DECISION-DRAFT.md`
- `apps/tu-farmasi/docs/TU-CONNECT-21-RISK-ACCEPTANCE-FORM.md`
- `apps/tu-farmasi/docs/TU-CONNECT-21-PRODUCTION-CUTOVER-PREREQUISITES.md`
- `apps/core-farmasi/docs/reports/TU-CONNECT-21-CUTOVER-DECISION-DRAFT-REPORT.md`

## Next Step

If owner accepts risk:

- TU-CONNECT-22 Staging Controlled Enablement.

If human UAT is required:

- Execute human UAT first.

If bug is found:

- Auth bugfix before any cutover planning.

