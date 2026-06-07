# Core Account Lifecycle Plan

Tanggal: 2026-06-01

## Tujuan

Dokumen ini merancang lifecycle akun Core Farmasi UBP untuk self-registration/account request, profile completion, centralized password, approval app access, dan pola login aplikasi consumer memakai password Core.

Tahap ini hanya planning dan guardrail implementasi. Tidak ada registrasi publik aktif, tidak ada SSO, tidak ada auto-login, tidak ada token URL, dan tidak ada app access otomatis.

## Baseline Saat Ini

Core sudah memiliki:

- `users` sebagai akun canonical.
- Password hashed di Core.
- `must_change_password`, timestamp password, dan reset metadata.
- Admin panel `/admin` hanya untuk user aktif dengan role global `super-admin` atau `admin-core`.
- Profile Portal punya login terpisah `/profile/login` untuk user biasa, lalu `/profile` untuk authenticated user melihat profil sendiri, mengubah safe contact fields, dan mengganti password Core.
- Guest yang membuka `/profile`, `/profile/edit`, atau `/profile/change-password` diarahkan ke `/profile/login`, bukan ke login admin.
- Change password page Filament `/admin/change-password` untuk user yang bisa masuk panel.
- User, student, lecturer, employee, role, app registry, app role, dan user app access CRUD.
- Excel Import Center untuk master data.
- Internal API dan app-client credential.
- TU portal password verification endpoint yang memverifikasi password Core dan app access tanpa membuat token/session.

Gap saat ini:

- Route account request skeleton sudah tersedia pada CORE-ACCOUNT-2, tetapi dinonaktifkan untuk publik pada CORE-ACCOUNT-2B:
  - `GET /account-request`
  - `POST /account-request`
  - `GET /account-request/success`
  - `/register` redirect ke `/account-request`
- Tabel pending request `account_requests` sudah tersedia pada CORE-ACCOUNT-2.
- Public account request dikontrol oleh `CORE_PUBLIC_ACCOUNT_REQUEST_ENABLED` dan default-nya `false`.
- Provisioning akun aktif dilakukan oleh Admin Core melalui CRUD/import, bukan oleh self-registration.
- Change password self-service tersedia di Profile Portal pada CORE-PROFILE-4:
  - `GET /profile/change-password`
  - `PUT /profile/change-password`
  - `/profil-saya/ganti-password` redirect ke `/profile/change-password`
- Generic cross-app password verification endpoint untuk semua app belum dibuat; pola yang sudah ada baru spesifik TU.

## Account Sources

### 1. Imported / Admin-Created

Sumber:

- Admin manual CRUD di Core.
- Excel Import Center.
- Import migrasi yang sudah disetujui.

Aturan:

- User dibuat admin/import harus `active` sesuai keputusan admin.
- User baru dari import/admin sebaiknya `must_change_password=true`.
- Password awal tidak boleh ditulis plaintext di report, log, template, atau chat.
- Initial password berbasis strategi yang disetujui harus langsung di-hash.
- App access tetap ditambahkan terpisah lewat `UserAppAccessResource` atau import app access yang eksplisit.

### 2. Self-Registered Pending

Status target untuk tahap depan bila kebijakan kampus membuka kembali registrasi publik:

- User mengisi account request dari route seperti `/account-request` atau `/register`.
- Request tidak langsung memberi akses aplikasi.
- Request masuk status `pending`.
- Password disimpan hashed jika request langsung membuat user pending, atau disimpan sebagai secure pending credential hanya jika desain storage aman sudah dibuat.
- Email/identifier perlu dicek duplikat.
- Admin Core meninjau dan memutuskan approve/reject.

Catatan desain:

- Rekomendasi aman adalah membuat tabel terpisah `account_requests` untuk pending request, bukan langsung mengaktifkan `users`.
- Jika nanti memilih langsung membuat `users`, status harus inactive/pending dan tidak boleh punya app access sampai disetujui.
- Pada CORE-ACCOUNT-2B, jalur ini nonaktif secara default. Halaman publik hanya menampilkan arahan bahwa akun dibuat oleh Admin Core.

### 3. Verified / Active

Kondisi:

- Identitas sudah diverifikasi admin.
- Linked profile student/lecturer/employee sudah cocok atau dibuat admin.
- User aktif.
- Role global minimal diberikan sesuai kebutuhan, bukan otomatis.
- User app access diberikan eksplisit sesuai aplikasi.

### 4. Rejected

Kondisi:

- Account request tidak valid atau duplikat.
- Tidak membuat user aktif.
- Tidak memberi app access.
- Alasan reject boleh disimpan internal, tanpa data sensitif.

### 5. Inactive

Kondisi:

- User tidak boleh login ke Core atau consumer yang memakai verifikasi Core.
- User app access sebaiknya dinonaktifkan.
- Data master tetap tersimpan untuk audit/historis.

