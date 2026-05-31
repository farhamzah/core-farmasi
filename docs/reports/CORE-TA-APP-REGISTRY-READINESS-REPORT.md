# Core TA App Registry Readiness Report

## Ringkasan

Core menyiapkan registry aplikasi `ta-farmasi` dan role catalog TA Farmasi UBP sebagai preparation/readiness. Perubahan ini tidak membuat SSO, tidak membuat token URL, dan tidak memberi akses otomatis massal ke user.

## App Registry

- App code: `ta-farmasi`
- Name: `TA Farmasi UBP`
- Local base URL: `http://127.0.0.1:8007`
- Local admin URL: `http://127.0.0.1:8007/admin`
- Status: active
- Public visible: false
- Requires login: true

## Role Catalog

Role aplikasi TA yang disiapkan:

- `mahasiswa`
- `dosen`
- `dosen-pembimbing`
- `penguji`
- `koordinator-ta`
- `admin-ta`
- `kaprodi`
- `dekan`
- `validator`

Role catalog hanya untuk app access/capability awal. Jabatan resmi seperti Dekan/Kaprodi tetap dibaca dari leadership assignment Core saat dibutuhkan.

## File Dibuat / Diubah

Dibuat:

- `app/Console/Commands/TaAppReadinessCommand.php`
- `tests/Feature/TaAppRegistryReadinessTest.php`
- `docs/reports/CORE-TA-APP-REGISTRY-READINESS-REPORT.md`

Diubah:

- `database/seeders/CoreApplicationSeeder.php`
- `.env.example`

## Readiness Command

Command:

```bash
php artisan core:ta-app-readiness
```

Command bersifat read-only dan mengecek:

- app `ta-farmasi` terdaftar;
- duplicate app code;
- status aktif;
- role wajib lengkap;
- active user app access count;
- URL tidak mengandung token/autologin/secret indicator.

Hasil lokal TA-26:

- Verdict: READY.
- App registered: yes.
- Duplicate app code: 0.
- Active: yes.
- Public visible: no.
- Requires login: yes.
- Required roles missing: none.
- Unsafe token/autologin URL: no.
- Active user app access count: 1 data lokal existing, bukan mass grant dari seeder registry TA.

Seeder lokal yang dijalankan:

```bash
php artisan db:seed --class=CoreApplicationSeeder
```

## Tests

Test:

- `tests/Feature/TaAppRegistryReadinessTest.php`

Hasil:

- `php artisan test --filter=TaAppRegistryReadinessTest`: 2 passed, 20 assertions.
- `php artisan test`: 219 passed, 1113 assertions.

## Guardrails

- Tidak membuat SSO.
- Tidak membuat auto-login.
- Tidak membuat token URL.
- Tidak membuat credential produksi.
- Tidak write-back dari TA ke Core.
- Tidak auto grant app access dari registry seeder.
- Tidak expose password/hash/token/secret.
