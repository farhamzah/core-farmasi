# CORE-UI-1 Asset Pipeline & Login UI Fix Report

Tanggal pengerjaan: 2026-05-23

## Scope

Tahap CORE-UI-1 fokus hanya pada asset pipeline dan login UI Core.

Yang dikerjakan:

- Memasang dependency frontend Core jika diperlukan.
- Menjalankan Vite production build.
- Membersihkan cache Laravel/Filament.
- Publish/refresh asset Filament.
- Polish ringan login resmi `/admin/login`.
- Verifikasi route, asset loading, auth behavior, dan test.

Yang tidak dikerjakan:

- Tidak membuat dashboard besar.
- Tidak polish resource besar.
- Tidak membuat app shortcut.
- Tidak membuat import Excel.
- Tidak membuat master data tendik/staf/laboran.
- Tidak mengubah flow password tanggal lahir.
- Tidak membuat integrasi API baru.
- Tidak membuat SSO/auto-login.
- Tidak mengubah database.
- Tidak menyentuh SAFA/KP/TU.

## Previous Findings Used

Temuan CORE-UI-0 yang dipakai:

- `public/build/manifest.json` tidak ada.
- `public/build`, `public/css`, `public/js`, dan `public/fonts` belum tersedia.
- `node_modules` tidak ada.
- Login resmi Core adalah `/admin/login`.
- Route `/login` tidak terdaftar.
- Tidak ada custom Filament theme yang menunjuk ke file hilang.
- Dugaan utama login polos adalah asset Filament/Vite belum tersedia atau tidak ter-load.

## Files Inspected

File/folder yang diperiksa:

- `apps/core-farmasi/package.json`
- `apps/core-farmasi/package-lock.json`
- `apps/core-farmasi/composer.json`
- `apps/core-farmasi/vite.config.js`
- `apps/core-farmasi/resources/css/app.css`
- `apps/core-farmasi/resources/js/app.js`
- `apps/core-farmasi/resources/js/bootstrap.js`
- `apps/core-farmasi/resources/views/welcome.blade.php`
- `apps/core-farmasi/app/Providers/Filament/AdminPanelProvider.php`
- `apps/core-farmasi/app/Models/User.php`
- `apps/core-farmasi/public/build`
- `apps/core-farmasi/public/vendor/filament`
- `apps/core-farmasi/public/css`
- `apps/core-farmasi/public/js`
- `apps/core-farmasi/public/fonts`
- `apps/core-farmasi/docs/reports/CORE-UI-0-PLANNING-INSPECTION-REPORT.md`

Catatan:

- `tailwind.config.*` tidak ada.
- `postcss.config.*` tidak ada.
- Ini masih kompatibel dengan setup Tailwind v4 yang dipakai via `@tailwindcss/vite`.
- `public/vendor/filament` tidak ada, tetapi Filament v5 pada project ini mem-publish asset ke `public/css/filament`, `public/js/filament`, dan `public/fonts/filament`.

## Files Changed

File dibuat/diubah:

- `apps/core-farmasi/app/Filament/Pages/Auth/Login.php`
- `apps/core-farmasi/app/Providers/Filament/AdminPanelProvider.php`
- `apps/core-farmasi/package-lock.json`
- `apps/core-farmasi/public/build/manifest.json`
- `apps/core-farmasi/public/build/assets/app-BAyTiNtq.css`
- `apps/core-farmasi/public/build/assets/app-UyRVujZY.js`
- `apps/core-farmasi/public/css/filament/filament/app.css`
- `apps/core-farmasi/public/js/filament/**`
- `apps/core-farmasi/public/fonts/filament/**`
- `apps/core-farmasi/node_modules/**`
- `apps/core-farmasi/docs/reports/CORE-UI-1-ASSET-LOGIN-FIX-REPORT.md`

README tidak diubah karena report tahap ini sudah cukup dan tidak perlu menambah catatan operasional baru.

## Asset Pipeline Fix

Kondisi awal saat CORE-UI-1 pertama dijalankan:

- `node_modules`: tidak ada.
- `package-lock.json`: tidak ada.
- `public/build/manifest.json`: tidak ada.
- `public/vendor/filament`: tidak ada.
- `public/css`, `public/js`, `public/fonts`: tidak ada.

Kondisi setelah perbaikan dan verifikasi ulang:

- `node_modules`: ada.
- `package-lock.json`: ada.
- `public/build/manifest.json`: ada.
- `public/build/assets`: ada.
- `public/css/filament/filament/app.css`: ada.
- `public/js/filament/filament/app.js`: ada.
- `public/fonts/filament/**`: ada.
- `public/vendor/filament`: tidak ada, dan tidak diperlukan pada hasil publish Filament di project ini.

