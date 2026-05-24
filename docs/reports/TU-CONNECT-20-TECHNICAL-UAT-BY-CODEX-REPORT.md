# TU-CONNECT-20 Technical UAT by Codex Report

Tanggal: 2026-05-24

## Scope

Tahap ini menjalankan Technical UAT by Codex untuk portal login TU via Core HTTP. Tujuannya adalah memvalidasi kesiapan teknis local/staging sejauh credential runtime tersedia, mengisi result form, dan membuat sign-off teknis.

Tahap ini bukan production cutover, bukan SSO, bukan auto-login, bukan token URL, dan bukan pengganti final UAT manusia oleh staf TU/mahasiswa/dosen.

## Previous Evidence

- Dosen login via Core HTTP sudah terbukti di HTTP-level pada fase sebelumnya.
- Mahasiswa login via Core HTTP sudah terbukti di HTTP-level pada fase sebelumnya.
- Tombol `Login Mahasiswa/Dosen` sudah tampil dan terbukti lewat automated test.
- App access `tu-farmasi` untuk dosen dan mahasiswa sudah terbukti `has-access`.
- Default tetap `TU_PORTAL_AUTH_MODE=disabled`.

## Environment Handling

Initial values recorded without secrets:

- `TU_PORTAL_AUTH_MODE=disabled`
- `TU_CORE_READ_MODE=http-shadow`
- `TU_CORE_HTTP_ENABLED=true`

Temporary UAT mode:

- `TU_PORTAL_AUTH_MODE=core_http`
- `TU_CORE_READ_MODE=http-shadow`
- `TU_CORE_HTTP_ENABLED=true`

Rollback:

- `TU_PORTAL_AUTH_MODE=disabled`
- `php artisan optimize:clear`: OK
- `php artisan tu:portal-login-preflight`: safe default `NOT READY` because mode is disabled

## Technical UAT Result

| Scenario | Result | Notes |
| --- | --- | --- |
| Portal login preflight in `core_http` | PASS | Status `READY`; HTTP shadow, app access, and password endpoint ready. |
| Dosen app access smoke `user_id=3` | PASS | `has-access`. |
| Mahasiswa app access smoke `user_id=4` | PASS | `has-access`. |
| Wrong password generic failure | PASS | `GENERIC_FAILURE_OK`; no detailed sensitive output. |
| Dosen successful login | SKIPPED | Password not available to Codex runtime. Previous HTTP-level proof exists. |
| Mahasiswa successful login | SKIPPED | Password not available to Codex runtime. Previous HTTP-level proof exists. |
| Logout/session clear | PASS | Covered by automated tests. |
| Portal access policy | PASS | Covered by automated ownership/redirect tests. |
| Admin auth separation | PASS | Admin route/auth remains separate and unchanged. |
| Security checks | PASS | No password/hash/token/secret output or report content. |
| Rollback to disabled | PASS | Completed. |

## Blocker

`TU-CONNECT-20-BLOCKER-01`: fresh successful login execution by Codex could not be performed because test account passwords were not available to Codex runtime via environment variables or another safe runtime channel.

No password was requested to be written to a file, and no password was printed.

## Sign-off

Technical sign-off: PASS with limitations.

Cutover sign-off: NO.

End-user/staff sign-off: pending.

## Security Confirmation

- No password in report.
- No password/hash/token/secret output.
- No SSO.
- No auto-login.
- No token URL.
- No write-back to Core.
- No admin auth replacement.
- No production cutover.
- Default restored to disabled.

## Commands Run

From `apps/tu-farmasi`:

```bash
php artisan optimize:clear
php artisan tu:portal-login-preflight
php artisan tu:core-smoke-test --user-id=3
php artisan tu:core-smoke-test --user-id=4
php artisan tinker --execute="..."
php artisan test
composer validate
php artisan route:list
```

The tinker command called `CoreFarmasiClient::verifyPortalPassword` with intentionally invalid credentials and only printed `GENERIC_FAILURE_OK`.

## Recommended Next Step

If a safe password runtime channel is provided:

- Repeat successful login execution for dosen and mahasiswa, then process final UAT result.

If proceeding with current evidence:

- Use this only as technical UAT evidence with limitations.
- Do not cut over production until owner/TU sign-off is available.

