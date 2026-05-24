# TU-CONNECT-7 Portal Auth HTTP Shadow Plan

Tanggal: 2026-05-24

## Scope

Evaluasi dan rencana integrasi portal auth TU memakai Core HTTP shadow. Tahap ini hanya menambahkan skeleton di TU dan report dokumentasi. Tidak ada perubahan runtime Core.

## Previous Evidence

- CORE-TU-CONNECT-3: API client `tu-farmasi` sudah dibuat/rotated dan siap staging config.
- TU-CONNECT-4: basic smoke TU ke Core OK; user-level app access saat itu belum diuji.
- TU-CONNECT-5: user-level smoke `--user-id=3` OK, `has_access=true`, context role dosen.
- TU-CONNECT-6: GO untuk HTTP shadow mode local/staging only. NOT GO untuk production cutover, SSO, auto-login, token URL, atau auth replacement.

## Current Environment

- TU_CORE_HTTP_ENABLED: true pada local/staging smoke.
- TU_CORE_READ_MODE: http-shadow pada local/staging smoke.
- TU_CORE_BASE_URL: configured.
- TU_CORE_PROFILE_URL: configured.
- TU_CORE_CLIENT_ID: configured.
- TU_CORE_CLIENT_SECRET: hidden.
- app_code: `tu-farmasi`.

## Current TU Auth Findings

- Existing `CorePortalAuthService` masih DB read-only oriented dan memakai Laravel `Hash::check()`.
- Existing `CoreFarmasiClient` sudah HTTP app-client oriented dan mendukung directory/app access.
- App access check via HTTP sudah terbukti untuk `user_id=3`.
- Endpoint HTTP untuk password verification portal belum tersedia.

## Skeleton Added in TU

- `CoreHttpPortalAuthService` disiapkan sebagai adapter portal auth HTTP shadow.
- `attempt()` belum mengirim password ke Core dan gagal aman dengan `core_http_password_auth_not_available`.
- `checkAppAccess()` memakai endpoint app access HTTP yang sudah terbukti smoke.
- `mapCoreProfileToIdentity()` memetakan profile safe array menjadi `PortalIdentity`.
- `tu:portal-login-preflight` membedakan DB read-only readiness, HTTP shadow readiness, app access readiness, dan password auth readiness.

## Readiness

- HTTP shadow: ready untuk local/staging.
- App access via HTTP: ready.
- User-level app access `user_id=3`: `has_access=true`.
- Password auth via HTTP: not available.
- Full portal login via HTTP: not ready.

## Required Core Endpoint

Core perlu endpoint khusus untuk verifikasi login/password portal sebelum TU boleh mengaktifkan login HTTP:

- endpoint internal read/auth check berbasis app client
- input login identifier, password, app code
- output profile minimal dan app access summary
- tidak mengembalikan password hash
- tidak membuat token URL
- tidak auto-login/SSO
- tidak write-back dari TU

## Security Confirmation

- No secret output.
- No password/hash/token output.
- No write-back.
- No SSO.
- No auto-login.
- No token URL.
- No auth replacement.
- No migration/database change.
- No KP/SAFA changes.

## Decision

GO for portal auth implementation skeleton.

NO-GO for full portal login until Core password verification endpoint exists.

NO-GO for production cutover.

## Recommended Next Step

CORE-TU-CONNECT-8 Design Core Portal Password Verification Endpoint.