Dependency/frontend:

- `npm.cmd install` dijalankan karena `node_modules` belum ada.
- Result: success.
- 87 packages installed.
- 0 vulnerabilities.
- `package-lock.json` dibuat.

Vite build:

- `npm.cmd run build` dijalankan dan sukses.
- Verifikasi ulang `npm.cmd run build` juga sukses.
- Vite version saat build: `v7.3.3`.
- Output:
  - `public/build/manifest.json`
  - `public/build/assets/app-BAyTiNtq.css`
  - `public/build/assets/app-UyRVujZY.js`

Laravel/Filament asset:

- `php artisan optimize:clear` dijalankan dan sukses.
- `php artisan filament:assets` dijalankan dan sukses.
- Verifikasi ulang kedua command juga sukses.
- Filament mem-publish asset ke:
  - `public/css/filament/filament/app.css`
  - `public/js/filament/**`
  - `public/fonts/filament/**`

Manual HTTP asset check:

- Server lokal sementara dijalankan di `http://127.0.0.1:8012`.
- `/admin/login` mengembalikan HTTP 200.
- Halaman login memuat 2 CSS dan 8 JS utama yang terdeteksi.
- Semua CSS/JS utama yang dicek mengembalikan HTTP 200.
- Tidak ditemukan asset 404 untuk CSS/JS utama pada check ini.

## Login UI Fix

Perubahan login:

- Login resmi tetap `/admin/login`.
- Tidak membuat route `/login`.
- Menambahkan custom page kecil `App\Filament\Pages\Auth\Login` yang extend `Filament\Auth\Pages\Login`.
- Override hanya:
  - `getTitle()`: `Login Core Farmasi UBP`
  - `getHeading()`: `Core Farmasi UBP`
  - `getSubheading()`: `Pusat Identitas & Master Data Fakultas Farmasi`
- `AdminPanelProvider` sekarang memakai `->login(CoreLogin::class)` dan `->brandName('Core Farmasi UBP')`.

Alasan perubahan minimal dan aman:

- Tetap memakai auth page bawaan Filament.
- Tidak mengubah route auth.
- Tidak mengubah form email/password/remember me bawaan.
- Tidak mengubah rate limiting, validation error, session auth, atau panel authorization.
- Tidak menambah asset path custom yang rapuh.
- Tidak membuat layout login custom besar.

Manual check login:

- `/admin/login` status 200.
- HTML berisi `Core Farmasi UBP`.
- HTML berisi `Pusat Identitas & Master Data Fakultas Farmasi`.
- Filament CSS utama tersedia dengan HTTP 200.
- Filament JS utama tersedia dengan HTTP 200.

## Security Confirmation

Konfirmasi security:

- `/admin/login` tetap login resmi.
- `/admin` tetap protected dan guest diarahkan ke `/admin/login`.
- Tidak ada route admin public baru.
- Tidak ada route `/login` baru.
- Tidak ada bypass login.
- Tidak ada SSO atau auto-login.
- Role access existing tidak dilonggarkan.
- `User::canAccessPanel()` tetap mensyaratkan user aktif dengan role aktif `super-admin` atau `admin-core`.
- Inactive user logic tidak dilemahkan.
- Login API dan bearer token tidak diubah.
- Tidak ada password, token, secret, API key, atau credential yang di-hardcode.

## Commands Run

Command yang dijalankan dari `apps/core-farmasi`:

- `npm.cmd install`
  - Result: success.
  - 87 packages installed.
  - 0 vulnerabilities.
  - Dijalankan karena `node_modules` awalnya belum ada.

- `php artisan optimize:clear`
  - Result: success.
  - Config/cache/compiled/events/routes/views/blade-icons/filament cache cleared.
  - Dijalankan ulang pada verifikasi akhir, tetap success.

- `php artisan filament:assets`
  - Result: success.
  - Filament CSS/JS/fonts published.
  - Dijalankan ulang pada verifikasi akhir, tetap success.

- `npm.cmd run build`
  - Result: success.
  - `public/build/manifest.json` generated.
  - `public/build/assets/app-BAyTiNtq.css` generated.
  - `public/build/assets/app-UyRVujZY.js` generated.
  - Dijalankan ulang pada verifikasi akhir, tetap success.

- `php artisan test`
  - Result: success.
  - 24 tests passed, 68 assertions.

