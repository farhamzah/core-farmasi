# CORE-MANUAL-QA-2 Profile Portal Browser Test Report

Tanggal: 2026-06-01

## Scope

Manual QA untuk Profile Portal login, first password change gate, profile completion, dan pembatasan akses `/admin` untuk user non-admin.

Tahap ini tidak menambah fitur baru, tidak menjalankan migration, tidak membuat SSO, tidak membuat token URL, tidak membuka `/admin` untuk non-admin, dan tidak commit/push.

## Server

- URL: `http://127.0.0.1:8000`
- PHP server PID: `17396`
- `/profile/login`: HTTP 200
- `/admin/login`: HTTP 200

Catatan tooling: in-app browser runtime gagal setup di lingkungan Codex Desktop. QA flow tetap dijalankan terhadap halaman Laravel nyata memakai HTTP session automation dengan CSRF/cookie session, bukan unit-only assertion. Form admin Filament memakai Livewire, sehingga admin create-button diverifikasi melalui feature test resource admin yang sudah membuka halaman resource terautentikasi.

## Baseline Commands

- `php artisan test --filter=CoreProfilePortalTest`: 24 passed / 128 assertions.
- `php artisan core:manual-qa-accounts --apply --reset-admin-password --create-users --assign-app-access`: OK, QA accounts reset untuk local test.

## Admin QA

- Admin login page `/admin/login`: OK, HTTP 200.
- Admin manual CRUD/create access: OK via `CoreManualCrudResourceTest`.
- Create buttons visible on master resource indexes: OK via `CoreManualCrudResourceTest`.
- Non-admin cannot access manual create pages: OK via `CoreManualCrudResourceTest`.
- Logout admin: not browser-clicked because admin login form is Livewire and in-app browser runtime was unavailable.

## Mahasiswa QA

- Login via `/profile/login`: OK.
- First login redirects to `/profile/change-password`: OK.
- Password change: OK.
- Old temporary password rejected after change: OK.
- After password change redirects to `/profile/edit`: OK.
- Safe profile update phone/address: OK.
- `/profile` shows profile/completion content after update: OK.
- `/admin` blocked for non-admin session: OK.
- Profile logout route responds with redirect: OK.

## Dosen QA

- Login via `/profile/login`: OK.
- First login redirects to `/profile/change-password`: OK.
- Password change: OK.
- Old temporary password rejected after change: OK.
- After password change redirects to `/profile/edit`: OK.
- Safe profile update phone/address: OK.
- `/profile` shows profile/completion content after update: OK.
- `/admin` blocked for non-admin session: OK.
- Profile logout route responds with redirect: OK.

## Tendik QA

- Login via `/profile/login`: OK.
- First login redirects to `/profile/change-password`: OK.
- Password change: OK.
- Old temporary password rejected after change: OK.
- After password change redirects to `/profile/edit`: OK.
- Safe profile update phone/address: OK.
- `/profile` shows profile/completion content after update: OK.
- `/admin` blocked for non-admin session: OK.
- Profile logout route responds with redirect: OK.

## Security Confirmation

- Non-admin `/admin` blocked: OK.
- Password hash not exposed in QA output/report: OK.
- Token/secret not exposed in QA output/report: OK.
- No SSO: OK.
- No token URL: OK.
- No auto-login: OK.
- Official identity fields remain locked by Profile Portal tests: OK.

## Commands Run

- `Get-Process -Id 17396 -ErrorAction SilentlyContinue`
- `curl.exe -s -o NUL -w "%{http_code}" http://127.0.0.1:8000/profile/login`
- `curl.exe -s -o NUL -w "%{http_code}" http://127.0.0.1:8000/admin/login`
- `php artisan test --filter=CoreProfilePortalTest`
- `php artisan core:manual-qa-accounts --apply --reset-admin-password --create-users --assign-app-access`
- HTTP session QA script for mahasiswa, dosen, and tendik Profile Portal flows.
- `php artisan test --filter=CoreManualCrudResourceTest`
- `php artisan test`

## Test Result

- `CoreProfilePortalTest`: 24 passed / 128 assertions.
- `CoreManualCrudResourceTest`: 5 passed / 138 assertions.
- Full regression: 261 passed / 1462 assertions.

## Issues Found

- No Core application issue found in Profile Portal flow.
- Tooling issue: in-app browser runtime failed to start in this Codex environment, so QA used HTTP session automation against the running local server.

## Fixes Applied

- No code fix applied.
- QA accounts were reset before the final QA pass using the existing local QA command.

## Recommended Next Step

- CORE-GIT-5 Commit & Push Profile Portal Login Flow.
