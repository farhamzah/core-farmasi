# Core Farmasi UBP

Core Farmasi UBP adalah aplikasi internal untuk pusat identity, master data, role/access aplikasi, import Excel, audit, data quality, leadership assignment, dan API internal di workspace Farmasi UBP.

Core bukan aplikasi publik dan tidak ditampilkan di SAFA public portal. Login resmi admin tetap `/admin/login`.

## Status Modul
- Identity center: users, username, identity type/number, must-change-password, change password flow.
- Profile portal: `/profile` untuk user authenticated melihat profil sendiri, mengubah phone/address aman, dan melihat ringkasan kelengkapan profil.
- Master data: students, lecturers, employees/tendik/staff, departments, study programs, roles.
- Organization: leadership assignments untuk Dekan, Kaprodi, Wakil Dekan, Sekretaris Prodi, Kepala Lab, dan jabatan lain.
- App access: dynamic app registry, app role catalog, user app access.
- Import center: template download, private upload, heading validation, row validation/conflict detection, admin decision UI, execute import students/lecturers/employees/users/global role assignments/user app accesses, rollback safety.
- Data quality: dashboard read-only untuk quality metrics identity, profile, app access, leadership, dan import.
- Launcher: internal app launcher tanpa SSO dan tanpa auto-login.
- Internal API: safe response, app access check, current leadership endpoint, app-client directory/profile endpoints, employee safe endpoint, app client credentials, rotate/revoke, per-client rate limit, request audit logs, and log pruning.

## Security Baseline
- Tidak ada SSO.
- Tidak ada auto-login.
- Tidak ada cross-app user session.
- Tidak ada token/secret lewat URL.
- Password selalu hashed.
- Initial password berbasis birth date hanya dipakai sebagai temporary password dan tidak ditampilkan ulang.
- API default tidak mengekspos password, hash, token, secret, atau `birth_date`.
- API client secret disimpan sebagai hash dan hanya ditampilkan sekali saat create/rotate.
- API internal app-client memiliki audit log aman, rate limit per client/app code, dan pruning command dengan dry-run/force safety.
- Profile Portal tidak membuka `/admin` untuk non-admin dan tidak mengizinkan edit role, app access, status, jabatan, atau identitas resmi.
- Import file disimpan private/local, bukan public disk.
- Import execute hanya berjalan setelah validation dan admin decision.
- Rollback import memakai metadata batch/record dan soft delete/manual review saat tidak aman.

## Internal API
Dokumentasi API internal ada di:

- `docs/CORE-INTERNAL-API.md`
- `docs/CORE-CONSUMER-INTEGRATION-PLAN.md`
- `docs/CORE-APP-CLIENT-CREDENTIAL-SOP.md`
- `docs/CORE-KP-TU-STAGING-SMOKE-EXECUTION-PLAN.md`
- `docs/CORE-TU-CONNECTION-PACKAGE.md`
- `docs/CORE-PROFILE-CUTOVER-NOTES.md`

App-to-app internal endpoint memakai app client headers:

- `X-Core-Client-Id`
- `X-Core-Client-Secret`
- `X-Core-App-Code`

Credential tidak boleh ditulis di README, report, log, URL, atau file export.

API request log pruning:

- `php artisan core:prune-api-request-logs --dry-run`
- `php artisan core:prune-api-request-logs --force`

Pruning hanya menghapus log API lama berdasarkan cutoff retensi. Data master, API clients, users, roles, dan app access tidak ikut dihapus.

## Import Flow
1. Download template resmi.
2. Upload file ke storage private.
3. Validate heading.
4. Validate row dan detect conflict.
5. Admin memilih decision per row.
6. Execute import untuk row approved.
7. Rollback batch jika dibutuhkan dan metadata memungkinkan.

Import execute leadership assignments, departments, dan study programs masih menjadi pekerjaan lanjutan. Users, user role assignments, dan user app accesses sudah mendukung validation, execute, dan rollback safety.

## Non-Goals Saat Ini
- SSO atau auto-login.
- Public Core portal.
- Public sensitive API.
- Cross-app session/token bridge.
- Form edit profil utama di KP/TU/SAFA.
- Import KP/TU/SAFA langsung dari tahap Core ini.
- Auto-fix data quality.

## Recommended Next Steps
- CORE-INTEGRATION-4B Real Staging Smoke Test Execution setelah app client staging dan secret manager siap.
- CORE-TU-CONNECT-1 readiness command dapat dipakai sebelum TU staging env diaktifkan.
- CORE-API-5 API Usage Dashboard jika traffic API mulai aktif.
- CORE-IMPORT-9 Departments / Study Programs / Leadership Import jika import organisasi lanjutan dibutuhkan.