## Registration Types

### Mahasiswa

Required fields:

- name.
- email.
- student_number / NIM.
- study_program candidate.
- phone.
- address.
- birth_date hanya jika diperlukan untuk verifikasi/password policy dan harus diperlakukan sensitif.

Approval checks:

- NIM unik.
- Email unik.
- Study program cocok dengan data Core.
- Jika NIM sudah ada, request harus di-link ke profile existing atau ditolak.

Default access:

- Tidak ada app access otomatis.
- Admin dapat memberi app access seperti `kp-farmasi:mahasiswa`, `tu-farmasi:mahasiswa`, atau app lain sesuai kebutuhan.

### Dosen

Required fields:

- name.
- email.
- lecturer_number / NIDN/NIP.
- department candidate.
- study_program candidate bila relevan.
- phone.
- address.

Approval checks:

- Lecturer number unik.
- Email unik.
- Department/study program cocok dengan Core.
- Jika dosen sudah ada, request harus di-link ke profile existing atau ditolak.

Default access:

- Tidak ada app access otomatis.
- Admin memberi role app seperti `kp-farmasi:pembimbing-dalam`, `ta-farmasi:dosen-pembimbing`, `lab-farmasi:dosen`, sesuai keputusan.

### Tendik / Staf / Laboran

Required fields:

- name.
- email.
- employee_number bila ada.
- staff_type.
- department candidate.
- study_program candidate bila relevan.
- position_title bila ada.
- phone.
- address.

Approval checks:

- Employee number unik bila diisi.
- Email unik.
- Staff type valid.
- Unit kerja cocok dengan Core.

Default access:

- Tidak ada app access otomatis.
- Admin memberi app access seperti `tu-farmasi:staf-tu`, `lab-farmasi:laboran`, `helpdesk-farmasi:agent`, sesuai kebutuhan.

## Approval Flow

1. User submit account request.
2. Core menyimpan request sebagai pending.
3. Sistem melakukan duplicate check:
   - email.
   - NIM.
   - NIDN/NIP / lecturer number.
   - employee number.
4. Admin Core membuka queue account request.
5. Admin memilih:
   - approve and link existing profile.
   - approve and create profile.
   - request correction.
   - reject.
6. Jika approved:
   - user menjadi active.
   - role global minimal diberikan jika diperlukan.
   - `must_change_password` diset sesuai policy.
   - app access diberikan manual/terpisah.
7. Semua keputusan diaudit tanpa menyimpan password/token/secret.

## CORE-ACCOUNT-2 Account Request Skeleton

CORE-ACCOUNT-2 menambahkan skeleton aman untuk account request pending approval:

- Public form `/account-request`.
- Alias `/register` redirect ke `/account-request`.
- Submit request membuat row `account_requests` dengan status `pending`.
- Submit request tidak membuat user aktif.
- Submit request tidak membuat session login.
- Submit request tidak membuat role atau app access.
- Form request tidak memiliki password field.
- Admin Core meninjau melalui `AccountRequestResource`.
- Admin action tersedia untuk:
  - mark in review.
  - reject.
  - approve skeleton.
- Approve skeleton hanya menandai request `approved`; belum membuat user, password, role, atau app access otomatis.
- Tidak ada mass approve.

CORE-ACCOUNT-2 sengaja berhenti di skeleton review agar tahap user creation aman bisa dirancang terpisah pada CORE-ACCOUNT-3.

## CORE-ACCOUNT-2B Disable Public Registration And Admin-Only Provisioning

CORE-ACCOUNT-2B mengunci keputusan operasional bahwa registrasi publik tidak dibuka secara mandiri.

- `GET /account-request` menampilkan halaman informasi disabled jika `CORE_PUBLIC_ACCOUNT_REQUEST_ENABLED=false`.
- `POST /account-request` ditolak `403` saat registrasi publik disabled.
- `/register` tetap redirect ke `/account-request`, tetapi tidak menampilkan form registrasi aktif.
- Admin resource untuk `AccountRequest` tetap ada sebagai histori/skeleton dan dapat dipakai kembali jika fitur publik diaktifkan eksplisit.
- Akun aktif dibuat melalui `UserResource` atau Import Center oleh Admin Core.
- Username mengikuti identitas resmi: mahasiswa memakai NIM, dosen memakai NIDN/NIP/nomor dosen, tendik/staf/laboran memakai nomor kepegawaian.
- Password awal admin/import mengikuti strategi `CORE_INITIAL_PASSWORD_STRATEGY`; default `name`, opsi lama `birth_date` tetap tersedia via konfigurasi.
- Password awal selalu hashed dan user baru dipaksa `must_change_password=true`.
- App access tetap diberikan manual/eksplisit melalui resource atau import app access; tidak ada akses aplikasi otomatis dari request publik.