- `php artisan route:list --path=login`
  - Result: success.
  - `/admin/login` resolves to `App\Filament\Pages\Auth\Login`.
  - Tidak ada route web `/login`.
  - `POST /api/v1/auth/login` tetap API login.

- Local manual check with `php artisan serve --host=127.0.0.1 --port=8012`
  - Result: success.
  - `/admin/login`: HTTP 200.
  - `/admin`: HTTP 302 to `/admin/login` for guest.
  - Login CSS/JS main assets checked: HTTP 200.
  - Temporary local server was stopped after check.

Catatan:

- Ada satu percobaan manual redirect check dengan `System.Net.Http.HttpClient` yang gagal karena assembly belum dimuat di PowerShell. Setelah `Add-Type -AssemblyName System.Net.Http`, check berhasil dan menunjukkan `/admin` guest response `302` ke `/admin/login`.

## Test Result

`php artisan test` result terakhir:

- Tests: 24 passed.
- Assertions: 68.
- Duration: 54.60s.

Test memakai konfigurasi testing dari `phpunit.xml`, yaitu SQLite in-memory, sehingga tidak mengubah database MySQL Core atau database KP.

## Manual Check

Checklist:

- `/admin/login` styled: OK, Filament CSS/JS loaded with HTTP 200 and login contains Core brand/subtitle.
- `/admin` protected: OK, guest request receives HTTP 302 to `/admin/login`.
- No 500 error: OK, `/admin/login` returns HTTP 200.
- No main CSS/JS asset 404: OK, all detected login CSS/JS assets returned HTTP 200.
- Responsive basic OK: OK by using Filament default auth layout; no custom layout added that would introduce desktop/mobile overflow risk.

Note:

- Visual browser screenshot was not captured because Playwright was not available to the Node REPL in this environment. Manual verification used Laravel local server plus HTTP status/content/asset checks.

## Guardrails Confirmation

Konfirmasi guardrails:

- Tidak menjalankan `migrate:fresh`.
- Tidak menjalankan `migrate:reset`.
- Tidak menjalankan `migrate:rollback`.
- Tidak menjalankan migration baru.
- Tidak drop database.
- Tidak menghapus database.
- Tidak menghapus data existing.
- Tidak mengubah database KP.
- Tidak execute import KP.
- Tidak menyentuh `apps/safa-ubp`.
- Tidak menyentuh `apps/kp-farmasi`.
- Tidak menyentuh `apps/tu-farmasi`.
- Tidak membuat Core tampil di SAFA public portal.
- Tidak membuat SSO atau auto-login.
- Tidak membuat bypass login.
- Tidak membuat app shortcut.
- Tidak membuat import Excel.
- Tidak membuat master data tendik/staf/laboran.
- Tidak mengubah flow password.

## Future Notes

Kebutuhan tahap berikutnya yang dicatat, belum diimplementasikan pada CORE-UI-1:

- Core sebagai Master Data Center untuk identitas dan master data utama seluruh aplikasi.
- Tambahan tipe data/persona: tendik, admin, staf TU, laboran.
- Excel import center dengan preview, validasi, conflict detection, dry-run, dan audit trail sebelum execute.
- Username awal dapat berbasis NIM/NIDN/NIP/nomor identitas sesuai tipe user.
- Password awal dapat dirancang berbasis tanggal lahir format `dd/mm/yyyy`, tetapi harus dibuat dengan flow aman, wajib ganti password, dan tidak diimplementasikan pada tahap ini.
- User perlu bisa update password setelah login.
- App access dan shortcut internal berbasis `UserAppAccess`, tanpa SSO/bypass login.
- Internal API/integration perlu cepat, aman, terdokumentasi, dan mudah dipakai oleh aplikasi lain.

## Risks / Notes

- `node_modules` dan compiled public assets sekarang ada di workspace. Untuk deployment, pastikan policy repo/deploy memutuskan apakah compiled assets dan `package-lock.json` ikut dikomit atau build dilakukan di pipeline.
- Jika login masih tampak polos pada browser tertentu, cek document root web server. Document root harus mengarah ke `apps/core-farmasi/public`.
- Jika memakai server selain `php artisan serve`, pastikan path `/css/filament/**`, `/js/filament/**`, `/fonts/filament/**`, `/build/**`, dan Livewire asset route dapat diakses.
- Jangan lanjut ke SSO/password tanggal lahir/import Excel sebelum ada rancangan security dan audit yang jelas.
- Dashboard/resource polish sebaiknya menjadi tahap terpisah agar perubahan UI tidak bercampur dengan asset pipeline fix.
