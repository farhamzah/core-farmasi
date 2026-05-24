# Core Pre-Staging Checklist

Checklist ini digunakan sebelum real staging smoke test KP/TU terhadap Core API dan Core Profile Portal link.

## Test Baseline
- Core tests pass.
- KP tests pass.
- TU tests pass.
- Tidak ada known blocking bug dari regression terakhir.

## Backup / Restore
- Backup Core staging database selesai.
- Backup KP staging database tersedia jika smoke/cutover menyentuh KP flow.
- Backup TU staging database tersedia jika smoke/cutover menyentuh TU flow.
- Backup disimpan di secure storage.
- Backup tidak berada di public web directory.
- Backup tidak masuk repository.
- Restore sudah diuji pada disposable database.
- Restore verification checklist selesai.

## Core API Client
- Core application `kp-farmasi` aktif.
- Core application `tu-farmasi` aktif.
- KP staging app client dibuat.
- TU staging app client dibuat.
- Ability KP minimal tersedia:
  - `read:users`
  - `read:students`
  - `read:lecturers`
  - `read:study-programs`
  - `read:app-access`
  - `read:leadership`
- Ability TU minimal tersedia:
  - `read:users`
  - `read:students`
  - `read:lecturers`
  - `read:employees`
  - `read:study-programs`
  - `read:departments`
  - `read:app-access`
  - `read:leadership`

## Secret Management
- Client secrets disimpan di secret manager/environment.
- Secret tidak masuk repo, docs, report, chat, screenshot, atau logs.
- Staging secret berbeda dari production secret.
- Rotation/revocation SOP tersedia.
- Emergency leak response siap.

## Staging Environment
KP env configured securely:
- `KP_CORE_HTTP_ENABLED=true` only for staging smoke.
- `KP_CORE_READ_MODE=legacy` or approved shadow mode.
- `KP_CORE_BASE_URL` points to Core staging.
- `KP_CORE_PROFILE_URL` points to Core staging `/profile`.
- `KP_CORE_CLIENT_ID` set securely.
- `KP_CORE_CLIENT_SECRET` set securely.

TU env configured securely:
- `TU_CORE_HTTP_ENABLED=true` only for staging smoke.
- `TU_CORE_READ_MODE=disabled` or approved shadow mode.
- `TU_CORE_BASE_URL` points to Core staging.
- `TU_CORE_PROFILE_URL` points to Core staging `/profile`.
- `TU_CORE_CLIENT_ID` set securely.
- `TU_CORE_CLIENT_SECRET` set securely.

After env changes:
- KP config cache cleared.
- TU config cache cleared.

## Operational SOP Ready
- `CORE-APP-CLIENT-CREDENTIAL-SOP.md` reviewed.
- `CORE-KP-TU-STAGING-SMOKE-EXECUTION-PLAN.md` reviewed.
- `CORE-BACKUP-RESTORE-SOP.md` reviewed.
- `CORE-SECRET-MANAGEMENT-READINESS.md` reviewed.
- API log pruning SOP ready.
- Rollback/disable plan ready.
- Smoke result template ready.

## Profile Link
- Core `/profile` reachable in staging.
- KP profile URL configured.
- TU profile URL configured.
- Profile link has no token/secret/session data.
- No SSO expected.
- User education prepared: Core login may be separate.

## Go / No-Go Owner Approval
Before smoke:
- owner/admin approves timing.
- expected test users identified without sharing passwords in docs.
- evidence location is secure.
- no real secret will be pasted in result report.

Go only if backup/restore, secret management, env config, SOP, and rollback/disable are ready.

## Do Not Do
- Jangan run production cutover.
- Jangan membuat SSO.
- Jangan membuat token URL.
- Jangan write-back dari KP/TU ke Core.
- Jangan restore ke database aktif.
- Jangan menjalankan migration dari smoke test.
- Jangan menghapus legacy/local profile form.