## Master Profile Auto User Provisioning

Core dapat otomatis membuat akun user saat master profile mahasiswa, dosen, atau tendik/staf/laboran dibuat tanpa `user_id`.

Aturan username:

- Mahasiswa: `students.student_number` / NIM.
- Dosen: `lecturers.lecturer_number` / NIDN/NIP/nomor dosen.
- Tendik/staf/laboran: `employees.employee_number`.

Aturan password awal:

- Format: `NamaDepan + 4 karakter akhir identifier + !`.
- Contoh `Andi nurjanah` dengan NIM `221011402637`: `Andi2637!`.
- Contoh nama empat kata `Muhammad Rizky Aditya Pratama` dengan NIM `221011409999`: `Muhammad9999!`.
- Password tetap langsung di-hash di database.
- User baru selalu `must_change_password=true`.

Aturan keamanan:

- Jika profile sudah punya `user_id`, Core tidak membuat user baru.
- Jika user existing cocok berdasarkan username/email/identity, profile akan ditautkan ke user tersebut.
- Jika ada lebih dari satu matching user, provisioning ditahan sebagai blocker dan tidak membuat duplikat.
- Jika identifier, nama, atau email kosong, provisioning dilewati.
- Tidak ada role admin otomatis.
- Tidak ada app access otomatis.

Backfill data lama:

- Default dry-run: `php artisan core:provision-master-users`.
- Apply terkontrol: `php artisan core:provision-master-users --apply`.
- Batasi per tipe/identifier bila perlu, contoh:
  `php artisan core:provision-master-users --apply --only=students --identifier=221011402637`.

## Password Policy

Prinsip:

- Password Core adalah password canonical.
- Password selalu hashed.
- Password plaintext tidak disimpan, tidak ditampilkan, tidak diekspor, tidak dilog.
- Admin/import-created user harus `must_change_password=true`.
- Self-registration publik saat ini disabled. Jika nanti diaktifkan, user harus tetap melewati validasi password kuat dan pending approval sebelum app access.
- Password change di Core berlaku untuk semua consumer app yang memverifikasi credential ke Core.

Current state:

- Admin Core punya `/admin/change-password`.
- `ChangePassword` memvalidasi current password, password confirmation, minimal 8, dan tidak boleh sama dengan password lama.
- Password change membuat audit `user.password_changed`.
- Profile Portal punya `/profile/change-password` untuk authenticated user, termasuk non-admin.
- Profile password change memvalidasi current password, confirmation, minimal 8, dan tidak boleh sama dengan password lama.
- Password baru langsung hashed, `must_change_password=false`, `password_changed_at` diisi, dan audit `profile.password_changed` dibuat tanpa menyimpan nilai password.
- User dengan `must_change_password=true` dipaksa mengganti password sebelum membuka `/profile` atau `/profile/edit`.
- Setelah password awal diganti, user yang profilnya belum lengkap diarahkan ke `/profile/edit`; user yang profilnya lengkap diarahkan ke `/profile`.
- Non-admin tetap tidak bisa membuka `/admin`.

## Profile Completion Policy

Profile Portal saat ini:

- Route `/profile/login`, `POST /profile/login`, `/profile/logout`, `/profile`, `/profile/edit`, `PUT /profile`, `/profile/change-password`, `PUT /profile/change-password`.
- Self-only.
- Editable safe fields:
  - phone.
  - address.
  - alternate_email jika kolom tersedia.
- Completion summary menampilkan linked profile, email, official identifier, phone, dan address.
- Completion indicator menampilkan status `Profil lengkap` atau `Profil belum lengkap`.
- Birth date tidak menjadi syarat completion dan tidak ditampilkan sebagai nilai penuh di Profile Portal.

User tidak boleh edit:

- NIM.
- NIDN/NIP.
- employee number.
- identity number.
- prodi.
- department.
- status.
- global role.
- app access.
- leadership/jabatan.

Roadmap:

- Tambahkan field aman tambahan hanya setelah ada kolom dan policy jelas.
- Tambahkan review flag bila user mengusulkan perubahan data resmi, bukan langsung update.
- Profile official correction request harus masuk approval queue.

## App Access Assignment Policy

Prinsip:

- App access tetap admin-controlled.
- Self-registration tidak memberi app access otomatis.
- User app access disimpan di `user_app_accesses`.
- Role aplikasi berasal dari `core_application_roles`.
- Role global berbeda dari app-specific role.

Approval:

- Admin Core memilih aplikasi dan role.
- Assignment aktif hanya jika user active dan app access `is_active=true`.
- Inactive user harus tidak bisa memakai access walau row masih ada.
- App access sensitif seperti admin app harus perlu review manual.

## Consumer App Login Policy

Target:

