# Core Backup & Restore SOP

## Purpose
SOP ini mengatur backup dan restore readiness sebelum real staging smoke test, import execute besar, production deployment, atau cutover terbatas.

Tahap ini tidak menjalankan backup/restore nyata. Restore operasional harus dilakukan ke disposable database terlebih dahulu, bukan langsung ke database aktif.

## Databases To Back Up
Minimal backup sebelum integrasi staging/production:
- `core_farmasi_ubp`: Core identity, master data, profile, app access, import, API clients/logs.
- KP database: hanya jika smoke/cutover nanti menyentuh flow KP atau perlu bukti no-mutation.
- TU database: hanya jika smoke/cutover nanti menyentuh flow TU atau perlu bukti no-mutation.

Jika environment memakai nama database berbeda, gunakan nama actual environment dan catat tanpa menulis password.

## Storage Backup Yang Aman
- Simpan backup di storage internal yang terbatas aksesnya.
- Gunakan encrypted backup storage jika tersedia.
- Pastikan backup tidak berada di public web directory.
- Jangan commit dump ke repository.
- Jangan upload dump ke chat, issue tracker, atau dokumentasi.
- Jangan menyimpan backup di `public/`, `storage/app/public`, atau folder build assets.
- Batasi akses backup pada admin/devops yang berwenang.

## Naming Convention
Gunakan nama yang jelas tanpa credential:

```text
{env}-{app-or-db}-{YYYYMMDD-HHmmss}-{purpose}.sql.gz
```

Contoh aman:

```text
staging-core_farmasi_ubp-20260524-210000-pre-smoke.sql.gz
staging-kp_farmasi-20260524-210500-pre-smoke.sql.gz
staging-tu_farmasi-20260524-211000-pre-smoke.sql.gz
```

Jangan memasukkan password, host internal sensitif, atau client secret ke filename.

## Backup Before
Backup harus tersedia sebelum:
- issuing staging credentials jika environment staging belum punya snapshot terbaru.
- real staging smoke test KP/TU.
- import execute besar.
- rollback batch import besar.
- production deployment.
- app client rotation besar bila risiko operasional tinggi.
- profile field cutover KP/TU.

## Backup Procedure Checklist
1. Identifikasi database target dan environment.
2. Pastikan maintenance window atau low-traffic window jika diperlukan.
3. Buat dump dari database target menggunakan tooling DB approved.
4. Simpan ke secure backup storage.
5. Hitung checksum backup.
6. Catat metadata backup: environment, database, waktu, ukuran, checksum, operator.
7. Jangan catat password/secret.
8. Pastikan file backup tidak berada di public path.
9. Pastikan backup tidak masuk git.

## Restore Procedure To Disposable Database
Restore validasi harus memakai disposable database:
1. Buat database disposable, misalnya `restore_check_core_YYYYMMDD`.
2. Restore dump ke disposable database.
3. Jangan restore langsung ke production/staging aktif.
4. Gunakan user DB dengan hak minimum yang cukup untuk restore disposable.
5. Setelah verifikasi selesai, dispose database sesuai SOP internal.
6. Catat hasil restore tanpa dump content dan tanpa credential.

## Restore Verification Checklist
Setelah restore ke disposable database:
- Database dapat dibaca.
- Table count sesuai ekspektasi.
- Tabel inti tersedia:
  - users
  - roles
  - students
  - lecturers
  - employees
  - departments
  - study_programs
  - core_applications
  - core_application_roles
  - user_app_accesses
  - core_api_clients
  - core_api_request_logs
- Sample row count masuk akal untuk environment tersebut.
- Migration status dapat dicek terhadap disposable env bila aplikasi diarahkan sementara secara aman.
- Admin login tidak diuji dengan membocorkan password.
- Route/test sanity boleh dilakukan pada disposable/test config, bukan production aktif.
- Tidak ada backup dump di public directory.

## Rollback Decision
Rollback/restore ke database aktif hanya boleh dipertimbangkan jika:
- root cause jelas,
- backup sudah diverifikasi pada disposable database,
- owner/admin menyetujui,
- maintenance window tersedia,
- dampak data setelah timestamp backup dipahami,
- rencana komunikasi user tersedia.

Jika problem bisa diselesaikan dengan disable feature flag, revoke app client, atau rollback konfigurasi, pilih cara non-destructive terlebih dahulu.

## Do Not Do
- Jangan restore dump langsung ke database aktif tanpa disposable restore test.
- Jangan menjalankan `migrate:fresh`, `migrate:reset`, atau `migrate:rollback` pada data operasional.
- Jangan drop database aktif.
- Jangan menyimpan dump di repository.
- Jangan menyimpan dump di public web directory.
- Jangan memasukkan secret/password ke nama file, log, atau report.
- Jangan memakai backup yang belum diverifikasi untuk keputusan production.
