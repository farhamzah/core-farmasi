# Core KP/TU Staging Smoke Execution Plan

## A. Purpose
Dokumen ini menjadi rencana eksekusi real staging smoke test untuk adapter read-only KP/TU terhadap Core API dan link Core Profile Portal.

Rencana ini tidak menjalankan smoke test dan tidak menyimpan credential. Eksekusi real hanya boleh dilakukan setelah app client staging dan secret manager siap.

## B. Scope
- Read-only Core API calls.
- Verifikasi link Core Profile Portal `/profile`.
- Staging only.
- No production cutover.
- No auth replacement.
- No SSO.
- No auto-login.
- No cross-app session.
- No token URL.
- No write-back.
- No database writes from smoke test.

## C. Preconditions
- Core staging running.
- KP staging running.
- TU staging running.
- Core `/profile` accessible through staging URL.
- Core app clients for `kp-farmasi` and `tu-farmasi` created.
- Client secrets stored in approved secret manager/environment, not repo.
- Core TU readiness package reviewed: `docs/CORE-TU-CONNECTION-PACKAGE.md`.
- Core readiness command for TU run before TU env activation:
  ```bash
  php artisan core:tu-connection-readiness
  ```
- `KP_CORE_PROFILE_URL` and `TU_CORE_PROFILE_URL` configured securely.
- Consumer config cache cleared after env changes.
- Backups/snapshots available if required by staging SOP.
- Core API request logs accessible to admin.
- KP/TU logs accessible for secret-leak review.

## D. KP Smoke Steps
Set env on KP staging only:

```env
KP_CORE_HTTP_ENABLED=true
KP_CORE_READ_MODE=legacy
KP_CORE_BASE_URL=https://core-staging.example.test
KP_CORE_PROFILE_URL=https://core-staging.example.test/profile
KP_CORE_APP_CODE=kp-farmasi
KP_CORE_CLIENT_ID=<staging-client-id>
KP_CORE_CLIENT_SECRET=<staging-client-secret>
KP_CORE_TIMEOUT=5
KP_CORE_CONNECT_TIMEOUT=3
KP_CORE_VERIFY_SSL=true
KP_CORE_FAIL_SILENTLY=true
```

Run:

```bash
php artisan config:clear
php artisan kp:core-smoke-test --user-id=<core-user-id>
```

Verify:
- Core health reachable.
- Users directory works if command/manual call includes it.
- Students directory works.
- Lecturers directory works.
- Study programs directory works.
- App access check works for a known KP user.
- Current leadership works or returns safe null if no active data.
- Core profile link opens `/profile` without token/secret.
- Core API request logs record safe metadata.
- KP logs do not contain secret/token/password/hash.
- KP DB remains unchanged.
- KP local auth remains unchanged.

## E. TU Smoke Steps
Set env on TU staging only:

```env
TU_CORE_HTTP_ENABLED=true
TU_CORE_READ_MODE=disabled
TU_CORE_BASE_URL=https://core-staging.example.test
TU_CORE_PROFILE_URL=https://core-staging.example.test/profile
TU_CORE_APP_CODE=tu-farmasi
TU_CORE_CLIENT_ID=<staging-client-id>
TU_CORE_CLIENT_SECRET=<staging-client-secret>
TU_CORE_TIMEOUT=5
TU_CORE_CONNECT_TIMEOUT=3
TU_CORE_VERIFY_SSL=true
TU_CORE_FAIL_SILENTLY=true
```

Run:

```bash
php artisan config:clear
php artisan tu:core-smoke-test --query=<safe-sample-query>
```

Verify:
- Core health reachable.
- Users directory works if command/manual call includes it.
- Students directory works.
- Lecturers directory works.
- Employees directory works.
- Departments directory works.
- Study programs directory works.
- `searchPeople` returns safe normalized data or empty result.
- Current leadership works or returns safe null if no active data.
- App access check works for a known TU user.
- Core profile link opens `/profile` without token/secret.
- Core API request logs record safe metadata.
- TU logs do not contain secret/token/password/hash.
- TU DB remains unchanged.
- TU local auth remains unchanged.

## F. Profile Link Checks
- KP displays "Ubah Profil di Core" only when profile URL is configured.
- TU displays "Ubah Profil di Core" only when profile URL is configured.
- Link target is Core `/profile` or `/profile/edit` according to app helper.
- Link contains no token, no client secret, no password, and no cross-app session data.
- Link should not need user id.
- If user is not logged into Core, Core asks user to login through Core flow.
- No SSO is expected.
- Profile edit occurs only inside Core Profile Portal.

## G. Profile Duplication Checks
- KP/TU should not introduce new main profile edit forms.
- Existing KP/TU local forms are documented as legacy/operational until future cutover.
- App-specific operational fields remain local.
- Canonical fields such as official identity, NIM/NIDN/NIP/employee number, prodi, department, status, role, app access, and leadership remain Core-owned/admin-controlled.
- No cutover of profile fields is part of this smoke test.
- Future cutover requires a separate stage and owner approval.

## H. Expected Results
- All read-only calls pass or return safe empty/null response where no data exists.
- Core API request logs show request metadata without secret/body/full headers.
- No secret appears in KP/TU logs, screenshots, command output, or report.
- No Core/KP/TU database mutation is caused by smoke commands.
- KP/TU remain functional with their existing auth/flow.
- Profile links work safely and lead to Core login/profile flow.
- No SSO, auto-login, or token URL behavior appears.

## I. Negative Tests
Run only in a controlled staging window:
- Invalid secret returns `401`.
- Missing ability returns `403`.
- Disabled/revoked client returns forbidden/unauthorized.
- Rate limit behavior is acceptable and returns safe `429` when intentionally exceeded.
- Core unavailable behavior is fail-safe if tested.
- Empty profile URL hides/disables profile link safely.

Do not publish invalid/real credential values in evidence.

## J. Go / No-Go
Go if:
- All read-only endpoints needed by KP/TU pass.
- Profile links are safe.
- No secret leak is found.
- KP/TU do not break if Core is unavailable.
- No unexpected write is found.
- Core API logs look safe.
- 401/403 negative tests behave as expected.

No-Go if:
- Secret appears in any log/report/output.
- Auth flow breaks in KP/TU.
- Consumer app crashes when Core is unavailable.
- Any unexpected database write occurs.
- Profile URL contains token/secret/user session data.
- Repeated 401/403 occurs with intended valid credential due to missing ability or app_code mismatch.

## K. Rollback / Disable
Disable KP:

```env
KP_CORE_HTTP_ENABLED=false
KP_CORE_READ_MODE=legacy
KP_CORE_PROFILE_URL=
```

Disable TU:

```env
TU_CORE_HTTP_ENABLED=false
TU_CORE_READ_MODE=disabled
TU_CORE_PROFILE_URL=
```

Then clear cache:

```bash
php artisan optimize:clear
```

If credential leak is suspected, revoke the app client in Core. No DB rollback is needed because smoke test is read-only.

## L. Evidence To Collect
Collect only non-sensitive evidence:
- Date/time and environment.
- Command result summary without secrets.
- Core/KP/TU app versions or commit identifiers if available.
- Core API request log counts or screenshots with no secret.
- Profile link screenshots showing no token/secret.
- Negative test status codes without credential values.
- KP/TU DB unchanged confirmation.
- Go/No-Go decision and approver.