- Aplikasi lain memakai Core password verification dan app access check.
- Consumer tetap memiliki session/guard lokal masing-masing bila diperlukan.
- Core tidak membuat cross-app browser session.

Allowed pattern:

1. User submit login di consumer app.
2. Consumer app mengirim credential ke Core endpoint app-client protected.
3. Core verifikasi:
   - user exists.
   - user active.
   - password valid.
   - app access aktif untuk `app_code`.
4. Core mengembalikan safe identity/access response.
5. Consumer app membuat session lokal miliknya sendiri.

Non-allowed pattern:

- SSO tanpa desain terpisah.
- Auto-login dari Core ke consumer.
- Token di URL.
- Sharing session cookie lintas app.
- Consumer menyimpan password Core.
- Consumer menulis balik password ke Core.
- Consumer memberi role/app access sendiri.

Existing implementation note:

- TU sudah memiliki endpoint `POST /api/v1/internal/apps/tu-farmasi/portal-auth/verify`.
- Endpoint tersebut tidak membuat token, session, atau auto-login.
- Endpoint generik untuk semua app masih perlu tahap desain dan guardrail baru.

## Security Risks And Mitigation

| Risiko | Mitigasi |
| --- | --- |
| Fake self-registration | Pending approval, duplicate checks, admin verification. |
| Email/NIM/NIDN duplicate | Unique checks dan manual merge/link flow. |
| Automatic privilege escalation | No automatic app access, no automatic admin role. |
| Non-admin masuk `/admin` | Keep `User::canAccessPanel()` active + role `super-admin/admin-core`. |
| Password leakage | Hash only, no plaintext logs/reports/templates, reject password columns in import. |
| Token URL misuse | Explicitly banned; app-client auth via headers only. |
| Consumer stores Core password | Banned; consumer only verifies via Core endpoint. |
| Account request spam | Rate limit, captcha/honeypot if public, admin queue limits. |
| Sensitive official data edited by user | Profile Portal only allows safe contact fields; official changes go to approval request. |
| Orphan profile | Approval requires linking/creating student/lecturer/employee profile. |

## Non-Goals

- Tidak membuat registrasi aktif penuh pada tahap ini.
- Tidak membuat SSO.
- Tidak membuat auto-login.
- Tidak membuat token URL.
- Tidak memberi app access otomatis.
- Tidak membuka `/admin` untuk non-admin.
- Tidak mengubah password user existing.
- Tidak mengubah aplikasi consumer.

## Roadmap

### Phase 1: Planning And Gap Lock

- Dokumen lifecycle ini disetujui.
- Tentukan apakah pending request memakai tabel baru `account_requests`.
- Tentukan required fields final per registration type.

### Phase 2: Account Request Skeleton

- Selesai pada CORE-ACCOUNT-2:
  - migration additive `account_requests`.
  - model `AccountRequest`.
  - service `CoreAccountRequestService`.
  - public account request form.
  - admin review resource.
  - no automatic activation/app access.

### Phase 2B: Safe Admin User Creation From Request

- Rancang explicit admin action untuk membuat user dari approved request.
- Require confirmation.
- Require duplicate check review.
- Password policy admin/import default memakai nama user sebagai password awal sementara dan memaksa `must_change_password`.
- Tetap tidak memberi app access otomatis.

Update 2026-06-07:

- Action admin `Approve & Buat Akun` tersedia di `AccountRequestResource`.
- Approval membuat/menautkan user dan profil master:
  - mahasiswa -> `students`
  - dosen -> `lecturers`
  - tendik/staf/laboran -> `employees`
- Role global dasar diberikan sesuai jenis pemohon:
  - mahasiswa -> `mahasiswa`
  - dosen -> `dosen`
  - tendik/staf/laboran -> `tata-usaha`
- Password awal mengikuti kebijakan Core `first_name_identifier_suffix`.
- `user_app_accesses` tetap tidak dibuat otomatis.
- Konflik email/nomor identitas diblokir agar tidak membuat duplikat.

### Phase 3: Profile Password Self-Service

- Selesai pada CORE-PROFILE-4:
  - password change route di Profile Portal.
  - current password wajib.
  - password confirmation wajib.
  - password baru hashed.
  - `must_change_password` dibersihkan setelah sukses.
  - `password_changed_at` diisi.
  - non-admin bisa ganti password tanpa akses `/admin`.

### Phase 4: Generic Consumer Credential Verification

- Desain endpoint generic app-client protected:
  - `POST /api/v1/internal/apps/{app_code}/auth/verify`
- Response safe dan generic failure.
- Ability scoped per app.
- No token/session.

### Phase 5: Staging Smoke And Cutover Per App

- Consumer app aktifkan Core verification bertahap.
- Browser smoke test per role.
- Rollback ke local/legacy auth mode bila gagal.
