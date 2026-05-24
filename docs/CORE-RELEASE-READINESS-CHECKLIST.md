# Core Farmasi Release Readiness Checklist

Dokumen ini adalah checklist operasional sebelum Core Farmasi UBP dipakai untuk integrasi lokal/staging dan sebelum dipertimbangkan untuk production.

## Environment Prerequisites
- PHP, Composer, Node runtime, database, storage, queue/scheduler, dan web server sesuai standar Laravel tersedia.
- `.env` memakai credential environment setempat, bukan credential contoh di dokumentasi.
- `APP_ENV`, `APP_DEBUG`, `APP_URL`, database connection, mail/log settings, dan filesystem private storage diverifikasi.
- `APP_DEBUG=false` untuk production.
- Storage private untuk import tersedia dan writable.
- Backup database dan storage private tersedia sebelum import execute atau rollback besar.

## Database Readiness
- Jalankan `php artisan migrate:status` untuk memeriksa migration status sebelum deployment.
- Jalankan `php artisan migrate` hanya dari release process yang disetujui.
- Jangan menjalankan `migrate:fresh`, `migrate:reset`, atau `migrate:rollback` pada data operasional.
- Pastikan tabel utama tersedia: users, roles, students, lecturers, employees, departments, study_programs, leadership_assignments, core_applications, core_application_roles, user_app_accesses, core_import_batches, core_import_records, core_api_clients, core_api_request_logs.

## Seed Safety
- Seeder app registry/app role catalog memakai pola idempotent.
- Jangan menjalankan seeder yang destruktif pada production.
- Pastikan `core-farmasi` tetap `is_public_visible=false`.
- Pastikan role global dan role aplikasi tetap terpisah.

## Admin Login Check
- Login resmi tetap `/admin/login`.
- `/admin` harus menolak guest dan user tanpa role Core admin.
- Inactive user harus ditolak oleh `canAccessPanel`.
- User dengan `must_change_password=true` harus diarahkan ke change password flow.
- Tidak ada SSO, auto-login, atau bypass login.

## Security Checks
- Tidak ada route public untuk import execute, rollback, API client secret, atau data sensitif.
- Tidak ada token/secret lewat URL.
- Tidak ada password plaintext, password hash, API token hash, API client secret, atau secret hash di response, log, report, atau export.
- API default tidak mengekspos `birth_date`.
- API client secret hanya ditampilkan sekali saat create/rotate dan disimpan sebagai hash.
- API request log tidak menyimpan body penuh, full headers, authorization header, atau secret.
- Role access admin tidak dilonggarkan.

## Import Checks
- Template download berjalan.
- Upload import tetap private/local dan tidak memakai public disk.
- Heading validation berjalan.
- Row validation/conflict detection berjalan untuk supported types.
- Admin decision wajib sebelum execute.
- Invalid/skip/pending rows tidak dieksekusi.
- Students/lecturers/employees execute dan rollback sudah tersedia.
- Users/global role assignments/user app accesses execute dan rollback sudah tersedia.
- Departments/study programs/leadership import execute belum tersedia.
- Password awal user import hanya dari `birth_date`, selalu hashed, dan `must_change_password=true`.
- Tidak ada fallback password lemah.

## Rollback Checks
- Rollback hanya untuk batch import Core.
- Create rollback memakai soft delete/manual review saat tidak aman.
- Update rollback membutuhkan `previous_snapshot`.
- User tidak dihapus jika bukan dibuat batch atau sudah dipakai data lain.
- Rollback tidak menyentuh KP/TU/SAFA.

## API Client Checks
- Internal app-to-app endpoint memakai header:
  - `X-Core-Client-Id`
  - `X-Core-Client-Secret`
  - `X-Core-App-Code`
- Client harus active, belum revoked, app_code cocok, dan punya ability yang dibutuhkan.
- Rotate/revoke client tersedia dari admin.
- Rate limit per client/app code aktif.
- Request log bisa dilihat read-only di admin.
- Tidak ada SSO, auto-login, cross-app session, atau token URL.

## API Pruning SOP
- Jalankan dry-run dulu:

```bash
php artisan core:prune-api-request-logs --dry-run
```

- Jalankan force hanya setelah summary diperiksa:

```bash
php artisan core:prune-api-request-logs --force
```

- Default retention: 90 hari untuk success logs dan 180 hari untuk failed logs.
- Pruning hanya menghapus `core_api_request_logs` lama berdasarkan cutoff.
- Pruning tidak menghapus data master, users, roles, app access, atau API clients.

## Backup Recommendation
SOP lengkap:
- `docs/CORE-BACKUP-RESTORE-SOP.md`
- `docs/CORE-PRE-STAGING-CHECKLIST.md`

- Backup database sebelum migration production.
- Backup database dan storage private sebelum import execute besar.
- Backup database sebelum rollback batch besar.
- Backup Core/KP/TU staging sebelum real staging smoke test bila environment perlu bukti no-mutation.
- Restore harus diuji ke disposable database, bukan langsung database aktif.
- Database dump tidak boleh disimpan di public web directory atau repository.
- Simpan hasil dry-run pruning sebagai catatan operasional sebelum prune force di production.

## Secret Management Readiness
SOP lengkap:
- `docs/CORE-SECRET-MANAGEMENT-READINESS.md`
- `docs/CORE-APP-CLIENT-CREDENTIAL-SOP.md`

Checklist:
- Secret harus tinggal di server env, secret manager, atau password manager yang disetujui.
- Secret tidak boleh ditulis di `.env.example`, README, docs, reports, screenshots, chat, URL, atau logs.
- Staging secret dan production secret harus berbeda.
- App client secret hanya ditampilkan sekali saat create/rotate.
- Rotation, revocation, dan emergency leak response harus dipahami sebelum real smoke test.

## No SSO / No Public Core Note
- Core tetap aplikasi internal admin.
- Core tidak tampil di SAFA public portal.
- App Launcher hanya navigasi internal, bukan SSO dan bukan auto-login.
- Consumer app tetap wajib punya guard/session lokalnya sendiri.

## Next Integration Checklist
- Buat app client untuk consumer app pertama.
- Berikan ability minimal yang dibutuhkan, misalnya `read:app-access` atau `read:leadership`.
- Simpan secret di secret manager/environment consumer app, bukan di repo.
- Uji endpoint internal di staging dengan app client header.
- Validasi 401/403/429 behavior.
- Validasi response tidak mengandung password/hash/token/secret/birth_date default.
- Pantau `core_api_request_logs` dan Data Quality Dashboard setelah integrasi.
- Dokumentasikan SOP rotate/revoke client untuk consumer app tersebut.
