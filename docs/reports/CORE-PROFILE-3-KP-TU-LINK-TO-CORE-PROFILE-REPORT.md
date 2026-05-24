# CORE-PROFILE-3 KP/TU Link-to-Core-Profile Report

## Scope
Tahap ini mengintegrasikan link "Ubah Profil di Core" ke KP dan TU secara aman. Integrasi ini hanya browser link biasa ke Core Profile Portal, bukan SSO, bukan auto-login, bukan write-back, dan bukan pengganti auth KP/TU.

## Previous Reports Reviewed
- CORE-PROFILE-2 Editable Safe Contact Fields & Profile Completion Report.
- CORE-INTEGRATION-2B KP Read-Only Adapter Report.
- CORE-INTEGRATION-3 TU Read-Only Adapter Report.
- `docs/CORE-CENTRALIZED-PROFILE-PORTAL-PLAN.md`.
- `docs/CORE-CONSUMER-INTEGRATION-PLAN.md`.

## Files Changed
Core:
- `apps/core-farmasi/docs/CORE-CONSUMER-INTEGRATION-PLAN.md`
- `apps/core-farmasi/docs/reports/CORE-PROFILE-3-KP-TU-LINK-TO-CORE-PROFILE-REPORT.md`

KP:
- `apps/kp-farmasi/config/core_farmasi.php`
- `apps/kp-farmasi/.env.example`
- `apps/kp-farmasi/app/Services/CoreFarmasiClient.php`
- `apps/kp-farmasi/app/Http/Controllers/ProfileController.php`
- `apps/kp-farmasi/resources/views/profile/show.blade.php`
- `apps/kp-farmasi/resources/views/profile/edit.blade.php`
- `apps/kp-farmasi/docs/CORE-HTTP-ADAPTER-READONLY.md`
- `apps/kp-farmasi/tests/Feature/CoreFarmasiClientTest.php`
- `apps/kp-farmasi/tests/Feature/UserImportAndProfileTest.php`
- `apps/kp-farmasi/public/build/*`

TU:
- `apps/tu-farmasi/config/core_farmasi.php`
- `apps/tu-farmasi/.env.example`
- `apps/tu-farmasi/app/Services/CoreFarmasiClient.php`
- `apps/tu-farmasi/resources/views/portal/home.blade.php`
- `apps/tu-farmasi/docs/CORE-HTTP-ADAPTER-READONLY.md`
- `apps/tu-farmasi/tests/Feature/CoreFarmasiClientTest.php`
- `apps/tu-farmasi/tests/Feature/UserPortalSkeletonTest.php`
- `apps/tu-farmasi/package-lock.json`
- `apps/tu-farmasi/public/build/*`

## Core Profile Ownership
- Core owns canonical profile edits.
- KP/TU remain read-only consumers for canonical profile data.
- App-specific data remains local in KP/TU.
- Other apps should show read-only profile data and link to Core Profile Portal for edits.

## KP Changes
- Added `KP_CORE_PROFILE_URL` placeholder.
- Added `core_farmasi.profile_url` config with fallback from `KP_CORE_BASE_URL/profile`.
- Added `CoreFarmasiClient::profileUrl()` and `profileEditUrl()`.
- Profile URL helper strips query string and only accepts `http`/`https`.
- KP profile show/edit pages now show "Profil utama dikelola di Core Farmasi" notice and "Ubah Profil di Core" link when configured.
- Link opens in new tab with `rel="noopener noreferrer"`.
- No token URL.
- Default `KP_CORE_READ_MODE=legacy` remains unchanged.
- Existing KP operational profile flow was preserved; no cutover was performed.

## TU Changes
- Added `TU_CORE_PROFILE_URL` placeholder.
- Added `core_farmasi.profile_url` config with fallback from `TU_CORE_BASE_URL/profile`.
- Added `CoreFarmasiClient::profileUrl()` and `profileEditUrl()`.
- Profile URL helper strips query string and only accepts `http`/`https`.
- TU portal home shows "Ubah Profil di Core" link and ownership notice when configured.
- Link opens in new tab with `rel="noopener noreferrer"`.
- No token URL.
- Default `TU_CORE_READ_MODE=disabled` remains unchanged.
- Existing TU portal forms/workflow remain local and unchanged.

## Security Confirmation
- No SSO.
- No auto-login.
- No token URL.
- No write-back.
- No auth replacement.
- No database changes.
- No profile edit form duplication was introduced.
- No real secret was written.
- No password/hash/token/secret exposure.
- Default KP legacy mode preserved.
- Default TU disabled mode preserved.
- SAFA not touched.

## Commands Run
KP:
- `php artisan optimize:clear` - OK.
- `php artisan test --filter=CoreFarmasiClientTest` - OK, 10 passed / 41 assertions.
- `php artisan test --filter=UserImportAndProfileTest` - OK, 6 passed / 31 assertions.
- `php artisan test` - OK, 129 passed / 613 assertions.
- `npm.cmd run build` - OK.

TU:
- `php artisan optimize:clear` - OK.
- `php artisan test --filter=CoreFarmasiClientTest` - OK, 11 passed / 51 assertions.
- `php artisan test --filter=UserPortalSkeletonTest` - OK, 5 passed / 34 assertions.
- First full `php artisan test` had one transient path assertion failure in `MakeDemoTemplateDocxCommandTest`; targeted rerun passed.
- Final `php artisan test` - OK, 253 passed / 1316 assertions.
- `npm.cmd run build` initially failed because Vite dependency was not installed.
- `npm.cmd install` - OK, installed dependencies.
- `npm.cmd run build` - OK.

Core:
- `php artisan test` - OK, 169 passed / 854 assertions.

Migrations:
- Not run.

## Test Result
- KP: 129 passed / 613 assertions.
- TU: 253 passed / 1316 assertions.
- Core: 169 passed / 854 assertions.

## Guardrails Confirmation
- Tidak menjalankan migration.
- Tidak mengubah database Core/KP/TU.
- Tidak execute import.
- Tidak cutover.
- Tidak mengganti auth KP/TU.
- Tidak membuat SSO/bypass login.
- Tidak membuat auto-login.
- Tidak membuat cross-app session.
- Tidak membuat token URL.
- Tidak menulis real secret.
- Tidak expose password/hash/token/secret.
- Tidak write-back.
- Tidak membuat form edit profil utama baru di KP/TU.
- Default KP legacy tetap.
- Default TU disabled tetap.
- Tidak menyentuh SAFA.
- Tidak membuat Core public.

## Risks / Notes
- Real Core profile URL belum dikonfigurasi di staging/production.
- Karena tidak ada SSO, user mungkin perlu login Core secara terpisah.
- Future SSO membutuhkan desain dan tahap terpisah.
- Existing KP local profile form masih ada untuk data operasional/legacy; policy cutover field utama perlu tahap migrasi tersendiri jika owner ingin mematikannya.
- User education needed: data utama diubah di Core, data operasional tetap di KP/TU.

## Recommended Next Step
Rekomendasi tahap berikutnya:
- CORE-INTEGRATION-4 Staging Smoke SOP.
- Alternatif: CORE-PROFILE-4 Profile Portal UX Polish.
- Alternatif: CORE-INTEGRATION-4B Real Staging Smoke Test Execution if credentials ready.
